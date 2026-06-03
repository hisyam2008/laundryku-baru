<?php
require_once 'auth.php';
require_once 'config/Database.php';

$is_admin = $_SESSION['role'] === 'admin';
$is_owner = $_SESSION['role'] === 'owner';
$is_kasir = $_SESSION['role'] === 'kasir';
if ($is_owner) { header('Location: index.php?err=akses'); exit; }
$db   = (new Database())->connect();
$edit = null;

if (isset($_GET['hapus'])) {
    if ($is_owner) { header('Location: member.php?err=akses'); exit; }
    $db->prepare("DELETE FROM tb_member WHERE id = ?")->execute([$_GET['hapus']]);
    header('Location: member.php'); exit;
}

if (isset($_GET['edit'])) {
    if ($is_owner) { header('Location: member.php?err=akses'); exit; }
    $stmt = $db->prepare("SELECT * FROM tb_member WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama          = trim($_POST['nama']);
    $alamat        = trim($_POST['alamat']);
    $jenis_kelamin = $_POST['jenis_kelamin'];
    $tlp           = trim($_POST['tlp']);

    if ($_POST['id']) {
        if ($is_owner) { header('Location: member.php?err=akses'); exit; }
        $db->prepare("UPDATE tb_member SET nama=?, alamat=?, jenis_kelamin=?, tlp=? WHERE id=?")
           ->execute([$nama, $alamat, $jenis_kelamin, $tlp, $_POST['id']]);
        header('Location: member.php'); exit;
    } else {
        if ($is_owner) { header('Location: member.php?err=akses'); exit; }
        // Set id_outlet for non-admins as well
        $id_outlet = $is_admin ? null : $_SESSION['id_outlet'];
        $db->prepare("INSERT INTO tb_member (nama, alamat, jenis_kelamin, tlp, id_outlet) VALUES (?,?,?,?,?)")
           ->execute([$nama, $alamat, $jenis_kelamin, $tlp, $id_outlet]);
        header('Location: member.php'); exit;
    }
}

$where = "";
$params = [];
if (!$is_admin) {
    $where = " WHERE m.id_outlet = ? ";
    $params[] = $_SESSION['id_outlet'];
}

$members = $db->prepare("SELECT m.*, o.nama as nama_outlet FROM tb_member m LEFT JOIN tb_outlet o ON m.id_outlet = o.id $where ORDER BY m.id ASC");
$members->execute($params);
$members = $members->fetchAll();
$page_title = 'Member — Laundry Hisam';
require_once 'layout/sidebar.php';
?>

<div class="main-content">
    <div class="topbar">
        <button class="btn-toggle" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
        <h6>Member</h6>
    </div>



    <div class="row g-4">
        <?php if (!$is_owner): ?>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header-custom">
                    <h5><?= $edit ? 'Edit Member' : 'Tambah Member' ?></h5>
                </div>
                <div class="card-body p-3">
                    <form method="POST">
                        <input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nama</label>
                            <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($edit['nama'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Alamat</label>
                            <textarea name="alamat" class="form-control" rows="2"><?= htmlspecialchars($edit['alamat'] ?? '') ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Jenis Kelamin</label>
                            <select name="jenis_kelamin" class="form-select">
                                <option value="L" <?= ($edit['jenis_kelamin'] ?? '') === 'L' ? 'selected' : '' ?>>Laki-laki</option>
                                <option value="P" <?= ($edit['jenis_kelamin'] ?? '') === 'P' ? 'selected' : '' ?>>Perempuan</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">No. Telepon</label>
                            <input type="text" name="tlp" class="form-control" value="<?= htmlspecialchars($edit['tlp'] ?? '') ?>" required>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-hijau flex-fill"><?= $edit ? 'Update' : 'Simpan' ?></button>
                            <?php if ($edit): ?>
                                <a href="member.php" class="btn btn-outline-secondary">Batal</a>
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
                    <h5>Daftar Member</h5>
                    <span class="badge bg-secondary"><?= count($members) ?> member</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nama</th>
                                <th>Alamat</th>
                                <th>JK</th>
                                <th>Telepon</th>
                                <?php if($is_admin): ?><th>Outlet</th><?php endif; ?>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($members)): ?>
                                <tr><td colspan="6" class="text-center text-muted py-4">Belum ada member.</td></tr>
                            <?php else: foreach ($members as $i => $m): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td class="fw-semibold"><?= htmlspecialchars($m['nama']) ?></td>
                                <td class="text-muted"><?= htmlspecialchars($m['alamat']) ?></td>
                                <td>
                                    <span class="badge <?= $m['jenis_kelamin'] === 'L' ? 'bg-primary' : 'bg-danger' ?>">
                                        <?= $m['jenis_kelamin'] === 'L' ? 'L' : 'P' ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($m['tlp']) ?></td>
                                <?php if($is_admin): ?>
                                    <td class="small text-white-50"><?= htmlspecialchars($m['nama_outlet'] ?? 'Global') ?></td>
                                <?php endif; ?>
                        <td>
                                    <?php if (!$is_owner): ?>
                                    <a href="member.php?edit=<?= $m['id'] ?>" class="btn btn-outline-warning btn-sm">Edit</a>
                                    <a href="member.php?hapus=<?= $m['id'] ?>" class="btn btn-outline-danger btn-sm"
                                       onclick="return confirm('Hapus member ini?')">Hapus</a>
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
