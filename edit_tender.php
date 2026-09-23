<?php
require_once __DIR__ . '/db.php';
check_auth();

$db = get_db();
$error = '';
$success = '';

if (!isset($_GET['id'])) {
    header("Location: tenders.php");
    exit;
}

$id = (int)$_GET['id'];

$stmt = $db->prepare("SELECT * FROM tenders WHERE id=?");
$stmt->execute([$id]);

$tender = $stmt->fetch();

if (!$tender) {
    die("İhale bulunamadı.");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $title = trim($_POST['title']);
    $institution = trim($_POST['institution']);
    $publish_date = $_POST['publish_date'];
    $end_date = $_POST['end_date'];

    if ($publish_date > $end_date) {
        $error = "Yayımlanma tarihi bitiş tarihinden sonra olamaz.";
    } else {

        $update = $db->prepare("
            UPDATE tenders
            SET
                title=?,
                institution=?,
                publish_date=?,
                end_date=?
            WHERE id=?
        ");

        $update->execute([
            $title,
            $institution,
            $publish_date,
            $end_date,
            $id
        ]);

        header("Location: tenders.php");
        exit;
    }

}
?>
<!DOCTYPE html>
<html lang="tr">

<head>

<meta charset="UTF-8">

<title>İhale Düzenle</title>

<link rel="stylesheet" href="css/style.css">

</head>

<body>

<div class="container" style="max-width:700px;margin:50px auto;">

<h2>İhale Düzenle</h2>

<?php if($error): ?>

<div class="alert alert-danger">

<?= $error ?>

</div>

<?php endif; ?>

<form method="POST">

<div class="form-group">

<label>İhale Adı</label>

<input
type="text"
name="title"
class="form-control"
value="<?= htmlspecialchars($tender['title']) ?>"
required>

</div>

<div class="form-group">

<label>İlgili Kurum</label>

<input
type="text"
name="institution"
class="form-control"
value="<?= htmlspecialchars($tender['institution']) ?>"
required>

</div>

<div class="form-group">

<label>Yayımlanma Tarihi</label>

<input
type="date"
name="publish_date"
class="form-control"
value="<?= $tender['publish_date'] ?>"
required>

</div>

<div class="form-group">

<label>Bitiş Tarihi</label>

<input
type="date"
name="end_date"
class="form-control"
value="<?= $tender['end_date'] ?>"
required>

</div>

<br>

<button class="btn btn-primary">

Kaydet

</button>

<a href="tenders.php" class="btn btn-secondary">

İptal

</a>

</form>

</div>

</body>

</html>