<?php
require_once 'auth.php';
require_once 'config/Database.php';

only_admin();

$db = (new Database())->connect();

if (isset($_GET['hapus'])) {
    if ($_SESSION['role'] !== 'admin') { header('Location: daftar_user.php'); exit; }
    $db->prepare("DELETE FROM tb_user WHERE id = ? AND username != 'admin'")->execute([$_GET['hapus']]);
    header('Location: daftar_user.php'); exit;
}

$users      = $db->query("SELECT u.id, u.nama, u.username, u.role, o.nama AS nama_outlet FROM tb_user u LEFT JOIN tb_outlet o ON o.id = u.id_outlet ORDER BY u.id ASC")->fetchAll();
$page_title = 'Kelola User — Laundry Hisam';
require_once 'layout/sidebar.php';
?>

<div class="main-content">
    <div class="topbar">
        <button class="btn-toggle" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
        <h6>Kelola User</h6>
    </div>



    <div class="card">
        <div class="card-header-custom">
            <h5>Data Pengguna (Staff)</h5>
            <?php if ($_SESSION['role'] === 'admin'): ?>
            <div class="d-flex gap-2">
                <a href="tambah_user.php" class="btn btn-hijau btn-sm">Tambah User</a>
            </div>
            <?php endif; ?>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>NAMA</th>
                        <th>USERNAME</th>
                        <th>OUTLET</th>
                        <th>ROLE</th>
                        <th>AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars($u['nama']) ?></td>
                        <td class="text-muted"><?= htmlspecialchars($u['username']) ?></td>
                        <td>
                            <?php if ($u['nama_outlet']): ?>
                                <span class="badge bg-secondary bg-opacity-15 text-dark fw-normal"><?= htmlspecialchars($u['nama_outlet']) ?></span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $roleColor = ['admin'=>'danger','owner'=>'warning','kasir'=>'success'];
                            $roleLabel = ['admin'=>'Admin','owner'=>'Owner','kasir'=>'Kasir'];
                            $color = $roleColor[$u['role']] ?? 'secondary';
                            $label = $roleLabel[$u['role']] ?? ucfirst($u['role']);
                            ?>
                            <span class="badge bg-<?= $color ?>-subtle text-<?= $color ?> border border-<?= $color ?>-subtle"><?= $label ?></span>
                        </td>
                        <td>
                            <?php if ($_SESSION['role'] === 'admin'): ?>
                                <a href="edit_user.php?id=<?= $u['id'] ?>" class="text-warning fw-semibold me-2 text-decoration-none">Edit</a>
                                <?php if ($u['username'] !== 'admin'): ?>
                                    <a href="daftar_user.php?hapus=<?= $u['id'] ?>" class="text-danger fw-semibold text-decoration-none"
                                       onclick="return confirm('Hapus user ini?')">Hapus</a>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'layout/footer.php'; ?>
