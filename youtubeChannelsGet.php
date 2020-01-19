<?php
/*
・DataAPIドキュメント
https://developers.google.com/youtube/v3/getting-started?hl=ja
https://developers.google.com/youtube/v3/code_samples/php?hl=ja
https://developers.google.com/youtube/v3/docs/channels?hl=ja

-------Install-------
$ composer require google/apiclient:~2.0
 */

date_default_timezone_set('Asia/Tokyo');

if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
  throw new \Exception('please run "composer require google/apiclient:~2.0" in "' . __DIR__ .'"');
}

require_once __DIR__ . '/vendor/autoload.php';

$DEVELOPER_KEY = '<取得したYouTubeDataAPIのキーを入力>';

$client = new Google_Client();
$client->setDeveloperKey($DEVELOPER_KEY);

$youtube = new Google_Service_YouTube($client);

$basicInfoFile = 'basicInfo.txt';
$dayInfoFile = 'dayInfo.txt';

$htmlBody = '';
$basicInfoList = '';
$dayInfoList = '';

// YouTuberリスト
$youtuberList = "";
include "youtuberList.php";

$youtuberIDList = "";
foreach ($youtuberList as $y){
  $youtuberIDList .= $y[1] . ",";
}
$youtuberIDList = rtrim($youtuberIDList, ",");

try {
    $channelsResponse = $youtube->channels->listChannels('id,snippet,statistics,brandingSettings', array(
        'id' => $youtuberIDList
    ));

    // var_dump($channelsResponse['items']);

    foreach ($channelsResponse['items'] as $cResult) {
        $t = new DateTime($cResult['snippet']['publishedAt']);
        $t->setTimeZone(new DateTimeZone('Asia/Tokyo'));
        $publishedAt = $t->format('Y/m/d H:i:s');

        $today = date('Y/m/d');

        //■基本情報 - チャンネルID,チャンネル名,チャンネル開始日,ヘッダー画像
        //■デイリー情報 - 日付,チャンネルID,チャンネル登録者数,合計投稿数,合計再生数
        $basicInfo = sprintf('"%s","%s","%s","%s"', $cResult['id'], $cResult['snippet']["title"],$cResult['brandingSettings']["image"]["bannerImageUrl"], $publishedAt). "\n";
        $dayInfo = sprintf('"%s","%s","%d","%d","%d"', $today, $cResult['id'], $cResult['statistics']["subscriberCount"], $cResult['statistics']["videoCount"], $cResult['statistics']["viewCount"]). "\n";

        $basicInfoList .= $basicInfo;
        $dayInfoList .= $dayInfo;
    }
    
    file_put_contents(__DIR__ . "/" . $basicInfoFile, $basicInfoList);
    
    $fp = fopen(__DIR__ . "/" . $dayInfoFile,"a");
    @fwrite($fp, $dayInfoList);
    fclose($fp);

    $htmlBody .= "<h2>basicInfo</h2><pre>$basicInfoList</pre>";
    $htmlBody .= "<h2>dayInfo</h2><pre>$dayInfoList</pre>";

} catch (Google_Service_Exception $e) {
    $htmlBody .= sprintf('<p>A service error occurred: <code>%s</code></p>',
    htmlspecialchars($e->getMessage()));
} catch (Google_Exception $e) {
    $htmlBody .= sprintf('<p>An client error occurred: <code>%s</code></p>',
    htmlspecialchars($e->getMessage()));
}

?>

<!doctype html>
<html>
  <head>
    <title>YouTube channel Result</title>
  </head>
  <body>
    <?=$htmlBody?>
  </body>
</html>