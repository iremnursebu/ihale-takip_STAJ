<?php
require_once __DIR__ . '/db.php';
check_auth();

$db = get_db();
$today = date('Y-m-d');
$error = '';
$success = '';

// Handle Add Tender
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $title = trim($_POST['title'] ?? '');
    $institution = trim($_POST['institution'] ?? '');
    $publish_date = $_POST['publish_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';

    if (empty($title) || empty($institution) || empty($publish_date) || empty($end_date)) {
        $error = 'Lütfen tüm alanları doldurun.';
    } elseif ($publish_date > $end_date) {
        $error = 'Yayımlanma tarihi bitiş tarihinden sonra olamaz.';
    } else {
        try {
            $stmt = $db->prepare("INSERT INTO tenders (title, institution, publish_date, end_date, email_sent) VALUES (?, ?, ?, ?, 0)");
            $stmt->execute([$title, $institution, $publish_date, $end_date]);
            $success = 'İhale başarıyla eklendi.';
        } catch (PDOException $e) {
            $error = 'Hata oluştu: ' . $e->getMessage();
        }
    }
}

// Handle Delete Tender
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $stmt = $db->prepare("DELETE FROM tenders WHERE id = ?");
        $stmt->execute([$id]);
        $success = 'İhale başarıyla silindi.';
    } catch (PDOException $e) {
        $error = 'Silme işlemi başarısız: ' . $e->getMessage();
    }
}

// Filters (All, Active, Expired)
$filter = $_GET['filter'] ?? 'all';
if ($filter === 'active') {
    $stmt = $db->prepare("SELECT * FROM tenders WHERE end_date >= ? ORDER BY end_date ASC");
    $stmt->execute([$today]);
} elseif ($filter === 'expired') {
    $stmt = $db->prepare("SELECT * FROM tenders WHERE end_date < ? ORDER BY end_date DESC");
    $stmt->execute([$today]);
} else {
    $stmt = $db->prepare("SELECT * FROM tenders ORDER BY end_date ASC");
    $stmt->execute();
}
$tenders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İhale Takip - İhale Yönetimi</title>
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
                <li class="sidebar-item active">
                    <a href="tenders.php">
                        <span class="icon">📄</span> İhaleler
                    </a>
                </li>
                <li class="sidebar-item">
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
                    <h2>İhale Yönetimi</h2>
                    <p>Yeni ihaleler ekleyin, mevcutları inceleyin veya silin.</p>
                </div>
                <div>
                    <button class="btn btn-primary" onclick="openModal()">
                        <span>➕</span> Yeni İhale Ekle
                    </button>
                </div>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <!-- Filters Bar -->
            <div style="display:flex; justify-content: space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:16px;">
                <div style="display:flex; gap:10px;">
                    <a href="tenders.php?filter=all" class="btn btn-secondary btn-sm <?php echo $filter === 'all' ? 'btn-primary' : ''; ?>">Tüm İhaleler</a>
                    <a href="tenders.php?filter=active" class="btn btn-secondary btn-sm <?php echo $filter === 'active' ? 'btn-primary' : ''; ?>">Aktif İhaleler</a>
                    <a href="tenders.php?filter=expired" class="btn btn-secondary btn-sm <?php echo $filter === 'expired' ? 'btn-primary' : ''; ?>">Süresi Geçenler</a>
                </div>
                <div style="color: var(--text-secondary); font-size:14px;">
                    Toplam <strong><?php echo count($tenders); ?></strong> ihale listelendi.
                </div>
            </div>

            <!-- Tenders List -->
            <div class="card-block">
                <?php if (empty($tenders)): ?>
                    <div style="text-align: center; padding: 60px 0; color: var(--text-secondary);">
                        <span style="font-size: 40px; display: block; margin-bottom: 16px;">📁</span>
                        Kayıtlı ihale bulunamadı.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>İhale Adı</th>
                                    <th>İlgili Kurum</th>
                                    <th>Yayımlanma Tarihi</th>
                                    <th>Bitiş Tarihi</th>
                                    <th>Durum</th>
                                    <th style="text-align: right;">İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tenders as $tender): ?>
                                    <?php 
                                        $end_time = strtotime($tender['end_date']);
                                        $today_time = strtotime($today);
                                        $days_left = ($end_time - $today_time) / 86400;
                                        
                                        if ($days_left < 0) {
                                            $badge_class = 'badge-expired';
                                            $badge_text = 'Süresi Geçti';
                                      } elseif ($days_left <= 7) {
    $badge_class = 'badge-warning';

    if (round($days_left) == 0) {
        $badge_text = 'Bugün Bitiyor';
    } else {
        $badge_text = round($days_left) . ' Gün Kaldı';
    }
} else {
                                            $badge_class = 'badge-active';
                                            $badge_text = 'Aktif';
                                        }
                                    ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($tender['title']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($tender['institution']); ?></td>
                                        <td><?php echo date('d.m.Y', strtotime($tender['publish_date'])); ?></td>
                                        <td><?php echo date('d.m.Y', strtotime($tender['end_date'])); ?></td>
                                        <td>
                                            <span class="badge <?php echo $badge_class; ?>">
                                                <?php echo $badge_text; ?>
                                            </span>
                                        </td>
                                        <td style="text-align: right; display:flex; gap:8px; justify-content:flex-end;">

    <a href="edit_tender.php?id=<?php echo $tender['id']; ?>"
       class="btn btn-primary btn-sm">
       Düzenle
    </a>

    <a href="tenders.php?action=delete&id=<?php echo $tender['id']; ?>&filter=<?php echo $filter; ?>"
       class="btn btn-danger btn-sm"
       onclick="return confirm('Bu ihaleyi silmek istediğinizden emin misiniz?');">
       Sil
    </a>

</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add Tender Modal -->
    <div class="modal-backdrop" id="addTenderModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Yeni İhale Ekle</h3>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <form action="tenders.php" method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label" for="title">İhale Adı</label>
                        <input type="text" id="title" name="title" class="form-control" placeholder="İhale başlığını girin" required autocomplete="off">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="institution">İlgili Kurum</label>
                        <input type="text" id="institution" name="institution" class="form-control" placeholder="Örn: X Bakanlığı, Y Belediyesi" required autocomplete="off">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="publish_date">Yayımlanma Tarihi</label>
                        <input type="date" id="publish_date" name="publish_date" class="form-control" required value="<?php echo $today; ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="end_date">Bitiş Tarihi</label>
                        <input type="date" id="end_date" name="end_date" class="form-control" required value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">İptal</button>
                    <button type="submit" class="btn btn-primary">Kaydet</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal script -->
    <script>
        function openModal() {
            document.getElementById('addTenderModal').classList.add('show');
        }

        function closeModal() {
            document.getElementById('addTenderModal').classList.remove('show');
        }

        // Close modal when clicking outside content
        window.onclick = function(event) {
            const modal = document.getElementById('addTenderModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>
