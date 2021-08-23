<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <title>なろう流行チェッカー：TOP</title>
    <link href="./boom.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KyZXEAg3QhqLMpG8r+8fhAXLRk2vvoC2f3B09zVXn8CA5QIVfZOJ3BCsw2P0p/We" crossorigin="anonymous">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sawarabi+Gothic&display=swap" rel="stylesheet">
    <style>
        body{
            background-color:honeydew  !important;
        }
        .container {
            font-family: 'Sawarabi Gothic', sans-serif;
        }
        a {
            text-decoration: none;
        }
        th {
            height: 30px;
            text-align: center;
        }
        td {
            height: 100px;
        }
        .youbi{
            background:lightgray !important;
        }
        .day{
            background:snow !important;
        }
        .today {
            background: orange !important;
        }
        th:nth-of-type(1), td:nth-of-type(1) {
            color: red;
        }
        th:nth-of-type(7), td:nth-of-type(7) {
            color: blue;
        }
    </style>
</head>

<body>
<?php
//＊＊＊＊＊＊＊＊カレンダーデータ＊＊＊＊＊＊＊＊

// タイムゾーンを東京に設定
date_default_timezone_set('Asia/Tokyo');

// 前月・次月リンクが押された場合は、GETパラメーターから年月を取得
if (isset($_GET['y'])&&isset($_GET['m'])) {
    $y=$_GET['y'];
    $m=$_GET['m'];

    //前4ケタがY、後ろ2ケタをmとする
    //$temp= str_split($_GET['ym']);

    //$y=$temp[0].$temp[1].$temp[2].$temp[3];
    //$m=$temp[5].$temp[6];

    //$ym = $_GET['ym'];
} else {
    // 今月の年月を表示
    $y = date('Y');
    $m = date('m');
}

// タイムスタンプを作成し、フォーマットをチェックする
$timestamp = strtotime($y.'-'.$m . '-01');
if ($timestamp === false) {
    $ym = date('Y-m');
    $timestamp = strtotime($ym . '-01');
}

// 今日の日付 フォーマット　例）2021-06-3
$today = date('Ymd');

// カレンダーのタイトルを作成　例）2021年6月
$html_title = date('Y年n月', $timestamp);
if(201305<=$y.$m&&$y.$m<=date('Ym')){
    //2013年5月〜当月の範囲である場合カレンダーのタイトルからリンクを作成
    $html_title = '<u><a href="./boom.php'.'?type=m&day='.$y.$m.'01'.'">'.$html_title.'</a></u>';
}

// 前月・次月の年月を取得
$prev_y = date('Y', mktime(0, 0, 0, date('m', $timestamp)-1, 1, date('Y', $timestamp)));
$prev_m = date('m', mktime(0, 0, 0, date('m', $timestamp)-1, 1, date('Y', $timestamp)));
$next_y = date('Y', mktime(0, 0, 0, date('m', $timestamp)+1, 1, date('Y', $timestamp)));
$next_m = date('m', mktime(0, 0, 0, date('m', $timestamp)+1, 1, date('Y', $timestamp)));

// 該当月の日数を取得
$day_count = date('t', $timestamp);

// １日が何曜日か　0:日〜6:土
$youbi = date('w', mktime(0, 0, 0, date('m', $timestamp), 1, date('Y', $timestamp)));


// カレンダー作成の準備
$weeks = [];
$week = '';

// 第１週目の空のセルを追加
$week .= str_repeat('<td></td>', $youbi);

