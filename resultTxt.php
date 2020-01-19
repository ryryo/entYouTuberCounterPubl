<?php

date_default_timezone_set('Asia/Tokyo');

$basicInfoFile = 'basicInfo.txt';
$dayInfoFile = 'dayInfo.txt';

$htmlBody = '';

// YouTuberリスト
$youtuberList = "";
include "youtuberList.php";

$basicInfo = file_get_contents($basicInfoFile);
$dayInfo = file_get_contents($dayInfoFile);

$basicInfoAr = explode("\n", $basicInfo);
$dayInfoAr = explode("\n", $dayInfo);


/**************
 infoデータを配列に変換
 **************/
$basicData = [];
foreach ($basicInfoAr as $basic){
    $b = [];
    $b = str_getcsv($basic);
    
    if($b[0] != ""){
        $id = $b[0];
        $channelName = $b[1];
        $bannerImageUrl = $b[2];
        $channelStartDay = $b[3];

        $basicData[$id]["channelName"] = $channelName;
        $basicData[$id]["bannerImageUrl"] = $bannerImageUrl;
        $basicData[$id]["channelStartDay"] = $channelStartDay;
    }
}

/**************
 dayデータを日別・月別の配列に変換
 **************/
$dayAggregate = [];
$monthAggregate = [];

$maxDay = 0;
$maxMonthDay = [];
foreach($dayInfoAr as $key => $data){
    $d = [];
    $d = str_getcsv($data);

    if($d[0] != ""){
        $day = $d[0];

        if($day > $maxDay) $maxDay = $day;
        $id = $d[1];
        $dayAggregate[$day][$id]["subscriberCount"] = $d[2];
        $dayAggregate[$day][$id]["videoCount"] = $d[3];
        $dayAggregate[$day][$id]["viewCount"] = $d[4];

        $month = date("Y/m",strtotime($day));
        if(!isset($maxMonthDay[$id][$month])){
            $maxMonthDay[$id][$month] = $day;
            $monthAggregate[$id][$month]["subscriberCount"] = $d[2];
            $monthAggregate[$id][$month]["videoCount"] = $d[3];
            $monthAggregate[$id][$month]["viewCount"] = $d[4];
        }elseif($maxMonthDay[$id][$month] < $day){
            $maxMonthDay[$id][$month] = $day;
            $monthAggregate[$id][$month]["subscriberCount"] = $d[2];
            $monthAggregate[$id][$month]["videoCount"] = $d[3];
            $monthAggregate[$id][$month]["viewCount"] = $d[4];
        }
    }
}


/**************
 記事テキスト生成
 **************/

//最新日の情報を基本情報として使う
$nowData = $dayAggregate[$maxDay];
$dayArticleData = [];
foreach($youtuberList as $yt){
    $name = $yt[0];
    $id = $yt[1];
    $url = 'https://www.youtube.com/channel/' . $id;
    $category = $yt[2];

    $subscriberCount = $nowData[$id]["subscriberCount"];
    if($subscriberCount == 0) $subscriberCount = "(非公開)";
    $videoCount = $nowData[$id]["videoCount"];
    $viewCount = $nowData[$id]["viewCount"];

    if($category == "0_Youtuber") continue;

    $dayArticleData[$category][$id]["name"] = $name;
    $dayArticleData[$category][$id]["url"] = $url;
    $dayArticleData[$category][$id]["channelName"] = $basicData[$id]["channelName"];
    $dayArticleData[$category][$id]["channelStartDay"] = $basicData[$id]["channelStartDay"];
    $dayArticleData[$category][$id]["bannerImageUrl"] = $basicData[$id]["bannerImageUrl"];
    $dayArticleData[$category][$id]["subscriberCount"] = $subscriberCount;
    $dayArticleData[$category][$id]["videoCount"] = $videoCount;
    $dayArticleData[$category][$id]["viewCount"] = $viewCount;
    $dayArticleData[$category][$id]["averageVideoViews"] = ceil($viewCount / $videoCount);
}

