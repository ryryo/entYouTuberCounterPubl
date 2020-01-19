# About
指定したYouTubeチャンネルの登録者数を記録し記事用に加工するコードです。

# 初期設定
GoogleのAPIライブラリをインストールする必要があります。

$ composer require google/apiclient:~2.0

またAPIキーが必要です。
デベロッパーコンソール(https://console.developers.google.com/)から発行したAPIキーをyoutubeChannelsGet.php の$DEVELOPER_KEYへ入力してください。

# 各ファイルについて
- youtuberList.php
   - 取得するチャンネルの情報を記述するファイル
- youtubeChannelsGet.php
   - youtuberList.phpで指定したチャンネルの情報をAPIから取得するファイル。適当に1日1回実行するようにしておけばdailyの推移が取れる。
- resultTxt.php
   - 取得した情報を加工し表示するファイル。これを元にnoteの記事を書いている。https://note.com/ryryo/n/n633e695ad018
- basicInfo.txt
   - チャンネル名やヘッダー画像など、各チャンネルの基本的な情報を保存するファイル。都度上書き。
- dayInfo.txt
   - 各チャンネルのチャンネル登録者数・合計投稿数・合計再生数などを保存するファイル。都度追記。
