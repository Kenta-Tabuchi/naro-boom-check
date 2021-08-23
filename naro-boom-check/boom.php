<!DOCTYPE html>
<html lang="ja">
<head>
    <title>なろう流行チェッカー</title>
    <link href="./boom.css" rel="stylesheet">
    <style>
        td{
            background:snow !important;
        }
    </style>
</head>

<body>
<div class="main">
    <h1>なろう流行チェッカー</h1>
<?php

//****************変数定義****************

$rank_count = 0; //ランキング読み込みループ切り上げ用のループカウンタ
$rank_count_max = 100; //何位まで読み込むかの最大値

$word_list = array('単語リスト');//形態素解析して得たワードリスト
$word_freq = array(9999);//形態素解析して得たワードの出現回数
$word_looked = array(0);//そのワードは今チェックしてる作品でもう見たぞフラグ
$word_keyin = array(0);//そのワードはキーワードに入ってるぞフラグ

$word_flag = 0;//ワードリストの中に今チェックしてる単語があるかどうかフラグ

$genre_list =array('ジャンルリスト');//読み込んだ作品のジャンルコードを格納するリスト

require_once './igo/Igo.php';  //形態素解析ツールigoのアドレス指定
$igo = new Igo("./ipadic", "UTF-8"); //辞書フォルダのアドレス指定

//****************読み込んだものをword_listへ入れる関数****************
function word_list_in($in_data){
    //グローバル変数指定
    global $igo,$word_list,$word_freq,$word_looked,$word_flag,$word_keyin;

    $result = $igo->parse($in_data);

    foreach($result as $key=>$value){
        //featureをexplodeで分割する
        $explode_data=explode(',',$value->feature);
        //助詞・助動詞・記号を弾く
        if(strcmp($explode_data[0],'助詞')==0||strcmp($explode_data[0],'助動詞')==0||strcmp($explode_data[0],'記号')==0){
            continue;
        }

        ////一般・固有名詞が出て来た場合、原形の部分にそれを一時的に入れる
        if(strcmp($explode_data[6],'*')==0){
            //echo $value->surface.$value->feature.'</br>';
            $explode_data[6]=$value->surface;
        }

        //word_listの中を見る
        //ワード存在確認フラグをリセット
        $word_flag = 0;
        for($x=0;$x<count($word_list);$x++){
            //word_listの中に今チェックしている奴と同じ奴があった場合
            if(strcmp($word_list[$x], $explode_data[6])==0){
                //もう見た場合は発見フラグだけ立ててスルー
                if($word_looked[$x]==1){
                    $word_flag=1;
                    break;
                }else{
                    //初めて見た場合は見たフラグを立てた上で出現回数を1つ増やす
                    $word_looked[$x]=1;
                    $word_freq[$x]++;                    
                    $word_flag=1;
                    break;
                }
            }
        }
        if($word_flag==0){
            //同じ奴がなかった場合word_listの末尾に追加
            array_push($word_list,$explode_data[6]);
            //word_freqの末尾に出現回数の1を追加
            array_push($word_freq,1);
            //word_looedの末尾にもう見たフラグの1を追加
            array_push($word_looked,1);
            //word_keyinの末尾にキーワードではないフラグの0を追加
            array_push($word_keyin,0);
        }

        //デバッグ用：形態素解析画面出力
        /*
        print 'surface='.$value->surface.'</br>';
        print 'feature='.$value->feature.'</br>';
        print 'start='.$value->start.'</br>';
        */        
    }        
}


//****************本文開始***************

print '日付：'.$_GET['day'].'</br>';
print '対象：';
switch ($_GET['type']){
    case 'd':
        print '日間';
        break;
    case 'w':
        print '週間';
        break;        
    case 'm':
        print '月間';
        break;
    case 'q':
        print '四半期';
        break;
    default:
        print 'クエリがありません';
    }
print '</br>';
print '取得対象:'. $rank_count_max .'位まで</br>';
print '</br>';

//指定された日付の日刊ランキング取得
//APIのURL(パラメーターを指定してください)
$url='https://api.syosetu.com/rank/rankget/?out=json&gzip=5&rtype='.$_GET['day'].'-'.$_GET['type'];

//ヘッダーの設定
$options =array(
    'http' =>array(
            'header' => "User-Agent:".$_SERVER['HTTP_USER_AGENT'],
            )
    );

//APIを取得
$file = file_get_contents($url, false, stream_context_create($options));

//解凍する
$file=gzdecode($file);

//JSONデコード
$listarray=json_decode($file,true);