$article = '';
foreach ($dayArticleData as $category => $dayData){
    $article .= '<h3 class="mb-3 font-weight-bold">' . $category . '</h3>';

    //チャンネル登録者数順で連想配列をソート
    $sort = [];
    foreach ((array) $dayData as $key => $value) {
        $sort[$key] = $value['subscriberCount'];
    }
    array_multisort($sort, SORT_DESC, $dayData);

    foreach ($dayData as $id => $d){
        $cName = $d["channelName"];
        $channelLink = '<a href="' .$d["url"] .'">' . $cName . '</a>';
        $channelLinkHtml = htmlspecialchars($channelLink);

        $article .= '<div class="col-lg-12 border-bottom mb-4"><p>';
        if(strpos($d["bannerImageUrl"],'default_banner') !== false){
            $d["bannerImageUrl"] = "./img/1060x175.png";
        }
        $article .= '<a href="' . $d["bannerImageUrl"] . '" /><img src="' . $d["bannerImageUrl"] . '" /></a><br />';

        $article .= '■ ' . $d["name"] . '<br />『' . $channelLink . '』<br />';
        $article .= '<a href="' .$d["url"] .'">' . $d["url"] . '</a><br />';
        if(is_numeric($d["subscriberCount"])){
            $article .= '└チャンネル登録者: ' . number_format($d["subscriberCount"]) . '人<br />';
        }else{
            $article .= '└チャンネル登録者: ' . $d["subscriberCount"] . '<br />';
        }
        $article .= '└投稿動画数: ' . number_format($d["videoCount"]) . '本<br />';
        $article .= '└合計再生数: ' . number_format($d["viewCount"]) . '回<br />';
        $article .= '└平均再生数: ' . number_format($d["averageVideoViews"]) . '回<br />';
        $article .= '└開設日: ' . date("Y年m月d日",strtotime($d["channelStartDay"])) . ' (' . convert_to_fuzzy_time($d["channelStartDay"]) . ')<br />';

        if(count($monthAggregate[$id]) > 1){
            uksort($monthAggregate[$id], "compare_date_keys");
            $monthAggregate[$id] = array_reverse($monthAggregate[$id]);

            $maxSubscriber = 0;
            $maxViewCount = 0;

            $graphLabels = "[";
            $graphDataSubscriber = "[";
            $graphDataViewCount = "[";
            foreach ($monthAggregate[$id] as $month => $mData){
                if($maxSubscriber < $mData["subscriberCount"]) $maxSubscriber = $mData["subscriberCount"];
                if($maxViewCount < $mData["viewCount"]) $maxViewCount = $mData["viewCount"];

                $graphLabels .= "'" . $month . "',";
                $graphDataSubscriber .= "'" . $mData["subscriberCount"] . "',";
                $graphDataViewCount .= "'" . $mData["viewCount"] . "',";
            }

            $maxSubscriber = ceil(($maxSubscriber/100000))*100000;
            $maxViewCount = ceil(($maxViewCount/1000000))*1000000;

            $graphLabels = rtrim($graphLabels, ",");
            $graphDataSubscriber = rtrim($graphDataSubscriber, ",");
            $graphDataViewCount = rtrim($graphDataViewCount, ",");
            $graphLabels .= "]";
            $graphDataSubscriber .= "]";
            $graphDataViewCount .= "]";

            $article .= '<canvas id="subscriberChart-' . $id . '"></canvas>';
            $article .= <<<EOD
<script>
var ctx = document.getElementById('subscriberChart-$id').getContext('2d');
var chart = new Chart(ctx, {
// The type of chart we want to create
type: 'line',

// The data for our dataset
data: {
    labels: $graphLabels,
    datasets: [{
        label: 'チャンネル登録者数',
        backgroundColor: 'rgba(0, 0, 0, 0)',
        borderColor: 'rgb(54, 162, 235)',
        fill: false,
        lineTension: 0, //直線
        data: $graphDataSubscriber,
    }]
},
// Configuration options go here
options: {
    responsive: true,
    title: {
        display: true,
        text: '測定結果'
    },
    scales: {
        xAxes: [{
            ticks: {}
        }],
        yAxes: [{
            ticks: {
                callback: function(label, index, labels) {
                    return label.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',') +' 人';
                }
            }
        }]
    },
    tooltips: {
        mode: 'index'
    },

    }
});
</script>
EOD;
        }else{
        }

        $article .= '</p></div>';

    }
}


$htmlBody .= "<h2>article</h2>$article";
$htmlBody .= "<h2>basicInfo</h2><div class='col-lg-12'><pre>$basicInfo</pre></div>";


/**
 * X秒前、X分前、X時間前、X日前などといった表示に変換する。
 * 一分未満は秒、一時間未満は分、一日未満は時間、
 * 31日以内はX日前、それ以上はX月X日と返す。
 * X月X日表記の時、年が異なる場合はyyyy年m月d日と、年も表示する
 *
 * @param   <String> $time_db       strtotime()で変換できる時間文字列 (例：yyyy/mm/dd H:i:s)
 * @return  <String>                X日前,などといった文字列
 **/
function convert_to_fuzzy_time($time_db){
    $unix   = strtotime($time_db);
    $now    = time();
    $diff_sec   = $now - $unix;

    if($diff_sec < 60){
        $time   = floor($diff_sec);
        $unit   = "秒前";
    }
    elseif($diff_sec < 3600){
        $time   = floor($diff_sec/60);
        $unit   = "分前";
    }
    elseif($diff_sec < 86400){
        $time   = floor($diff_sec/3600);
        $unit   = "時間前";
    }
    elseif($diff_sec < 2764800){
        $time   = floor($diff_sec/86400);
        $unit   = "日前";
    }
    else{
        // $time   = $diff_sec/2592000;
        $today = (date("y") * 12) + date("m");
        $anohi = (date("y",strtotime($time_db)) * 12) + date("n",strtotime($time_db));

        $time = $today - $anohi; //月の差分

        if($time > 11){
            $year = floor($time / 12);
            $month = $time % 12;

            if($month == 0){
                $time = $year;
                $unit   = "年前";
            }else{
                $time = $year . "年" . $month;
                $unit   = "ヵ月前";
            }
        }else{
            $unit   = "ヵ月前";
        }
    }
    return $time .$unit;
}

function compare_date_keys($dt1, $dt2) {
    $tm1 = strtotime($dt1);
    $tm2 = strtotime($dt2);
    // return ($tm1 < $tm2) ? -1 : (($tm1 > $tm2) ? 1 : 0);
    return ($tm1 < $tm2) ? 1 : (($tm1 > $tm2) ? -1 : 0);
}
?>

<!doctype html>
<html>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>YouTube channel Result</title>
    <link rel="stylesheet" media="all" href="./css/style.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">

    <script src="https://code.jquery.com/jquery-3.4.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@2.8.0"></script>

  </head>
  <body>

    <div class="container">
        <section>
            <div class="row">
                <?=$htmlBody?>
            </div>
        </section>
    </div>


    </body>
</html>