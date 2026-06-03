<?php
require_once 'auth.php';
require_once 'config/Database.php';

only_admin();

$db    = (new Database())->connect();
$error = '';

$outlets = $db->query("SELECT id, nama FROM tb_outlet ORDER BY nama ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama      = trim($_POST['nama']);
    $username  = trim($_POST['username']);
    $password  = $_POST['password'];
    $konfirm   = $_POST['konfirm'];
    $role      = $_POST['role'];
    $id_outlet = !empty($_POST['id_outlet']) && $_POST['id_outlet'] != '0' ? $_POST['id_outlet'] : null;

    if ($password !== $konfirm) {
        $error = 'Password dan konfirmasi tidak cocok.';
    } else {
        $cek = $db->prepare("SELECT id FROM tb_user WHERE username = ?");
        $cek->execute([$username]);
        if ($cek->fetch()) {
            $error = 'Username sudah dipakai.';
        } else {
            $db->prepare("INSERT INTO tb_user (nama, username, password, role, id_outlet) VALUES (?,?,?,?,?)")
               ->execute([$nama, $username, $password, $role, $id_outlet]);
            header('Location: daftar_user.php?msg=' . urlencode('User berhasil ditambahkan')); exit;
        }
    }
}

$page_title = 'Tambah User — Laundry Hisam';
require_once 'layout/sidebar.php';
?>

<div class="main-content">
    <div class="topbar">
        <button class="btn-toggle" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
        <h6><i class="bi bi-person-plus me-2"></i>Tambah User</h6>
        <a href="daftar_user.php" class="btn btn-sm btn-outline-secondary ms-auto"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
    </div>

    <div class="card">
        <div class="card-header-custom">
            <h5><i class="bi bi-person-plus me-2"></i>Form Tambah User</h5>
        </div>
        <div class="card-body p-4">
            <?php if ($error): ?>
                <div class="alert alert-danger py-2"><i class="bi bi-exclamation-circle me-1"></i><?= $error ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold">Nama</label>
                        <input type="text" name="nama" class="form-control" placeholder="Masukkan nama lengkap" required autofocus>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Username</label>
                        <input type="text" name="username" class="form-control" placeholder="Masukkan username" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Konfirmasi Password</label>
                        <input type="password" name="konfirm" class="form-control" placeholder="Ulangi password" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Role</label>
                        <select name="role" class="form-select">
                            <option value="admin">Admin</option>
                            <option value="owner">Owner</option>
                            <option value="kasir">Kasir</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Outlet</label>
                        <select name="id_outlet" class="form-select">
                            <option value="0">— Pilih Outlet —</option>
                            <?php foreach ($outlets as $o): ?>
                                <option value="<?= $o['id'] ?>"><?= htmlspecialchars($o['nama']) ?></option>
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
