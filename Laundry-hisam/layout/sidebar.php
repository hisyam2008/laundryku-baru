<?php
$current  = basename($_SERVER['PHP_SELF']);
$is_admin = $_SESSION['role'] === 'admin';
$is_owner = $_SESSION['role'] === 'owner';
$is_kasir = $_SESSION['role'] === 'kasir';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Laundry Hisam' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/Laundry-hisam/assets/css/style.css" rel="stylesheet">

</head>
<body>

<div class="sidebar-overlay" id="overlay" onclick="toggleSidebar()"></div>
<div class="sidebar" id="sidebar">
    <?php
    $roleColor = ['admin'=>'danger','owner'=>'warning','kasir'=>'success'];
    $roleLabel = ['admin'=>'Admin','owner'=>'Owner','kasir'=>'Kasir'];
    $userRoleColor = $roleColor[$_SESSION['role']] ?? 'secondary';
    $userRoleLabel = $roleLabel[$_SESSION['role']] ?? ucfirst($_SESSION['role']);
    ?>
    <div class="sidebar-brand">
        <div class="brand-title">Laundry Hisam</div>
        <span class="badge bg-<?= $userRoleColor ?>-subtle text-<?= $userRoleColor ?> border border-<?= $userRoleColor ?>-subtle brand-role"><?= $userRoleLabel ?></span>
    </div>

    <div class="sidebar-nav">
        <a href="/Laundry-hisam/index.php" class="<?= $current === 'index.php' ? 'active' : '' ?>">
            Dashboard
        </a>

        <?php if (!$is_owner): ?>
        <div class="nav-section">Operasional</div>
        <?php endif; ?>
        <?php if (!$is_owner): ?>
        <a href="/Laundry-hisam/transaksi.php" class="<?= in_array($current, ['transaksi.php','tambah_transaksi.php','detail_transaksi.php']) ? 'active' : '' ?>">
            Transaksi
        </a>
        <?php endif; ?>
        
        <a href="/Laundry-hisam/laporan.php" class="<?= $current === 'laporan.php' ? 'active' : '' ?>">
            Laporan
        </a>
        
        <?php if ($is_admin): ?>
        <a href="/Laundry-hisam/paket.php" class="<?= $current === 'paket.php' ? 'active' : '' ?>">
            Paket
        </a>
        <?php endif; ?>

        <?php if ($is_admin || $is_kasir): ?>
        <a href="/Laundry-hisam/member.php" class="<?= $current === 'member.php' ? 'active' : '' ?>">
            Pelanggan
        </a>
        <?php endif; ?>

        <?php if (!$is_owner): ?>
        <div class="nav-section">Manajemen</div>
        <?php endif; ?>
        <?php if ($is_admin): ?>
        <a href="/Laundry-hisam/outlet.php" class="<?= $current === 'outlet.php' ? 'active' : '' ?>">
            Outlet
        </a>
        <?php endif; ?>

        <?php if ($is_admin): ?>
        <a href="/Laundry-hisam/daftar_user.php" class="<?= in_array($current, ['daftar_user.php','tambah_user.php','edit_user.php']) ? 'active' : '' ?>">
            Pengguna
        </a>
        <?php endif; ?>

        <a href="/Laundry-hisam/logout.php" style="color:#e53e3e;">
            Logout
        </a>
    </div>

    <div class="sidebar-user"></div>
</div>

<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('show');
    document.getElementById('overlay').classList.toggle('show');
}
</script>
