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

// Fetch outlet info for header if not admin
$outlet_info = null;
if ($role !== 'admin') {
    $stmt_o = $db->prepare("SELECT * FROM tb_outlet WHERE id = ?");
    $stmt_o->execute([$id_outlet]);
    $outlet_info = $stmt_o->fetch();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Laporan - Laundry Hisam</title>
    <style>
        body { font-family: 'Arial', sans-serif; color: #333; line-height: 1.6; padding: 20px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #ed1c24; padding-bottom: 10px; }
        .header h1 { margin: 0; color: #ed1c24; }
        .header p { margin: 5px 0; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; font-size: 12px; }
        th { background-color: #f8f9fa; font-weight: bold; }
        tr:nth-child(even) { background-color: #fafafa; }
        .footer { margin-top: 30px; text-align: right; font-size: 12px; }
        .total-row { font-weight: bold; background-color: #eee !important; }
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #2ecc71; color: white; border: none; cursor: pointer; border-radius: 4px;">Cetak / Simpan PDF</button>
        <button onclick="window.close()" style="padding: 10px 20px; background: #95a5a6; color: white; border: none; cursor: pointer; border-radius: 4px;">Tutup</button>
    </div>

    <div class="header">
        <h1>LAUNDRY HISAM</h1>
        <?php if ($outlet_info): ?>
            <p><?= htmlspecialchars($outlet_info['nama']) ?></p>
            <p><?= htmlspecialchars($outlet_info['alamat']) ?> | Telp: <?= htmlspecialchars($outlet_info['tlp']) ?></p>
        <?php else: ?>
            <p>Laporan Konsolidasi Seluruh Outlet</p>
        <?php endif; ?>
        <p style="margin-top: 10px; font-weight: bold; text-decoration: underline;">LAPORAN TRANSAKSI</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Invoice</th>
                <th>Tanggal</th>
                <th>Member</th>
                <?php if($role === 'admin'): ?><th>Outlet</th><?php endif; ?>
                <th>Status</th>
                <th>Pembayaran</th>
                <th>Total Bayar</th>
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
                <td><?= date('d/m/Y', strtotime($row['tgl'])) ?></td>
                <td><?= htmlspecialchars($row['member_name']) ?></td>
                <?php if($role === 'admin'): ?><td><?= htmlspecialchars($row['outlet_name']) ?></td><?php endif; ?>
                <td><?= ucfirst($row['status']) ?></td>
                <td><?= ucfirst($row['dibayar']) ?></td>
                <td>Rp <?= number_format($total_bayar, 0, ',', '.') ?></td>
            </tr>
            <?php endforeach; ?>
            <tr class="total-row">
                <td colspan="<?= ($role === 'admin' ? '7' : '6') ?>" style="text-align: right;">TOTAL PENGHASILAN</td>
                <td>Rp <?= number_format($grand_total, 0, ',', '.') ?></td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <p>Dicetak pada: <?= date('d/m/Y H:i:s') ?></p>
        <p>Petugas: <?= htmlspecialchars($_SESSION['nama'] ?? 'Admin') ?></p>
    </div>
</body>
</html>