for ( $day = 1; $day <= $day_count; $day++, $youbi++) {

    // YYYY-MM-DD
    //表記用のために1~9日の場合は0埋めする
    $day_zero = $day;//0埋めしたやつ
    if($day<10){
        $day_zero = '0'.$day;
    }
    $date = $y.$m.$day_zero;

    if ($today == $date) {
        // 今日の日付の場合は、class="today"をつける
        $week .= '<td class="today">';
    } else {
        $week .= '<td class="day">';
    }
    if($y.$m.$day_zero>date('Ymd')||$y.$m<=201304){
        //当日以降or2013年4月以前の場合リンクのついてない日付を作成
        $week .= $day_zero.'</td>';
    }else{
        //リンクを作成
        $week .='<u><a href="./boom.php'.'?type=d&day='.$y.$m.$day_zero.'">'.$day_zero.'</a></u>';
        if($youbi % 7 == 2){
            //火曜日の場合週間へのリンクを作成
            $week .='</br>
                    <u><a href="./boom.php'.'?type=w&day='.$y.$m.$day_zero.'">週間</a></u>';
        }
        $week .= '</td>'; 
    }

    // 週終わり、または、月終わりの場合
    if ($youbi % 7 == 6 || $day == $day_count) {

        if ($day == $day_count) {
            // 月の最終日の場合、空セルを追加
            $week .= str_repeat('<td></td>', 6 - $youbi % 7);
        }

        // weeks配列にtrと$weekを追加する
        $weeks[] = '<tr>' . $week . '</tr>';

        // weekをリセット
        $week = '';
    }
}
?>
<div class= "main">

    <h1>なろう流行チェッカー</h1>
    </br>
    <p>「小説家になろう」の任意の日付のランキングを取得し、</br>
        タイトル・あらすじ・キーワードから頻出ワードをカウント・図示します。</br>
    </p>
    </br>

    <div class="container">
        <h3 class="mb-5">
            <?php
                //年月と矢印表示
                //2013年5月以前の場合戻る矢印を表示しない
                if($y.$m>201305){
                    print '<a href="?y='.$prev_y.'&m='.$prev_m.'">&lt;-  </a>';
                }
                print $html_title;
                //当月以降の場合進む矢印を表示しない
                if($y.$m<date('Ym')){
                    print '<a href="?y='. $next_y.'&m='.$next_m.'">  -&gt;</a>';
                }
                //ジャンプ用のプルダウンメニュー
                print '<form method="get" action="top.php">';
                print '<select name="y">
                        <option value="'.date('Y').'">年</option>';
                for($x=2013;$x<=date('Y');$x++){
                    print '<option value="'.$x.'">'.$x.'</option>';
                }
                print '</select>';
                print '<select name="m">
                        <option value="'.date('m').'">月</option>';
                print '<option value="01">01</option>
                        <option value="02">02</option>
                        <option value="03">03</option>
                        <option value="04">04</option>
                        <option value="05">05</option>
                        <option value="06">06</option>
                        <option value="07">07</option>
                        <option value="08">08</option>
                        <option value="09">09</option>
                        <option value="10">10</option>
                        <option value="11">11</option>
                        <option value="12">12</option>';
                print '</select>';
                print '<input type="submit" value="移動">';
                print '</form>';
            ?>
        </h3>
        <table class="table table-bordered">
            <tr>
                <th class="youbi">日</th>
                <th class="youbi">月</th>
                <th class="youbi">火</th>
                <th class="youbi">水</th>
                <th class="youbi">木</th>
                <th class="youbi">金</th>
                <th class="youbi">土</th>
            </tr>
            <?php
                foreach ($weeks as $week) {
                    echo $week;
                }
            ?>
        </table>
        <h4><u>
        <?php
            if(201305<=$y.$m&&$y.$m<=date('Ym')){
                //2013年5月〜当月の範囲である場合四半期へのリンクを作成
                print '<u><a href="./boom.php'.'?type=q&day='.$y.$m.'01'.'">四半期</a></u>';
            }else{
                print '<s>四半期</s>';
            }
        ?>
        </u></h4></br>
    </div>

    年月の部分をクリック：その月の月間ランキングをチェック</br>
    週間をクリック：その週（火曜集計）の週間ランキングをチェック</br>
    日付をクリック：その日付の日間ランキングをチェック</br>
    四半期をクリック：その月に記録された四半期ランキングをチェック</br></br>
    <small>*なろうAPIによるランキングの提供は2013年5月〜当日までの為、それ以外の範囲を指定した場合リンクは生成されません。</small></br>
    </br>
    </br>
</div>

</body>