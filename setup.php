<?php
require_once __DIR__ . '/db.php';

$db = get_db();
$user_count = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();

// If admin user is already set up, redirect to login
if ($user_count > 0) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Lütfen tüm alanları doldurun.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Geçersiz e-posta adresi.';
    } elseif (strlen($password) < 6) {
        $error = 'Şifre en az 6 karakter olmalıdır.';
    } elseif ($password !== $password_confirm) {
        $error = 'Şifreler eşleşmiyor.';
    } else {
        try {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (username, password_hash, email) VALUES (?, ?, ?)");
            $stmt->execute([$username, $password_hash, $email]);
            $success = 'Yönetici hesabı başarıyla oluşturuldu. Giriş sayfasına yönlendiriliyorsunuz...';
            
            // Redirect after 2 seconds
            header("refresh:2;url=login.php");
        } catch (PDOException $e) {
            $error = 'Hata oluştu: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İhale Takip - Sistem Kurulumu</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div style="display:flex; justify-content:center; margin-bottom: 15px;">
                    <div class="brand-icon" style="width: 50px; height: 50px; font-size: 20px;">İT</div>
                </div>
                <h1>Sistem Kurulumu</h1>
                <p>Sistemi kullanmaya başlamak için ilk yönetici hesabını oluşturun.</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <?php if (empty($success)): ?>
                <form action="setup.php" method="POST">
                    <div class="form-group">
                        <label class="form-label" for="username">Kullanıcı Adı</label>
                        <input type="text" id="username" name="username" class="form-control" placeholder="Örn: admin" required autocomplete="off">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="email">E-posta Adresi</label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="Örn: admin@ihale.com" required autocomplete="off">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">Şifre</label>
                        <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password_confirm">Şifre Tekrar</label>
                        <input type="password" id="password_confirm" name="password_confirm" class="form-control" placeholder="••••••••" required>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Kurulumu Tamamla</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
