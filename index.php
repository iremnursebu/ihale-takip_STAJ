<?php
require_once __DIR__ . '/db.php';
check_auth();

$db = get_db();
$today = date('Y-m-d');
$next_week = date('Y-m-d', strtotime('+7 days'));
$last_week = date('Y-m-d', strtotime('-7 days'));

// 1. Stats Queries
$total_tenders = $db->query("SELECT COUNT(*) FROM tenders")->fetchColumn();

$stmt_active = $db->prepare("SELECT COUNT(*) FROM tenders WHERE end_date >= ?");
$stmt_active->execute([$today]);
$active_tenders = $stmt_active->fetchColumn();

$stmt_expired = $db->prepare("SELECT COUNT(*) FROM tenders WHERE end_date < ?");
$stmt_expired->execute([$today]);
$expired_tenders = $stmt_expired->fetchColumn();

$stmt_ending_soon = $db->prepare("SELECT COUNT(*) FROM tenders WHERE end_date >= ? AND end_date <= ?");
$stmt_ending_soon->execute([$today, $next_week]);
$ending_soon_tenders = $stmt_ending_soon->fetchColumn();

$stmt_ended_last_week = $db->prepare("SELECT COUNT(*) FROM tenders WHERE end_date >= ? AND end_date < ?");
$stmt_ended_last_week->execute([$last_week, $today]);
$ended_last_week_tenders = $stmt_ended_last_week->fetchColumn();

// 2. Fetch Tenders Ending in the Next 7 Days
$stmt_list_soon = $db->prepare("SELECT * FROM tenders WHERE end_date >= ? AND end_date <= ? ORDER BY end_date ASC LIMIT 5");
$stmt_list_soon->execute([$today, $next_week]);
$ending_soon_list = $stmt_list_soon->fetchAll();

