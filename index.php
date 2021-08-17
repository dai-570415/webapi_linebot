<?php
header('Content-Type: application/json; charset=UTF-8');

// フォルダ名・ファイル名は任意
include_once('./inc/choinomi/kobe/15.php');

//　methodパラメータが付与されたら
if(isset($_GET['method'])) {
    // LINEで送信して送られてくる情報
    $getX = htmlspecialchars($_GET['x']);
    $getY = htmlspecialchars($_GET['y']);
    // 小数点に変更 
    $floatGetX = floatval($getX);
    $floatGetY = floatval($getY);

    foreach ($db as $val) {
        foreach ($val['shop'] as $shop_detail) {
            $name = $shop_detail['name'];
            $line = $shop_detail['line'];
            $longitude = floatval($shop_detail['x']);
            $latitude = floatval($shop_detail['y']);

            $sortX = abs($floatGetX - $longitude);
            $sortY = abs($floatGetY - $latitude);

            $arr['response']['shop'][] = [
                'name' => $name,
                'line' => $line,
                'x' => $longitude,
                'y' => $latitude,
                'distanceX' => $sortX,
                'distanceY' => $sortY,
            ];
        }
    }
} else {
    $arr['status'] = 'no request';
}

// ソート処理
foreach($arr['response']['shop'] as $key => $val) {
    $distanceX[$key] = $val['distanceX'];
    $distanceY[$key] = $val['distanceY'];
}
array_multisort(
    $distanceX, SORT_ASC,
    $distanceY, SORT_ASC,
    $arr['response']['shop']
);

// jsonに変換
print json_encode($arr, JSON_PRETTY_PRINT);