//読み込んだデータを解析する
foreach($listarray as $key=>$value){
    $rank_count++;
    if($rank_count>$rank_count_max){break;}; //指定した順位まで読み込んだら切り上げ

    //nコードからタイトルとあらすじを取得
    $now_url='https://api.syosetu.com/novelapi/api/?out=json&gzip=5&ncode='.$value['ncode'];

    //APIを取得
    $now_file = file_get_contents($now_url, false, stream_context_create($options));

    //解凍する
    $now_file=gzdecode($now_file);

    //JSONデコード
    $now_listarray=json_decode($now_file,true);

    //作品ごとにチェック
    foreach($now_listarray as $now_key=>$now_value){
        //nコードの存在しない小説の場合要素0に[{"allcount":0}]と入っているのでそれをチェック
        if($now_key==0){
            if($now_value['allcount']=='0'){
                //デバッグ用：画面出力
                //print '<b>'.$value['rank']."位</b><br>";
                //print ('この小説は存在しません');
                break;
            }
        }else{
            //要素0はcontinueで飛ばす
            if($now_key==0){continue;}
            //ジャンルリストに投げ込む
            array_push($genre_list,$now_value['genre']);

            //形態素解析
            //****************タイトルの形態素解析****************
            word_list_in($now_value['title']);

            //****************あらすじの形態素解析****************
            word_list_in($now_value['story']);

            //****************キーワード****************
            //キーワードは形態素解析せずにexplodeで分割する
            $explode_keyword=explode(' ',$now_value['keyword']);
            //キーワード数分ループ
            for($a=0;$a<count($explode_keyword);$a++){
                //word_listの中を見る
                //ワード存在確認フラグをリセット
                $word_flag = 0;
                for($x=0;$x<count($word_list);$x++){
                    //word_listの中に今チェックしている奴と同じ奴があった場合
                    if(strcmp($word_list[$x], $explode_keyword[$a])==0){
                        //キーワードに入ってるよフラグを立てる
                        $word_keyin[$x]=1;
                        //もう見た場合は発見フラグを立て脱出
                        if($word_looked[$x]==1){
                            $word_flag=1;
                            break;
                        }else{
                            //初めて見た場合は見たフラグを立てた上で出現回数を1つ増やす
                            $word_looked[$x]=1;
                            $word_freq[$x]++;                    
                            $word_flag=1;
                            break;
                        }
                    }
                }
                if($word_flag==0){
                    //同じ奴がなかった場合word_listの末尾に追加
                    array_push($word_list,$explode_keyword[$a]);
                    //word_freqの末尾に出現回数の1を追加
                    array_push($word_freq,1);
                    //word_looedの末尾にもう見たフラグの1を追加
                    array_push($word_looked,1);
                    //word_keyinの末尾にキーワードにあるフラグの1を追加
                    array_push($word_keyin,1);
                }
            }

            //word_lookedのフラグをリセット
            for($x=0;$x<count($word_looked);$x++){
                $word_looked[$x]=0;
            }

            //デバッグ用：画面出力
            //print nl2br($now_value['keyword'])."<br>";

            //デバッグ用：小説情報の画面出力
            /*
            print '<b>'.$value['rank']."位</b><br>"; //ランク
            print '<b>'.$now_value['title']."</b><br>"; //タイトル
            print nl2br($now_value['story'])."<br>"; //あらすじ
            print nl2br($now_value['keyword'])."<br>"; //キーワード
            $novelurl='http://ncode.syosetu.com/'.strtolower($value['ncode']).'/'; //URL
            print '<a href="'.$novelurl.'">'.$novelurl.'</a>'; //小説へのリンク
            */
        }
        //デバッグ用：画面出力
        //print '<hr>';
    }
}

print '▼ジャンル出現頻度<br>';
//****************ジャンル出現頻度カウント****************
$g_101=0;
$g_102=0;
$g_201=0;
$g_202=0;
$g_301=0;
$g_302=0;
$g_303=0;
$g_304=0;
$g_305=0;
$g_306=0;
$g_307=0;
$g_401=0;
$g_402=0;
$g_403=0;
$g_404=0;
$g_9901=0;
$g_9902=0;
$g_9903=0;
$g_9904=0;
$g_9999=0;
$g_9801=0;
for($x=1;$x<count($genre_list);$x++){
    switch($genre_list[$x]){
        case 101:
            $g_101++;
            break;
        case 102:
            $g_102++;
            break;
        case 201:
            $g_201++;
            break;
        case 202:
            $g_202++;
            break;
        case 301:
            $g_301++;
            break;
        case 302:
            $g_302++;
            break;
        case 303:
            $g_303++;
            break;
        case 304:
            $g_304++;
            break;
        case 305:
            $g_305++;
            break;
        case 306:
            $g_306++;
            break;
        case 307:
            $g_307++;
            break;
        case 401:
            $g_401++;
            break;
        case 402:
            $g_402++;
            break;
        case 403:
            $g_403++;
            break;
        case 404:
            $g_404++;
            break;
        case 9901:
            $g_9901++;
            break;
        case 9902:
            $g_9902++;
            break;
        case 9903:
            $g_9903++;
            break;
        case 9904:
            $g_9904++;
            break;
        case 9999:
            $g_9999++;
            break;
        case 9801:
            $g_9801++;
            break;           
    }
}

