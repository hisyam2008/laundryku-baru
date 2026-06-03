<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'auth.php';
require_once 'config/Database.php';
require_once 'app/models/OutletModel.php';
require_once 'app/models/MemberModel.php';
require_once 'app/models/UserModel.php';

$db     = (new Database())->connect();
$outlet = new OutletModel($db);
$member = new MemberModel($db);
$user   = new UserModel($db);

$id_outlet = $_SESSION['id_outlet'];
$role      = $_SESSION['role'];

// Custom count for dashboard
if ($role === 'admin') {
    $outlet_count = $db->query("SELECT COUNT(*) FROM tb_outlet")->fetchColumn();
    $member_count = $member->count();
    $user_count   = $db->query("SELECT COUNT(*) FROM tb_user")->fetchColumn();
} else {
    $outlet_count = 1; // Only their own outlet
    $member_count = $member->count($id_outlet);
    $user_count   = $db->prepare("SELECT COUNT(*) FROM tb_user WHERE id_outlet = ?");
    $user_count->execute([$id_outlet]);
    $user_count = $user_count->fetchColumn();
}

$page_title = 'Dashboard — Laundry Hisam';
require_once 'layout/sidebar.php';
?>

<div class="main-content">
    <div class="topbar">
        <button class="btn-toggle" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
        <h6>Dashboard</h6>
        <span class="ms-auto text-white-50" style="font-size:.8rem;"><?= date('d/m/Y') ?></span>
    </div>

    <?php if (isset($_GET['err']) && $_GET['err'] === 'akses'): ?>
        <div class="alert alert-danger alert-dismissible fade show py-2">
            <i class="bi bi-shield-exclamation me-1"></i>Akses ditolak. Anda tidak memiliki izin.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($is_admin || $is_owner): ?>
    <!-- Dashboard Admin/Owner -->
    <div class="row g-4 mb-4">
        <div class="col-sm-4">
            <div class="card stat-card shadow-sm border-0 h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-white-50 small fw-medium mb-1">DATA OUTLET</div>
                            <div class="display-6 fw-bold text-white"><?= $outlet_count ?></div>
                        </div>
                    </div>
                    <?php if(isset($outlet_error)) echo "<small class='text-danger'>Error: $outlet_error</small>"; ?>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card stat-card shadow-sm border-0 h-100" style="--primary-color: #3b82f6;">
                <div class="card-body p-4 border-start border-primary border-4 rounded-start">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-white-50 small fw-medium mb-1">TOTAL PELANGGAN</div>
                            <div class="display-6 fw-bold text-white"><?= $member_count ?></div>
                        </div>
                    </div>
                    <?php if(isset($member_error)) echo "<small class='text-danger'>Error: $member_error</small>"; ?>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card stat-card shadow-sm border-0 h-100" style="--primary-color: #f59e0b;">
                <div class="card-body p-4 border-start border-warning border-4 rounded-start">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-white-50 small fw-medium mb-1">PENGGUNA AKTIF</div>
                            <div class="display-6 fw-bold text-white"><?= $user_count ?></div>
                        </div>
                    </div>
                    <?php if(isset($user_error)) echo "<small class='text-danger'>Error: $user_error</small>"; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header-custom">
                    <h5><i class="bi bi-shop me-2"></i>Outlet Terbaru</h5>
                    <a href="outlet.php" class="btn btn-hijau btn-sm">Lihat Semua</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>#</th><th>Nama</th><th>Alamat</th></tr></thead>
                        <tbody>
                            <?php 
                            $outlets = [];
                            try { $outlets = $outlet->latest(5); } catch(Exception $e) { echo "<tr><td colspan='3' class='text-danger p-3'>Error: " . $e->getMessage() . "</td></tr>"; }
                            ?>
                            <?php if (empty($outlets)): ?>
                                <tr><td colspan="3" class="text-center text-muted py-3">Belum ada outlet.</td></tr>
                            <?php else: foreach ($outlets as $i => $o): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><?= htmlspecialchars($o['nama']) ?></td>
                                    <td class="text-muted"><?= htmlspecialchars($o['alamat']) ?></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header-custom">
                    <h5><i class="bi bi-people me-2"></i>Member Terbaru</h5>
                    <a href="member.php" class="btn btn-hijau btn-sm">Lihat Semua</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>#</th><th>Nama</th><th>Alamat</th><th>Gender</th><th>Telepon</th></tr></thead>
                        <tbody>
                            <?php 
                            $members = [];
                            try { $members = $member->latest(5); } catch(Exception $e) { echo "<tr><td colspan='5' class='text-danger p-3'>Error: " . $e->getMessage() . "</td></tr>"; }
                            ?>
                            <?php if (empty($members)): ?>
                                <tr><td colspan="5" class="text-center text-muted py-3">Belum ada member.</td></tr>
                            <?php else: foreach ($members as $i => $m): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><?= htmlspecialchars($m['nama']) ?></td>
                                    <td class="text-white-50"><?= htmlspecialchars($m['alamat']) ?></td>
                                    <td class="text-white-50"><?= htmlspecialchars($m['jenis_kelamin']) ?></td>
                                    <td class="text-white-50"><?= htmlspecialchars($m['tlp']) ?></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php else: ?>
    <!-- Dashboard Kasir -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6">
            <div class="card p-3" style="border-left:4px solid #3b82f6;">
                <div class="d-flex align-items-center gap-3">
                    <div>
                        <div class="fw-bold fs-4 lh-1 text-white"><?= $member_count ?></div>
                        <div class="text-white-50" style="font-size:.8rem;">Total Member</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-header-custom">
            <h5>Member Terbaru</h5>
            <a href="member.php" class="btn btn-hijau btn-sm">Lihat Semua</a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>#</th><th>Nama</th><th>Alamat</th><th>Gender</th><th>Telepon</th></tr></thead>
                <tbody>
                    <?php $members = $member->latest(5, $id_outlet); ?>
                    <?php if (empty($members)): ?>
                        <tr><td colspan="5" class="text-white-50 p-3">Belum ada member.</td></tr>
                    <?php else: foreach ($members as $i => $m): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= htmlspecialchars($m['nama']) ?></td>
                            <td class="text-white-50"><?= htmlspecialchars($m['alamat']) ?></td>
                            <td class="text-white-50"><?= htmlspecialchars($m['jenis_kelamin']) ?></td>
                            <td class="text-white-50"><?= htmlspecialchars($m['tlp']) ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'layout/footer.php'; ?>
