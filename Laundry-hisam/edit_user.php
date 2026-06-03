<?php
require_once 'auth.php';
require_once 'config/Database.php';

only_admin();

$db      = (new Database())->connect();
$outlets = $db->query("SELECT id, nama FROM tb_outlet ORDER BY nama")->fetchAll();
$error   = '';

$id   = $_GET['id'] ?? null;
$stmt = $db->prepare("SELECT * FROM tb_user WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();
if (!$user) { header('Location: daftar_user.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama      = trim($_POST['nama']);
    $username  = trim($_POST['username']);
    $role      = $_POST['role'];
    $id_outlet = !empty($_POST['id_outlet']) ? $_POST['id_outlet'] : null;
    $password  = $_POST['password'];

    if (!empty($password)) {
        $db->prepare("UPDATE tb_user SET nama=?, username=?, role=?, id_outlet=?, password=? WHERE id=?")
           ->execute([$nama, $username, $role, $id_outlet, $password, $id]);
    } else {
        $db->prepare("UPDATE tb_user SET nama=?, username=?, role=?, id_outlet=? WHERE id=?")
           ->execute([$nama, $username, $role, $id_outlet, $id]);
    }
    header('Location: daftar_user.php?msg=' . urlencode('User berhasil diupdate')); exit;
}

$page_title = 'Edit User — Laundry Hisam';
require_once 'layout/sidebar.php';
?>

<div class="main-content">
    <div class="topbar">
        <button class="btn-toggle" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
        <h6><i class="bi bi-person-gear me-2"></i>Edit User</h6>
        <a href="daftar_user.php" class="btn btn-sm btn-outline-secondary ms-auto"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
    </div>

    <div class="card">
        <div class="card-header-custom">
            <h5><i class="bi bi-person-gear me-2"></i>Form Edit User</h5>
        </div>
        <div class="card-body p-4">
            <form method="POST">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold">Nama</label>
                        <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($user['nama']) ?>" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Username</label>
                        <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Password Baru <small class="text-muted fw-normal">(kosongkan jika tidak diubah)</small></label>
                        <input type="password" name="password" class="form-control" placeholder="Masukkan password baru">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Role</label>
                        <select name="role" class="form-select">
                            <option value="admin"  <?= $user['role'] === 'admin'  ? 'selected' : '' ?>>Admin</option>
                            <option value="owner"  <?= $user['role'] === 'owner'  ? 'selected' : '' ?>>Owner</option>
                            <option value="kasir"  <?= $user['role'] === 'kasir'  ? 'selected' : '' ?>>Kasir</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Outlet</label>
                        <select name="id_outlet" class="form-select">
                            <option value="">— Pilih Outlet —</option>
                            <?php foreach ($outlets as $o): ?>
                                <option value="<?= $o['id'] ?>" <?= $user['id_outlet'] == $o['id'] ? 'selected' : '' ?>><?= htmlspecialchars($o['nama']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 mt-2">
                        <button type="submit" class="btn btn-hijau px-4"><i class="bi bi-check-lg me-1"></i>Simpan</button>
                        <a href="daftar_user.php" class="btn btn-outline-secondary ms-2">Batal</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'layout/footer.php'; ?>
