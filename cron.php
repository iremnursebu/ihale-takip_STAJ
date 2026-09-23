<?php
require_once __DIR__ . '/db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/lib/PHPMailer/Exception.php';
require_once __DIR__ . '/lib/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/lib/PHPMailer/SMTP.php';

$db = get_db();

$today = date('Y-m-d');
$is_manual = isset($_GET['manual']) && $_GET['manual'] == 1;

/*
|---------------------------------------------------------
| Önümüzdeki 3 gün içinde bitecek ihaleleri al
|---------------------------------------------------------
*/

$stmt = $db->prepare("
SELECT *
FROM tenders
WHERE end_date BETWEEN ? AND date(?, '+3 day')
ORDER BY end_date ASC
");

$stmt->execute([$today, $today]);

$tenders = $stmt->fetchAll(PDO::FETCH_ASSOC);

if(empty($tenders)){

    if($is_manual){
        header("Location:index.php?alert_check=no_tenders");
        exit;
    }

    exit("Gönderilecek ihale bulunamadı.");
}

/*
|---------------------------------------------------------
| Kullanıcıları al
|---------------------------------------------------------
*/

$userStmt = $db->query("SELECT email FROM users WHERE email IS NOT NULL AND email<>''");
$users = $userStmt->fetchAll(PDO::FETCH_ASSOC);

if(empty($users)){
    exit("Kullanıcı bulunamadı.");
}

/*
|---------------------------------------------------------
| Mail tablosunu oluştur
|---------------------------------------------------------
*/

$rows="";

foreach($tenders as $tender){

    $days=floor(
        (strtotime($tender["end_date"])-strtotime($today))/86400
    );

    if($days==0){

        $status="Bugün Bitiyor";

    }elseif($days==1){

        $status="1 Gün Kaldı";

    }else{

        $status=$days." Gün Kaldı";

    }

    $rows.='

<tr>

<td>'.htmlspecialchars($tender["title"]).'</td>

<td>'.htmlspecialchars($tender["institution"]).'</td>

<td>'.date("d.m.Y",strtotime($tender["publish_date"])).'</td>

<td>'.date("d.m.Y",strtotime($tender["end_date"])).'</td>

<td>'.$status.'</td>

</tr>

';

}

$mail=new PHPMailer(true);

try{

$mail->isSMTP();

$mail->Host=SMTP_HOST;

$mail->SMTPAuth=true;

$mail->Username=SMTP_USER;

$mail->Password=SMTP_PASS;

$mail->Port=SMTP_PORT;

$mail->CharSet="UTF-8";

$mail->SMTPSecure=SMTP_SECURE;

$mail->setFrom(SMTP_USER,SMTP_FROM_NAME);

foreach($users as $user){

$mail->addAddress($user["email"]);

}

$mail->isHTML(true);

$mail->Subject="İhale Takip Sistemi - Önümüzdeki 3 Gün İçinde Bitecek İhaleler";

$mail->Body='
<!doctype html>

<html>

<head>

<meta charset="utf-8">

<style>

body{

font-family:Arial;

background:#f4f4f4;

padding:30px;

}

.card{

background:white;

max-width:850px;

margin:auto;

border-radius:12px;

overflow:hidden;

box-shadow:0 10px 25px rgba(0,0,0,.08);

}

.header{

background:#4f46e5;

color:white;

padding:25px;

text-align:center;

}

.content{

padding:25px;

}

table{

width:100%;

border-collapse:collapse;

}

th{

background:#4f46e5;

color:white;

padding:12px;

}

td{

padding:12px;

border:1px solid #ddd;

text-align:center;

}

tr:nth-child(even){

background:#fafafa;

}
</style>

</head>

<body>

<div class="card">

<div class="header">

<h2>📢 Önümüzdeki 3 Gün İçinde Bitecek İhaleler</h2>

</div>

<div class="content">

<p>Merhaba,</p>

<p>
Aşağıda <strong>önümüzdeki 3 gün içerisinde</strong> bitiş tarihi gelecek ihaleler listelenmiştir.
Lütfen gerekli işlemleri zamanında gerçekleştiriniz.
</p>

<table>

<thead>

<tr>

<th>İhale Adı</th>

<th>Kurum</th>

<th>Yayın Tarihi</th>

<th>Bitiş Tarihi</th>

<th>Durum</th>

</tr>

</thead>

<tbody>

'.$rows.'

</tbody>

</table>

<br>

<p style="color:#666;font-size:14px;">

Bu e-posta İhale Takip Sistemi tarafından otomatik olarak gönderilmiştir.

</p>

</div>

</div>

</body>

</html>

';

$mail->send();

if($is_manual){

header("Location:index.php?alert_check=success");

exit;

}

echo "[".date("Y-m-d H:i:s")."] Bildirim gönderildi.";

}catch(Exception $e){

if($is_manual){

header("Location:index.php?alert_check=error");

exit;

}

echo $mail->ErrorInfo;

}