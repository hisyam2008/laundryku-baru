<?php
require_once 'auth.php';
require_once 'config/Database.php';

$is_owner = $_SESSION['role'] === 'owner';
if ($is_owner) { header('Location: index.php?err=akses'); exit; }
$db = (new Database())->connect();

if (isset($_GET['status']) && isset($_GET['id'])) {
    if ($is_owner) { header('Location: transaksi.php'); exit; }
    $allowed = ['baru','proses','selesai','diambil'];
    if (in_array($_GET['status'], $allowed)) {
        if ($_SESSION['role'] === 'admin') {
            $db->prepare("UPDATE tb_transaksi SET status=? WHERE id=?")->execute([$_GET['status'], $_GET['id']]);
        } else {
            $db->prepare("UPDATE tb_transaksi SET status=? WHERE id=? AND id_outlet=?")->execute([$_GET['status'], $_GET['id'], $_SESSION['id_outlet']]);
        }
    }
    header('Location: transaksi.php'); exit;
}

if (isset($_GET['hapus'])) {
    if ($is_owner) { header('Location: transaksi.php'); exit; }
    if ($_SESSION['role'] === 'admin') {
        $db->prepare("DELETE FROM tb_transaksi WHERE id=?")->execute([$_GET['hapus']]);
    } else {
        $db->prepare("DELETE FROM tb_transaksi WHERE id=? AND id_outlet=?")->execute([$_GET['hapus'], $_SESSION['id_outlet']]);
    }
    header('Location: transaksi.php'); exit;
}

$where  = "WHERE 1=1";
$params = [];

if ($_SESSION['role'] !== 'admin') {
    $where .= " AND t.id_outlet = ?";
    $params[] = $_SESSION['id_outlet'];
}

if (!empty($_GET['status_filter'])) {
    $where .= " AND t.status = ?";
    $params[] = $_GET['status_filter'];
}
if (!empty($_GET['cari'])) {
    $where .= " AND (m.nama LIKE ? OR t.kode_invoice LIKE ?)";
    $params[] = '%' . $_GET['cari'] . '%';
    $params[] = '%' . $_GET['cari'] . '%';
}

$stmt = $db->prepare("SELECT t.*, o.nama AS nama_outlet, m.nama AS nama_member
    FROM tb_transaksi t
    LEFT JOIN tb_outlet o ON o.id = t.id_outlet
    LEFT JOIN tb_member m ON m.id = t.id_member
    $where ORDER BY t.tgl DESC");
$stmt->execute($params);
$transaksis = $stmt->fetchAll();

$page_title = 'Transaksi — Laundry Hisam';
require_once 'layout/sidebar.php';
?>

<div class="main-content">
    <div class="topbar">
        <button class="btn-toggle" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
        <h6>Transaksi</h6>
        <?php if (!$is_owner): ?>
        <a href="tambah_transaksi.php" class="btn btn-hijau btn-sm ms-auto">Tambah Transaksi</a>
        <?php endif; ?>
    </div>



    <div class="card mb-3">
        <div class="card-body p-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <input type="text" name="cari" class="form-control form-control-sm" placeholder="Cari nama member / kode invoice..." value="<?= htmlspecialchars($_GET['cari'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <select name="status_filter" class="form-select form-select-sm">
                        <option value="">— Semua Status —</option>
                        <?php foreach (['baru','proses','selesai','diambil'] as $s): ?>
                            <option value="<?= $s ?>" <?= ($_GET['status_filter'] ?? '') === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <button class="btn btn-hijau btn-sm">Filter</button>
                    <a href="transaksi.php" class="btn btn-outline-secondary btn-sm ms-1">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header-custom">
            <h5>Daftar Transaksi</h5>
            <span class="badge bg-secondary"><?= count($transaksis) ?> transaksi</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr><th>#</th><th>Kode Invoice</th><th>Member</th><th>Outlet</th><th>Tanggal</th><th>Status</th><th>Dibayar</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($transaksis)): ?>
                        <tr><td colspan="8" class="text-center text-muted py-4">Belum ada transaksi.</td></tr>
                    <?php else: foreach ($transaksis as $i => $t): ?>
                    <?php
                    $badge = ['baru'=>'warning','proses'=>'info','selesai'=>'success','diambil'=>'secondary'];
                    $badgeBayar = ['dibayar'=>'success','belum_dibayar'=>'danger','diabayar'=>'secondary'];
                    ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><span class="fw-semibold text-success"><?= htmlspecialchars($t['kode_invoice']) ?></span></td>
                        <td class="fw-semibold"><?= htmlspecialchars($t['nama_member'] ?? '-') ?></td>
                        <td class="text-muted"><?= htmlspecialchars($t['nama_outlet'] ?? '-') ?></td>
                        <td><?= date('d/m/Y', strtotime($t['tgl'])) ?></td>
                        <td><span class="badge bg-<?= $badge[$t['status']] ?>"><?= ucfirst($t['status']) ?></span></td>
                        <td><span class="badge bg-<?= $badgeBayar[$t['dibayar']] ?? 'secondary' ?>"><?= ucfirst($t['dibayar']) ?></span></td>
                        <td>
                            <div class="d-flex gap-1 flex-wrap">
                                <a href="detail_transaksi.php?id=<?= $t['id'] ?>" class="btn btn-outline-primary btn-sm">Detail</a>
                                <?php if (!$is_owner): ?>
                                <?php
                                $next = ['baru'=>'proses','proses'=>'selesai','selesai'=>'diambil'];
                                if (isset($next[$t['status']])): ?>
                                <a href="transaksi.php?status=<?= $next[$t['status']] ?>&id=<?= $t['id'] ?>"
                                   class="btn btn-outline-success btn-sm"
                                   onclick="return confirm('Ubah status?')">
                                    <?= ucfirst($next[$t['status']]) ?>
                                </a>
                                <?php endif; ?>
                                <a href="transaksi.php?hapus=<?= $t['id'] ?>" class="btn btn-outline-danger btn-sm"
                                   onclick="return confirm('Hapus transaksi ini?')">Hapus</a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'layout/footer.php'; ?>
