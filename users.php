<?php
require_once __DIR__ . '/db.php';
check_auth();

$db = get_db();
$error = '';
$success = '';

// Handle Add User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Lütfen tüm alanları doldurun.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Geçersiz e-posta adresi.';
    } elseif (strlen($password) < 6) {
        $error = 'Şifre en az 6 karakter olmalıdır.';
    } else {
        try {
            // Check if username already exists
            $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetchColumn() > 0) {
                $error = 'Bu kullanıcı adı zaten alınmış.';
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO users (username, password_hash, email) VALUES (?, ?, ?)");
                $stmt->execute([$username, $password_hash, $email]);
                $success = 'Yeni kullanıcı başarıyla eklendi.';
            }
        } catch (PDOException $e) {
            $error = 'Hata oluştu: ' . $e->getMessage();
        }
    }
}

// Handle Delete User
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // Prevent self-deletion
    if ($id === (int)$_SESSION['user_id']) {
        $error = 'Kendi hesabınızı silemezsiniz!';
    } else {
        try {
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $success = 'Kullanıcı başarıyla silindi.';
        } catch (PDOException $e) {
            $error = 'Silme işlemi başarısız: ' . $e->getMessage();
        }
    }
}

// Fetch all users
$users = $db->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İhale Takip - Kullanıcı Yönetimi</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="app-layout">
        <!-- Sidebar Navigation -->
        <div class="sidebar">
            <div class="sidebar-brand">
                <div class="brand-icon">İT</div>
                <div class="brand-name">İhale Takip</div>
            </div>
            <ul class="sidebar-menu">
                <li class="sidebar-item">
                    <a href="index.php">
                        <span class="icon">📊</span> Panel
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="tenders.php">
                        <span class="icon">📄</span> İhaleler
                    </a>
                </li>
                <li class="sidebar-item active">
                    <a href="users.php">
                        <span class="icon">👤</span> Kullanıcılar
                    </a>
                </li>
            </ul>
            <div class="sidebar-footer">
                <div class="user-profile">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                    </div>
                    <div class="user-info">
                        <span class="user-name"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                        <span class="user-email"><?php echo htmlspecialchars($_SESSION['email']); ?></span>
                    </div>
                </div>
                <a href="logout.php" class="btn btn-secondary btn-sm btn-block">
                    <span>🚪</span> Çıkış Yap
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="page-header">
                <div class="page-title">
                    <h2>Kullanıcı Yönetimi</h2>
                    <p>Sisteme erişimi olan yetkili kullanıcıları yönetin.</p>
                </div>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <!-- Split Grid: Table on left, form on right -->
            <div class="dashboard-grid">
                <!-- Users list -->
                <div class="card-block">
                    <div class="card-title">Yetkili Kullanıcı Listesi</div>
                    <div class="table-responsive">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>Kullanıcı Adı</th>
                                    <th>E-posta</th>
                                    <th>Kayıt Tarihi</th>
                                    <th style="text-align: right;">İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $u): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($u['username']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                                        <td><?php echo date('d.m.Y H:i', strtotime($u['created_at'])); ?></td>
                                        <td style="text-align: right;">
                                            <?php if ($u['id'] === $_SESSION['user_id']): ?>
                                                <span class="badge badge-active" style="text-transform:none;">Siz</span>
                                            <?php else: ?>
                                                <a href="users.php?action=delete&id=<?php echo $u['id']; ?>" 
                                                   class="btn btn-danger btn-sm"
                                                   onclick="return confirm('Bu kullanıcıyı silmek istediğinizden emin misiniz?');">
                                                    Sil
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Add User Form -->
                <div class="card-block">
                    <div class="card-title">Yeni Kullanıcı Ekle</div>
                    <form action="users.php" method="POST">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="form-group">
                            <label class="form-label" for="username">Kullanıcı Adı</label>
                            <input type="text" id="username" name="username" class="form-control" placeholder="Kullanıcı adı" required autocomplete="off">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="email">E-posta Adresi</label>
                            <input type="email" id="email" name="email" class="form-control" placeholder="E-posta" required autocomplete="off">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="password">Şifre</label>
                            <input type="password" id="password" name="password" class="form-control" placeholder="Min. 6 karakter" required>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block" style="margin-top: 10px;">Kullanıcıyı Kaydet</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
