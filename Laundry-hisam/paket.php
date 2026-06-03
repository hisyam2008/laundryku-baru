<?php
require_once 'auth.php';
require_once 'config/Database.php';

only_admin();
$db   = (new Database())->connect();
$edit = null;

if (isset($_GET['hapus'])) {
    if (is_owner()) { header('Location: paket.php'); exit; }
    $id_hapus = $_GET['hapus'];
    try {
        $db->beginTransaction();
        
        // Hapus detail transaksi yang menggunakan paket ini
        $db->prepare("DELETE FROM tb_detail_transaksi WHERE id_paket = ?")->execute([$id_hapus]);
        
        // Hapus paket
        $db->prepare("DELETE FROM tb_paket WHERE id = ?")->execute([$id_hapus]);
        
        $db->commit();
        header('Location: paket.php?msg=deleted'); exit;
    } catch (Exception $e) {
        $db->rollBack();
        header('Location: paket.php?err=' . urlencode("Gagal menghapus paksa: " . $e->getMessage())); exit;
    }
}

if (isset($_GET['edit'])) {
    if ($is_owner) { header('Location: paket.php'); exit; }
    $stmt = $db->prepare("SELECT * FROM tb_paket WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($is_owner) { header('Location: paket.php'); exit; }
    $nama_paket = trim($_POST['nama_paket']);
    $jenis      = $_POST['jenis'];
    $harga      = (int) $_POST['harga'];
    $id_outlet  = !empty($_POST['id_outlet']) ? $_POST['id_outlet'] : null;

    if ($_POST['id']) {
        $db->prepare("UPDATE tb_paket SET nama_paket=?, jenis=?, harga=?, id_outlet=? WHERE id=?")
           ->execute([$nama_paket, $jenis, $harga, $id_outlet, $_POST['id']]);
        header('Location: paket.php'); exit;
    } else {
        $db->prepare("INSERT INTO tb_paket (nama_paket, jenis, harga, id_outlet) VALUES (?,?,?,?)")
           ->execute([$nama_paket, $jenis, $harga, $id_outlet]);
        header('Location: paket.php'); exit;
    }
}

$outlets = $db->query("SELECT id, nama FROM tb_outlet ORDER BY nama")->fetchAll();
$pakets  = $db->query("SELECT p.*, o.nama AS nama_outlet FROM tb_paket p LEFT JOIN tb_outlet o ON o.id = p.id_outlet ORDER BY p.id ASC")->fetchAll();
$page_title = 'Paket — Laundry Hisam';
require_once 'layout/sidebar.php';
?>

<div class="main-content">
    <div class="topbar">
        <button class="btn-toggle" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
        <h6>Paket Laundry</h6>
    </div>

    <?php if (isset($_GET['err'])): ?>
        <div class="alert alert-danger mx-4 mt-3 py-2 small"><?= htmlspecialchars($_GET['err']) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success mx-4 mt-3 py-2 small">Data berhasil dihapus.</div>
    <?php endif; ?>



    <div class="row g-4">
        <?php if (!$is_owner): ?>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header-custom">
                    <h5><?= $edit ? 'Edit Paket' : 'Tambah Paket' ?></h5>
                </div>
                <div class="card-body p-3">
                    <form method="POST">
                        <input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nama Paket</label>
                            <input type="text" name="nama_paket" class="form-control" value="<?= htmlspecialchars($edit['nama_paket'] ?? '') ?>" placeholder="cth: Reguler Kiloan" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Jenis</label>
                            <select name="jenis" class="form-select">
                                <?php foreach (['kiloan','selimut','bed_cover','kaos','lainya'] as $j): ?>
                                    <option value="<?= $j ?>" <?= ($edit['jenis'] ?? '') === $j ? 'selected' : '' ?>><?= ucfirst($j) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Harga</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" name="harga" class="form-control" value="<?= $edit['harga'] ?? '' ?>" placeholder="0" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Outlet</label>
                            <select name="id_outlet" class="form-select">
                                <option value="">— Semua Outlet —</option>
                                <?php foreach ($outlets as $o): ?>
                                    <option value="<?= $o['id'] ?>" <?= ($edit['id_outlet'] ?? '') == $o['id'] ? 'selected' : '' ?>><?= htmlspecialchars($o['nama']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-hijau flex-fill"><?= $edit ? 'Update' : 'Simpan' ?></button>
                            <?php if ($edit): ?>
                                <a href="paket.php" class="btn btn-outline-secondary">Batal</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <div class="col-md-<?= !$is_owner ? '8' : '12' ?>">
            <div class="card">
                <div class="card-header-custom">
                    <h5>Daftar Paket</h5>
                    <span class="badge bg-secondary"><?= count($pakets) ?> paket</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr><th>#</th><th>Nama Paket</th><th>Jenis</th><th>Harga</th><th>Outlet</th><th>Aksi</th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pakets)): ?>
                                <tr><td colspan="6" class="text-center text-muted py-4">Belum ada paket.</td></tr>
                            <?php else: foreach ($pakets as $i => $p): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td class="fw-semibold"><?= htmlspecialchars($p['nama_paket']) ?></td>
                                <td><span class="badge bg-success-subtle text-success"><?= ucfirst($p['jenis']) ?></span></td>
                                <td>Rp <?= number_format($p['harga'], 0, ',', '.') ?></td>
                                <td class="text-muted"><?= htmlspecialchars($p['nama_outlet'] ?? '-') ?></td>
                                <td>
                                    <?php if (!$is_owner): ?>
                                    <a href="paket.php?edit=<?= $p['id'] ?>" class="btn btn-outline-warning btn-sm">Edit</a>
                                    <a href="paket.php?hapus=<?= $p['id'] ?>" class="btn btn-outline-danger btn-sm"
                                       onclick="return confirm('Hapus paket ini?')">Hapus</a>
                                    <?php else: ?>
                                    <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'layout/footer.php'; ?>
