<?php
require_once 'auth.php';
require_once 'config/Database.php';

$db = (new Database())->connect();
$id = $_GET['id'] ?? null;
if (!$id) { header('Location: transaksi.php'); exit; }

$stmt = $db->prepare("SELECT t.*, o.nama AS nama_outlet, m.nama AS nama_member, u.nama AS nama_kasir
    FROM tb_transaksi t
    LEFT JOIN tb_outlet o ON o.id = t.id_outlet
    LEFT JOIN tb_member m ON m.id = t.id_member
    LEFT JOIN tb_user u ON u.id = t.id_user
    WHERE t.id = ?");
$stmt->execute([$id]);
$trx = $stmt->fetch();

if (!$trx || ($_SESSION['role'] !== 'admin' && $trx['id_outlet'] != $_SESSION['id_outlet'])) {
    header('Location: transaksi.php');
    exit;
}

$stmt2 = $db->prepare("SELECT d.*, p.nama_paket, p.jenis, p.harga AS harga_paket
    FROM tb_detail_transaksi d
    JOIN tb_paket p ON p.id = d.id_paket
    WHERE d.id_transaksi = ?");
$stmt2->execute([$id]);
$details = $stmt2->fetchAll();

$badge      = ['baru'=>'warning','proses'=>'info','selesai'=>'success','diambil'=>'secondary'];
$badgeBayar = ['dibayar'=>'success','belum_dibayar'=>'danger','diabayar'=>'secondary'];

$page_title = 'Detail Transaksi — Laundry Hisam';
require_once 'layout/sidebar.php';
?>

<div class="main-content">
    <div class="topbar">
        <button class="btn-toggle" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
        <h6>Detail Transaksi</h6>
        <div class="ms-auto d-flex gap-2">
            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">Print</button>
            <a href="transaksi.php" class="btn btn-sm btn-outline-secondary">Kembali</a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-5">
            <div class="card">
                <div class="card-header-custom">
                    <h5>Info Transaksi</h5>
                    <div class="d-flex gap-1">
                        <span class="badge bg-<?= $badge[$trx['status']] ?>"><?= ucfirst($trx['status']) ?></span>
                        <span class="badge bg-<?= $badgeBayar[$trx['dibayar']] ?? 'secondary' ?>"><?= ucfirst($trx['dibayar']) ?></span>
                    </div>
                </div>
                <div class="card-body p-3">
                    <table class="table table-sm mb-0">
                        <tr>
                            <td class="text-muted" style="width:40%">Kode Invoice</td>
                            <td class="fw-bold text-success"><?= htmlspecialchars($trx['kode_invoice']) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Member</td>
                            <td class="fw-semibold"><?= htmlspecialchars($trx['nama_member'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Outlet</td>
                            <td><?= htmlspecialchars($trx['nama_outlet'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Kasir</td>
                            <td><?= htmlspecialchars($trx['nama_kasir'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Tanggal</td>
                            <td><?= date('d/m/Y H:i', strtotime($trx['tgl'])) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Batas Waktu</td>
                            <td><?= date('d/m/Y H:i', strtotime($trx['batas_waktu'])) ?></td>
                        </tr>
                        <?php if ($trx['tgl_bayar']): ?>
                        <tr>
                            <td class="text-muted">Tgl Bayar</td>
                            <td><?= date('d/m/Y H:i', strtotime($trx['tgl_bayar'])) ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <td class="text-muted">Biaya Tambahan</td>
                            <td>Rp <?= number_format($trx['biaya_tambahan'], 0, ',', '.') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Diskon</td>
                            <td><?= $trx['diskon'] ?>%</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Pajak</td>
                            <td><?= $trx['pajak'] ?>%</td>
                        </tr>
                    </table>
                </div>
            </div>

            <?php
            $next      = ['baru'=>'proses','proses'=>'selesai','selesai'=>'diambil'];
            $nextLabel = ['baru'=>'Tandai Proses','proses'=>'Tandai Selesai','selesai'=>'Tandai Diambil'];
            $nextColor = ['baru'=>'info','proses'=>'success','selesai'=>'secondary'];
            if (isset($next[$trx['status']])): ?>
            <div class="mt-3">
                <a href="transaksi.php?status=<?= $next[$trx['status']] ?>&id=<?= $trx['id'] ?>"
                   class="btn btn-<?= $nextColor[$trx['status']] ?> w-100"
                   onclick="return confirm('Ubah status?')">
                    <?= $nextLabel[$trx['status']] ?>
                </a>
            </div>
            <?php endif; ?>
        </div>

        <div class="col-md-7">
            <div class="card">
                <div class="card-header-custom">
                    <h5>Rincian Item</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr><th>#</th><th>Paket</th><th>Jenis</th><th>Qty</th><th>Harga</th><th>Keterangan</th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($details)): ?>
                                <tr><td colspan="6" class="text-center text-muted py-3">Tidak ada item.</td></tr>
                            <?php else: foreach ($details as $i => $d): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td class="fw-semibold"><?= htmlspecialchars($d['nama_paket']) ?></td>
                                <td><span class="badge bg-success-subtle text-success"><?= ucfirst($d['jenis']) ?></span></td>
                                <td><?= $d['qty'] ?></td>
                                <td>Rp <?= number_format($d['harga_paket'], 0, ',', '.') ?></td>
                                <td class="text-muted"><?= htmlspecialchars($d['keterangan'] ?: '-') ?></td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white border-top-0 p-3 pt-0">
                    <?php
                    $subtotal = 0;
                    foreach ($details as $d) { $subtotal += ($d['qty'] * $d['harga_paket']); }
                    $diskon_val = ($subtotal * $trx['diskon'] / 100);
                    $pajak_val  = ($subtotal * $trx['pajak'] / 100);
                    $total      = ($subtotal + $trx['biaya_tambahan'] - $diskon_val + $pajak_val);
                    ?>
                    <div class="border rounded p-3 bg-light">
                        <div class="d-flex justify-content-between mb-1 small">
                            <span>Subtotal</span>
                            <span>Rp <?= number_format($subtotal, 0, ',', '.') ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-1 small">
                            <span>Biaya Tambahan</span>
                            <span>+ Rp <?= number_format($trx['biaya_tambahan'], 0, ',', '.') ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-1 small text-danger">
                            <span>Diskon (<?= $trx['diskon'] ?>%)</span>
                            <span>- Rp <?= number_format($diskon_val, 0, ',', '.') ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2 small text-primary">
                            <span>Pajak (<?= $trx['pajak'] ?>%)</span>
                            <span>+ Rp <?= number_format($pajak_val, 0, ',', '.') ?></span>
                        </div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between fw-bold text-success fs-5">
                            <span>Total Bayar</span>
                            <span>Rp <?= number_format($total, 0, ',', '.') ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .sidebar, .topbar, .btn, .sidebar-overlay { display: none !important; }
    .main-content { margin-left: 0 !important; }
}
</style>

<?php require_once 'layout/footer.php'; ?>
