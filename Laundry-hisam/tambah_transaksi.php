<?php
require_once 'auth.php';
require_once 'config/Database.php';

if ($_SESSION['role'] === 'owner') { header('Location: transaksi.php'); exit; }

$db      = (new Database())->connect();
if ($_SESSION['role'] === 'admin') {
    $members = $db->query("SELECT id, nama FROM tb_member ORDER BY nama")->fetchAll();
} else {
    $stmt_m = $db->prepare("SELECT id, nama FROM tb_member WHERE id_outlet = ? ORDER BY nama");
    $stmt_m->execute([$_SESSION['id_outlet']]);
    $members = $stmt_m->fetchAll();
}
$stmt_p = $db->prepare("SELECT * FROM tb_paket WHERE id_outlet = ? ORDER BY nama_paket");
$stmt_p->execute([$_SESSION['id_outlet']]);
$pakets = $stmt_p->fetchAll();
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_member       = !empty($_POST['id_member']) ? $_POST['id_member'] : null;
    $id_outlet       = !empty($_POST['id_outlet']) ? $_POST['id_outlet'] : null;
    $tgl             = $_POST['tgl'];
    $batas_waktu     = $_POST['batas_waktu'];
    $tgl_bayar       = !empty($_POST['tgl_bayar']) ? $_POST['tgl_bayar'] : date('Y-m-d H:i:s');
    $biaya_tambahan  = (int) ($_POST['biaya_tambahan'] ?? 0);
    $diskon          = (float) ($_POST['diskon'] ?? 0);
    $pajak           = (int) ($_POST['pajak'] ?? 0);
    $dibayar         = $_POST['dibayar'] ?? 'belum_dibayar';
    $id_pakets       = $_POST['id_paket'] ?? [];
    $qtys            = $_POST['qty'] ?? [];
    $keterangans     = $_POST['keterangan'] ?? [];

    if (empty($id_pakets)) {
        $error = 'Minimal tambahkan 1  .';
    } else {
        $kode_invoice = 'INV' . date('Ymd') . strtoupper(substr(uniqid(), -4));

        $db->prepare("INSERT INTO tb_transaksi (kode_invoice, id_member, id_outlet, id_user, tgl, batas_waktu, tgl_bayar, biaya_tambahan, diskon, pajak, status, dibayar)
                      VALUES (?,?,?,?,?,?,?,?,?,?,'baru',?)")
           ->execute([$kode_invoice, $id_member, $id_outlet, $_SESSION['id_user'], $tgl, $batas_waktu, $tgl_bayar, $biaya_tambahan, $diskon, $pajak, $dibayar]);

        $id_transaksi = $db->lastInsertId();
        foreach ($id_pakets as $k => $id_paket) {
            if (empty($id_paket) || empty($qtys[$k])) continue;
            $keterangan = $keterangans[$k] ?? '';
            $db->prepare("INSERT INTO tb_detail_transaksi (id_transaksi, id_paket, qty, keterangan) VALUES (?,?,?,?)")
               ->execute([$id_transaksi, $id_paket, $qtys[$k], $keterangan]);
        }

        header('Location: transaksi.php?msg=' . urlencode('Transaksi berhasil ditambahkan')); exit;
    }
}

$page_title = 'Tambah Transaksi — Laundry Hisam';
require_once 'layout/sidebar.php';
?>

<div class="main-content">
    <div class="topbar">
        <button class="btn-toggle" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
        <h6>Transaksi Baru</h6>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2 mx-4 mt-3"><i class="bi bi-exclamation-circle me-1"></i><?= $error ?></div>
    <?php endif; ?>

    <div class="page-content">
    <form method="POST">
        <div class="card">
            <div class="card-body p-4">
                <div class="row g-3">
                    <input type="hidden" name="id_outlet" value="<?= $_SESSION['id_outlet'] ?>">
                    
                    <div class="col-md-12">
                        <label class="form-label fw-semibold">Member / Pelanggan</label>
                        <select name="id_member" class="form-select">
                            <option value="">-- Pilih Member --</option>
                            <?php foreach ($members as $m): ?>
                                <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nama']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Tanggal & Batas Waktu -->
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Tanggal</label>
                        <input type="datetime-local" name="tgl" class="form-control" value="<?= date('Y-m-d\TH:i') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Batas Waktu</label>
                        <input type="datetime-local" name="batas_waktu" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime('+3 days')) ?>" required>
                    </div>

                    <!-- Item Paket -->
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label fw-semibold mb-0">Item Paket</label>
                            <a href="#" onclick="tambahItem(); return false;" class="text-primary fw-semibold text-decoration-none">+ Tambah Item</a>
                        </div>
                        <div id="items-container">
                            <div class="item-row row g-2 mb-2">
                                <div class="col-md-5">
                                    <select name="id_paket[]" class="form-select">
                                        <option value="">-- Pilih Paket --</option>
                                        <?php foreach ($pakets as $p): ?>
                                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nama_paket']) ?> (<?= ucfirst($p['jenis']) ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <input type="number" name="qty[]" class="form-control" placeholder="Qty" min="0.1" step="0.1">
                                </div>
                                <div class="col-md-5">
                                    <input type="text" name="keterangan[]" class="form-control" placeholder="Keterangan">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Biaya Tambahan, Diskon, Pajak -->
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Biaya Tambahan</label>
                        <input type="number" name="biaya_tambahan" class="form-control" value="0" min="0" placeholder="0">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Diskon (%)</label>
                        <input type="number" name="diskon" class="form-control" value="0" min="0" max="100" step="0.1" placeholder="0">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Pajak (%)</label>
                        <input type="number" name="pajak" class="form-control" value="0" min="0" max="100" step="0.1" placeholder="0">
                    </div>

                    <!-- Status & Pembayaran -->
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status" class="form-select">
                            <option value="baru">Baru</option>
                            <option value="proses">Proses</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Pembayaran</label>
                        <select name="dibayar" class="form-select">
                            <option value="belum_dibayar">Belum Dibayar</option>
                            <option value="dibayar">Dibayar</option>
                        </select>
                    </div>

                    <!-- Tombol -->
                    <div class="col-12 d-flex justify-content-between align-items-center mt-2">
                        <button type="submit" class="btn btn-hijau px-4">Simpan Transaksi</button>
                        <a href="transaksi.php" class="text-muted text-decoration-none">Batal</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
    </div>
</div>

<script>
const paketOptions = `<?php foreach ($pakets as $p): ?><option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nama_paket']) ?> (<?= ucfirst($p['jenis']) ?>)</option><?php endforeach; ?>`;

function tambahItem() {
    const container = document.getElementById('items-container');
    const div = document.createElement('div');
    div.className = 'item-row row g-2 mb-2';
    div.innerHTML = `
        <div class="col-md-5">
            <select name="id_paket[]" class="form-select">
                <option value="">-- Pilih Paket --</option>${paketOptions}
            </select>
        </div>
        <div class="col-md-2">
            <input type="number" name="qty[]" class="form-control" placeholder="Qty" min="0.1" step="0.1">
        </div>
        <div class="col-md-4">
            <input type="text" name="keterangan[]" class="form-control" placeholder="Keterangan">
        </div>
        <div class="col-md-1">
            <button type="button" class="btn btn-outline-danger w-100" onclick="hapusItem(this)"><i class="bi bi-trash"></i></button>
        </div>`;
    container.appendChild(div);
}

function hapusItem(btn) {
    btn.closest('.item-row').remove();
}
</script>

<?php require_once 'layout/footer.php'; ?>
