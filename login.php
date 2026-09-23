<?php
require_once __DIR__ . '/db.php';

// Check if there are any users. If not, redirect to setup
$db = get_db();
$user_count = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
if ($user_count == 0) {
    header('Location: setup.php');
    exit;
}

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Lütfen kullanıcı adı ve şifrenizi girin.';
    } else {
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Login successful
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            
            header('Location: index.php');
            exit;
        } else {
            $error = 'Geçersiz kullanıcı adı veya şifre.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İhale Takip - Giriş Yap</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div style="display:flex; justify-content:center; margin-bottom: 15px;">
                    <div class="brand-icon" style="width: 50px; height: 50px; font-size: 20px;">İT</div>
                </div>
                <h1>İhale Takip Sistemi</h1>
                <p>Devam etmek için lütfen giriş yapın.</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <div class="form-group">
                    <label class="form-label" for="username">Kullanıcı Adı</label>
                    <input type="text" id="username" name="username" class="form-control" placeholder="Kullanıcı adınızı girin" required autocomplete="off">
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Şifre</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Şifrenizi girin" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block" style="margin-top: 10px;">Giriş Yap</button>
            </form>
        </div>
    </div>
</body>
</html>