//ジャンルテーブルを作成
print '<table border="1" width="100%">';
print '<tr><td width="20%">異世界〔恋愛〕</td>';
print '<td width="5%">'.$g_101.'</td>';
printf("<td><hr size=\"10\" color=\"#cc6633\" align=\"left\" width=\"%d%%\"></td>", $g_101 / $rank_count_max * 100);
print '</tr>';
print '<tr>';
print '<tr><td width="20%">現実世界〔恋愛〕</td>';
print '<td width="5%">'.$g_102.'</td>';
printf("<td><hr size=\"10\" color=\"#cc6633\" align=\"left\" width=\"%d%%\"></td>", $g_102 / $rank_count_max * 100);
print '</tr>';
print '<tr><td width="20%">ハイファンタジー〔ファンタジー〕</td>';
print '<td width="5%">'.$g_201.'</td>';
printf("<td><hr size=\"10\" color=\"#cc6633\" align=\"left\" width=\"%d%%\"></td>", $g_201 / $rank_count_max * 100);
print '</tr>';
print '<tr><td width="20%">ローファンタジー〔ファンタジー〕</td>';
print '<td width="5%">'.$g_202.'</td>';
printf("<td><hr size=\"10\" color=\"#cc6633\" align=\"left\" width=\"%d%%\"></td>", $g_202 / $rank_count_max * 100);
print '</tr>';
print '<tr><td width="20%">純文学〔文芸〕</td>';
print '<td width="5%">'.$g_301.'</td>';
printf("<td><hr size=\"10\" color=\"#cc6633\" align=\"left\" width=\"%d%%\"></td>", $g_301 / $rank_count_max * 100);
print '</tr>';
print '<tr><td width="20%">ヒューマンドラマ〔文芸〕</td>';
print '<td width="5%">'.$g_302.'</td>';
printf("<td><hr size=\"10\" color=\"#cc6633\" align=\"left\" width=\"%d%%\"></td>", $g_302 / $rank_count_max * 100);
print '</tr>';
print '<tr><td width="20%">歴史〔文芸〕</td>';
print '<td width="5%">'.$g_303.'</td>';
printf("<td><hr size=\"10\" color=\"#cc6633\" align=\"left\" width=\"%d%%\"></td>", $g_303 / $rank_count_max * 100);
print '</tr>';
print '<tr><td width="20%">推理〔文芸〕</td>';
print '<td width="5%">'.$g_304.'</td>';
printf("<td><hr size=\"10\" color=\"#cc6633\" align=\"left\" width=\"%d%%\"></td>", $g_304 / $rank_count_max * 100);
print '</tr>';
print '<tr><td width="20%">ホラー〔文芸〕</td>';
print '<td width="5%">'.$g_305.'</td>';
printf("<td><hr size=\"10\" color=\"#cc6633\" align=\"left\" width=\"%d%%\"></td>", $g_305 / $rank_count_max * 100);
print '</tr>';
print '<tr><td width="20%">アクション〔文芸〕</td>';
print '<td width="5%">'.$g_306.'</td>';
printf("<td><hr size=\"10\" color=\"#cc6633\" align=\"left\" width=\"%d%%\"></td>", $g_306 / $rank_count_max * 100);
print '</tr>';
print '<tr><td width="20%">コメディー〔文芸〕</td>';
print '<td width="5%">'.$g_307.'</td>';
printf("<td><hr size=\"10\" color=\"#cc6633\" align=\"left\" width=\"%d%%\"></td>", $g_307 / $rank_count_max * 100);
print '</tr>';
print '<tr><td width="20%">VRゲーム〔SF〕</td>';
print '<td width="5%">'.$g_401.'</td>';
printf("<td><hr size=\"10\" color=\"#cc6633\" align=\"left\" width=\"%d%%\"></td>", $g_401 / $rank_count_max * 100);
print '</tr>';
print '<tr><td width="20%">宇宙〔SF〕</td>';
print '<td width="5%">'.$g_402.'</td>';
printf("<td><hr size=\"10\" color=\"#cc6633\" align=\"left\" width=\"%d%%\"></td>", $g_402 / $rank_count_max * 100);
print '</tr>';
print '<tr><td width="20%">空想科学〔SF〕</td>';
print '<td width="5%">'.$g_403.'</td>';
printf("<td><hr size=\"10\" color=\"#cc6633\" align=\"left\" width=\"%d%%\"></td>", $g_403 / $rank_count_max * 100);
print '</tr>';
print '<tr><td width="20%">パニック〔SF〕</td>';
print '<td width="5%">'.$g_404.'</td>';
printf("<td><hr size=\"10\" color=\"#cc6633\" align=\"left\" width=\"%d%%\"></td>", $g_404 / $rank_count_max * 100);
print '</tr>';
print '<tr><td width="20%">童話〔その他〕</td>';
print '<td width="5%">'.$g_9901.'</td>';
printf("<td><hr size=\"10\" color=\"#cc6633\" align=\"left\" width=\"%d%%\"></td>", $g_9901 / $rank_count_max * 100);
print '</tr>';
print '<tr><td width="20%">詩〔その他〕</td>';
print '<td width="5%">'.$g_9902.'</td>';
printf("<td><hr size=\"10\" color=\"#cc6633\" align=\"left\" width=\"%d%%\"></td>", $g_9902 / $rank_count_max * 100);
print '</tr>';
print '<tr><td width="20%">エッセイ〔その他〕</td>';
print '<td width="5%">'.$g_9903.'</td>';
printf("<td><hr size=\"10\" color=\"#cc6633\" align=\"left\" width=\"%d%%\"></td>", $g_9903 / $rank_count_max * 100);
print '</tr>';
print '<tr><td width="20%">リプレイ〔その他〕</td>';
print '<td width="5%">'.$g_9904.'</td>';
printf("<td><hr size=\"10\" color=\"#cc6633\" align=\"left\" width=\"%d%%\"></td>", $g_9904 / $rank_count_max * 100);
print '</tr>';
print '<tr><td width="20%">その他〔その他〕</td>';
print '<td width="5%">'.$g_9999.'</td>';
printf("<td><hr size=\"10\" color=\"#cc6633\" align=\"left\" width=\"%d%%\"></td>", $g_9999 / $rank_count_max * 100);
print '</tr>';
print '<tr><td width="20%">ノンジャンル〔その他〕</td>';
print '<td width="5%">'.$g_9801.'</td>';
printf("<td><hr size=\"10\" color=\"#cc6633\" align=\"left\" width=\"%d%%\"></td>", $g_9801 / $rank_count_max * 100);
print '</tr>';
print '</table>';

