<?php
require_once 'auth.php';
require_once 'config/Database.php';

$db = (new Database())->connect();
$id_outlet = $_SESSION['id_outlet'];
$role = $_SESSION['role'];

// Filter by outlet if not admin
$where = " WHERE 1=1 ";
$params = [];

if ($role !== 'admin') {
    $where .= " AND t.id_outlet = ? ";
    $params[] = $id_outlet;
}

$sql = "SELECT t.*, m.nama as member_name, o.nama as outlet_name 
        FROM tb_transaksi t
        JOIN tb_member m ON t.id_member = m.id
        JOIN tb_outlet o ON t.id_outlet = o.id
        $where
        ORDER BY t.tgl DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$data = $stmt->fetchAll();

$page_title = 'Laporan - Laundry Hisam';
require_once 'layout/sidebar.php';
?>

<div class="main-content">
    <div class="container-fluid py-4">
        <h2 class="mb-4">Laporan Transaksi</h2>
        
        <div class="mb-4 d-flex justify-content-between align-items-center no-print">
            <p class="mb-0 text-white-50">Menampilkan seluruh data transaksi.</p>
            <a href="cetak_laporan.php" target="_blank" class="btn btn-primary">
                <i class="bi bi-file-earmark-pdf me-1"></i>Cetak PDF / Print
            </a>
        </div>

        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Invoice</th>
                    <th>Member</th>
                    <?php if($role === 'admin'): ?><th>Outlet</th><?php endif; ?>
                    <th>Tanggal</th>
                    <th>Status</th>
                    <th>Pembayaran</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $grand_total = 0;
                foreach ($data as $i => $row): 
                    $stmt_items = $db->prepare("SELECT SUM(qty * harga) as subtotal FROM tb_detail_transaksi dt JOIN tb_paket p ON dt.id_paket = p.id WHERE dt.id_transaksi = ?");
                    $stmt_items->execute([$row['id']]);
                    $res_items = $stmt_items->fetch();
                    $subtotal = $res_items['subtotal'] ?? 0;
                    $diskon_val = $subtotal * (($row['diskon'] ?? 0) / 100);
                    $pajak_val = $subtotal * (($row['pajak'] ?? 0) / 100);
                    $total_bayar = $subtotal + ($row['biaya_tambahan'] ?? 0) - $diskon_val + $pajak_val;
                    $grand_total += $total_bayar;
                ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= $row['kode_invoice'] ?></td>
                    <td><?= htmlspecialchars($row['member_name']) ?></td>
                    <?php if($role === 'admin'): ?><td><?= htmlspecialchars($row['outlet_name']) ?></td><?php endif; ?>
                    <td><?= date('d/m/Y', strtotime($row['tgl'])) ?></td>
                    <td><?= ucfirst($row['status']) ?></td>
                    <td><?= ucfirst($row['dibayar']) ?></td>
                    <td>Rp <?= number_format($total_bayar, 0, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="fw-bold">
                <tr>
                    <td colspan="<?= ($role === 'admin' ? '7' : '6') ?>" class="text-end">TOTAL KESELURUHAN</td>
                    <td>Rp <?= number_format($grand_total, 0, ',', '.') ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<style>
@media print {
    .sidebar, .no-print, .topbar { display: none !important; }
    .main-content { margin-left: 0 !important; }
    .table-dark { background-color: #fff !important; color: #000 !important; }
}
</style>

<?php require_once 'layout/footer.php'; ?>
