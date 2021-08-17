# CREATE LINE BOT
　
## 作ったもの
LINEで位置情報を送信するとその位置から近い場所順に登録店舗が表示できるアプリ（ひとまず2件表示できるようにしています）
店舗登録は配列に入れていくだけで簡単に追加可能
　
## まずはphpでWebAPIを実装する
　
### 環境
- php
- phpビルトインサーバー　※Xamppとかでも大丈夫です
- heroku ※こちらは任意です
　
```
$ git clone https://github.com/dai-570415/webapi_linebot.git
$ php -S localhost:8000
```
　
こちらでアクセスするとローカル上でAPIが確認できます。
[http://localhost:8000/?method=get&x=135&y=35](http://localhost:8000/?method=get&x=135&y=35)
　
自分の所有しているphpが動かせるサーバーにデプロイすると公開できます。
今回はherokuでデプロイしてました。
　
　
# データリソース追加方法
　
```php
// ./inc/choinomi/kobe/15.php
$db['db'] = [
    'shop' => [
        [
            'name' => '店舗名',
            'line' => '最寄駅',
            'x' => 000.0000000, // 緯度
            'y' => 00.000000, // 経度
        ],
        // ... コピーして追加
    ],
];
```
(注) 項目を増やしたりkey値を変更した場合はindex.phpとGASファイルを修正する必要があります。
　
　
## Google App ScriptでBOTを実装する
　
### 環境
- Google App Script
- LINE Developper
　
①Google App Scriptの新規プロジェクト作成
②LINE Developperで新規プロジェクトを作成し、Channel access tokenを発行して入れてください
③「https://<your-domain>.com」をサーバーのURLにする
④以上入れたら「公開」→「ウェブアプリケーションとして導入」→「Project version」を「New」→「更新」
⑤「Current web app URL」が発行されるのでLINE Developper側の「Messaging API」→「Webhook URL」の中に③で発行されたURLを入れる
※Google App ScriptとLINE Developperの使い方は割愛させていただきます。
　
```js
const access_token = "LINE API TOKEN"; // ①
const line_endpoint = 'https://api.line.me/v2/bot/message/reply';

function doPost(e) {

  const json = JSON.parse(e.postData.contents);

  //返信するためのトークン取得
  const reply_token= json.events[0].replyToken;
  //送られたメッセージ内容を取得
  const longitude = json.events[0].message.longitude;
  const latitude = json.events[0].message.latitude;

    const urlresponse = getApi(longitude,latitude);
    const length = Object.keys(urlresponse.response.shop).length;

        let name = [],
            line = [],
            encodename = [];

        for (let i = 0; i <= length-1; i++) {
            name.push(urlresponse.response.shop[i].name);
            line.push(urlresponse.response.shop[i].line);
            const encode = name[i]+line[i];
            encodename.push(encodeURI(encode));
        }
  
        let label1;
        let uri1;
        if (name[0] === undefined) {
          label1 = "該当なし";
          uri1 = "https://maps.google.co.jp/maps?q=";
        } else {
          label1 = name[0]+"("+line[0]+")";
          uri1 = "https://maps.google.co.jp/maps?q="+encodename[0];
        }
  
        let label2;
        let uri2;
        if (name[1] === undefined) {
          label2 = "該当なし";
          uri2 = "https://maps.google.co.jp/maps?q=";
        } else {
          label2 = name[1]+"("+line[1]+")";
          uri2 = "https://maps.google.co.jp/maps?q="+encodename[1];
        }

        // ... 追加する場合はコピーして各数値を+1して変更

        const message = {
            "replyToken": reply_token,
            "messages": [
              {
                "type": "flex",
                "altText": "Flex Message",
                "contents": {
                  "type": "bubble",
                  "direction": "ltr",
                  "header": {
                    "type": "box",
                    "layout": "vertical",
                    "contents": [
                      {
                        "type": "text",
                        "text": "現在地からの最寄駅一覧",
                        "align": "center",
                        "weight": "bold"
                      },
                      {
                        "type": "text",
                        "text": "駅名をクリックでGoogleMap",
                        "size": "sm",
                        "align": "center"
                      }
                    ]
                  },
                  "footer": {
                    "type": "box",
                    "layout": "vertical",
                    "contents": [
                      {
                        "type": "button",
                        "action": {
                          "type": "uri",
                          "label": label1,
                          "uri": uri1
                        }
                      },
                      {
                        "type": "button",
                        "action": {
                          "type": "uri",
                          "label": label2,
                          "uri": uri2
                        }
                      },
                      // ... 追加する場合はコピーして各数値を+1して変更
                    ]
                  }
                }
              }
            ]
          }

    const replyData = {
        "method": "post",
        "headers": {
            "Content-Type": "application/json",
            "Authorization": "Bearer " + access_token
        },
        "payload": JSON.stringify(message)
    };
    try {
        UrlFetchApp.fetch(line_endpoint, replyData);
    } catch (e) {
        return "error";
    }
}

// 実装したAPIを呼び出す関数
function getApi(longitude,latitude) {
    try {
        const url = 'https://<your-domain>.com?method=get&' + 'x=' + longitude + '&y=' + latitude; // ②
        const urlresponse = UrlFetchApp.fetch(url);
        return JSON.parse(urlresponse);
    } catch (e) {
        return "error";
    }
}
```