print '</br></br>';


print '▼ワードリスト一覧<br>（太字はキーワードにあるもの）<br>';

//word_listをソート
for($x=0;$x<count($word_list)-1;$x++){
    for($y=count($word_list)-1;$y>$x;$y--){
        if($word_freq[$y-1]<$word_freq[$y]){
            $temp_word=$word_list[$y-1];
            $temp_freq=$word_freq[$y-1];
            $temp_keyin=$word_keyin[$y-1];
            $word_list[$y-1]=$word_list[$y];
            $word_freq[$y-1]=$word_freq[$y];
            $word_keyin[$y-1]=$word_keyin[$y];
            $word_list[$y]=$temp_word;
            $word_freq[$y]=$temp_freq;
            $word_keyin[$y]=$temp_keyin;
        }
    }
}

//****************ワードリストテーブルを作成****************
print '<table border="1" width="100%" style="table-layout:fixed">';

//word_listとword_freqを出力
for($x=1;$x<count($word_list);$x++){
    //以下のワードは出力しても意味がないので出力せず弾く
    switch ($word_list[$x]){
        case 'する':
        case 'いる':
        case 'れる':
        case 'の':
        case 'こと':
        case 'なる':
        case 'ある':
        case 'ない':
        case 'られる':
        case '言う':
        case 'しまう':
        case 'この':
        case '思う':
        case 'それ':
        case 'その':
        case 'よう':
        case 'くる':
        case 'くれる':
        case 'そして':
        case 'しかし':
        case 'そんな':
        case '私':
        case '話':
        case 'いく':
        case '男':
        case '彼':
        case '自分':
        case '～':
        case 'ため':
        case 'これ':
        case 'できる':
        case 'ん':
        case '何':
        case 'せる':
        case 'もの':
            break;
    
        default:
            print '<tr><td width="20%" style="word-wrap:break-word;">';            
            if($word_keyin[$x]==1){
                //キーワードに入ってる奴は太字で出力
                print '<b>'.$word_list[$x].'</b></br>';
            }else{
                print $word_list[$x].'</br>';
            }
            print '</td>';
            //出現回数明記
            print '<td width="5%">'.$word_freq[$x].'</td>';
            //棒グラフ作成
            printf("<td><hr size=\"10\" color=\"#cc6633\" align=\"left\" width=\"%d%%\"></td>", $word_freq[$x] / $rank_count_max * 100);
            print '</tr>';
    }
}

print '</table>';
print '</br>';
print '<u><a href="./top.php">トップへ戻る</a></u>';
?>
</div>
</body>