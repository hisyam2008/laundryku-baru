<?php
require_once 'auth.php';
require_once 'config/Database.php';

only_admin();
$db   = (new Database())->connect();
$edit = null;

if (isset($_GET['hapus'])) {
    if (is_owner()) { header('Location: outlet.php'); exit; }
    $id_hapus = $_GET['hapus'];
    
    try {
        $db->beginTransaction();

        // 1. Hapus detail transaksi yang transaksinya ada di outlet ini
        $db->prepare("DELETE dt FROM tb_detail_transaksi dt JOIN tb_transaksi t ON dt.id_transaksi = t.id WHERE t.id_outlet = ?")->execute([$id_hapus]);

        // 2. Hapus transaksi di outlet ini
        $db->prepare("DELETE FROM tb_transaksi WHERE id_outlet = ?")->execute([$id_hapus]);

        // 3. Hapus paket di outlet ini
        $db->prepare("DELETE FROM tb_paket WHERE id_outlet = ?")->execute([$id_hapus]);

        // 4. Set NULL id_outlet pada user (agar user tidak ikut terhapus)
        $db->prepare("UPDATE tb_user SET id_outlet = NULL WHERE id_outlet = ?")->execute([$id_hapus]);

        // 5. Akhirnya hapus outlet
        $db->prepare("DELETE FROM tb_outlet WHERE id = ?")->execute([$id_hapus]);

        $db->commit();
        header('Location: outlet.php?msg=deleted'); exit;
    } catch (Exception $e) {
        $db->rollBack();
        header('Location: outlet.php?err=' . urlencode("Gagal hapus paksa: " . $e->getMessage())); exit;
    }
}

if (isset($_GET['edit'])) {
    if ($is_owner) { header('Location: outlet.php'); exit; }
    $stmt = $db->prepare("SELECT * FROM tb_outlet WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($is_owner) { header('Location: outlet.php'); exit; }
    $nama   = trim($_POST['nama']);
    $alamat = trim($_POST['alamat']);
    $tlp    = trim($_POST['tlp']);

    if ($_POST['id']) {
        $db->prepare("UPDATE tb_outlet SET nama=?, alamat=?, tlp=? WHERE id=?")
           ->execute([$nama, $alamat, $tlp, $_POST['id']]);
        header('Location: outlet.php'); exit;
    } else {
        $db->prepare("INSERT INTO tb_outlet (nama, alamat, tlp) VALUES (?,?,?)")
           ->execute([$nama, $alamat, $tlp]);
        header('Location: outlet.php'); exit;
    }
}

$outlets    = $db->query("SELECT o.*, (SELECT COUNT(*) FROM tb_member m WHERE m.id_outlet = o.id) as total_member FROM tb_outlet o ORDER BY o.id ASC")->fetchAll();
$page_title = 'Outlet — Laundry Hisam';
require_once 'layout/sidebar.php';
?>

<div class="main-content">
    <div class="topbar">
        <button class="btn-toggle" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
        <h6>Outlet</h6>
    </div>

    <?php if (isset($_GET['err'])): ?>
        <div class="alert alert-danger mx-4 mt-3 py-2 small"><?= htmlspecialchars($_GET['err']) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success mx-4 mt-3 py-2 small">Outlet berhasil dihapus.</div>
    <?php endif; ?>



    <div class="row g-4">
        <?php if (!$is_owner): ?>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header-custom">
                    <h5><?= $edit ? 'Edit Outlet' : 'Tambah Outlet' ?></h5>
                </div>
                <div class="card-body p-3">
                    <form method="POST">
                        <input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nama Outlet</label>
                            <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($edit['nama'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Alamat</label>
                            <textarea name="alamat" class="form-control" rows="2" required><?= htmlspecialchars($edit['alamat'] ?? '') ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">No. Telepon</label>
                            <input type="text" name="tlp" class="form-control" value="<?= htmlspecialchars($edit['tlp'] ?? '') ?>" required>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-hijau flex-fill"><?= $edit ? 'Update' : 'Simpan' ?></button>
                            <?php if ($edit): ?>
                                <a href="outlet.php" class="btn btn-outline-secondary">Batal</a>
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
                    <h5>Daftar Outlet</h5>
                    <span class="badge bg-secondary"><?= count($outlets) ?> outlet</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nama</th>
                                <th>Alamat</th>
                                <th>Telepon</th>
                                <th>Member</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($outlets)): ?>
                                <tr><td colspan="5" class="text-center text-muted py-4">Belum ada outlet.</td></tr>
                            <?php else: foreach ($outlets as $i => $o): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td class="fw-semibold"><?= htmlspecialchars($o['nama']) ?></td>
                                <td class="text-muted"><?= htmlspecialchars($o['alamat']) ?></td>
                                <td><?= htmlspecialchars($o['tlp']) ?></td>
                                <td class="fw-bold text-info"><?= $o['total_member'] ?></td>
                                <td>
                                    <?php if (!$is_owner): ?>
                                    <a href="outlet.php?edit=<?= $o['id'] ?>" class="btn btn-outline-warning btn-sm">Edit</a>
                                    <a href="outlet.php?hapus=<?= $o['id'] ?>" class="btn btn-outline-danger btn-sm"
                                       onclick="return confirm('Hapus outlet ini?')">Hapus</a>
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