// Handle Manual Email Check Alert Feedback
$alert_msg = '';
$alert_type = '';
if (isset($_GET['alert_check'])) {
    if ($_GET['alert_check'] === 'success') {
        $alert_msg = 'E-posta bildirim kontrolü başarıyla çalıştırıldı. Süresi gelen ihaleler için bildirimler gönderildi.';
        $alert_type = 'success';
    } elseif ($_GET['alert_check'] === 'error') {
        $alert_msg = 'E-posta gönderimi sırasında bir hata oluştu. Lütfen config.php ayarlarını kontrol edin.';
        $alert_type = 'danger';
    } elseif ($_GET['alert_check'] === 'no_tenders') {
        $alert_msg = 'Gönderilecek yeni e-posta bildirimi bulunmuyor (Tüm süresi geçen ihaleler zaten bildirildi).';
        $alert_type = 'success';
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İhale Takip - Kontrol Paneli</title>
    <link rel="stylesheet" href="css/style.css">
    <!-- Chart.js for premium statistics display -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                <li class="sidebar-item active">
                    <a href="index.php">
                        <span class="icon">📊</span> Panel
                    </a>
                </li>
                <li class="sidebar-item">
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
                    <h2>Kontrol Paneli</h2>
                    <p>Sistem genel durumunu ve yaklaşan ihaleleri takip edin.</p>
                </div>
                <div>
                    <a href="cron.php?manual=1" class="btn btn-primary">
                        <span>✉️</span> E-posta Kontrolü Çalıştır
                    </a>
                </div>
            </div>

            <?php if (!empty($alert_msg)): ?>
                <div class="alert alert-<?php echo $alert_type; ?>">
                    <?php echo htmlspecialchars($alert_msg); ?>
                </div>
            <?php endif; ?>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stats-card">
                    <div class="stats-info">
                        <h3>Toplam İhale</h3>
                        <div class="stats-value"><?php echo $total_tenders; ?></div>
                    </div>
                    <div class="stats-icon primary">📁</div>
                </div>
                <div class="stats-card">
                    <div class="stats-info">
                        <h3>Aktif İhaleler</h3>
                        <div class="stats-value"><?php echo $active_tenders; ?></div>
                    </div>
                    <div class="stats-icon success">🟢</div>
                </div>
                <div class="stats-card">
                    <div class="stats-info">
                        <h3>Yaklaşan (1 Hafta)</h3>
                        <div class="stats-value"><?php echo $ending_soon_tenders; ?></div>
                    </div>
                    <div class="stats-icon warning">⏳</div>
                </div>
                <div class="stats-card">
                    <div class="stats-info">
                        <h3>Geçmiş (1 Hafta)</h3>
                        <div class="stats-value"><?php echo $ended_last_week_tenders; ?></div>
                    </div>
                    <div class="stats-icon danger">🔴</div>
                </div>
            </div>

            <!-- Dashboard Grid -->
            <div class="dashboard-grid">
                <!-- Left Column: Ending Soon List -->
                <div class="card-block">
                    <div class="card-title">
                        <span>⏳ Son 1 Hafta İçinde Bitecek İhaleler</span>
                        <a href="tenders.php" class="btn btn-secondary btn-sm">Tümünü Gör</a>
                    </div>
                    
                    <?php if (empty($ending_soon_list)): ?>
                        <div style="text-align: center; padding: 40px 0; color: var(--text-secondary);">
                            <span style="font-size: 32px; display: block; margin-bottom: 12px;">🎉</span>
                            Önümüzdeki 1 hafta içinde bitiş tarihi yaklaşan bir ihale bulunmuyor.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th>İhale Adı</th>
                                        <th>İlgili Kurum</th>
                                        <th>Bitiş Tarihi</th>
                                        <th>Durum</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ending_soon_list as $tender): ?>
                                        <?php 
                                            $days_left = (strtotime($tender['end_date']) - strtotime($today)) / 86400;
                                            $badge_class = 'badge-warning';
                                            $badge_text = 'Son ' . round($days_left) . ' Gün';
                                            if ($days_left == 0) {
                                                $badge_text = 'Bugün Bitiyor';
                                            }
                                        ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($tender['title']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($tender['institution']); ?></td>
                                            <td><?php echo date('d.m.Y', strtotime($tender['end_date'])); ?></td>
                                            <td>
                                                <span class="badge <?php echo $badge_class; ?>">
                                                    <?php echo $badge_text; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Right Column: Graphs and Quick Statistics -->
                <div style="display: flex; flex-direction: column; gap: 24px;">
                    <div class="card-block">
                        <div class="card-title">İhale Dağılımı</div>
                        <div style="max-width: 220px; margin: 0 auto 20px auto; height: 220px;">
                            <canvas id="tenderChart"></canvas>
                        </div>
                        <div style="display:flex; flex-direction:column; gap:8px;">
                            <div class="quick-stat-item">
                                <span class="quick-stat-label">Aktif İhaleler</span>
                                <span class="quick-stat-count" style="color: var(--success);"><?php echo $active_tenders; ?></span>
                            </div>
                            <div class="quick-stat-item">
                                <span class="quick-stat-label">Süresi Geçenler</span>
                                <span class="quick-stat-count" style="color: var(--danger);"><?php echo $expired_tenders; ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="card-block">
                        <div class="card-title">Hızlı Rapor</div>
                        <div class="quick-stat-item">
                            <span class="quick-stat-label">Yaklaşan (1 Hafta)</span>
                            <span class="quick-stat-count"><?php echo $ending_soon_tenders; ?></span>
                        </div>
                        <div class="quick-stat-item">
                            <span class="quick-stat-label">Geçen Hafta Bitenler</span>
                            <span class="quick-stat-count"><?php echo $ended_last_week_tenders; ?></span>
                        </div>
                        <div class="quick-stat-item">
                            <span class="quick-stat-label">Toplam İhale Girişi</span>
                            <span class="quick-stat-count"><?php echo $total_tenders; ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart rendering script -->
    <script>
        const ctx = document.getElementById('tenderChart').getContext('2d');
        const activeCount = <?php echo (int)$active_tenders; ?>;
        const expiredCount = <?php echo (int)$expired_tenders; ?>;
        
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Aktif', 'Süresi Geçen'],
                datasets: [{
                    data: [activeCount, expiredCount],
                    backgroundColor: ['#10b981', '#ef4444'],
                    borderColor: 'rgba(255, 255, 255, 0.08)',
                    borderWidth: 2,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                cutout: '70%'
            }
        });
    </script>
</body>
</html>
