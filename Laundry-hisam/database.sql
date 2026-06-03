
CREATE TABLE IF NOT EXISTS tb_outlet (
    id     INT AUTO_INCREMENT PRIMARY KEY,
    nama   VARCHAR(100) NOT NULL,
    alamat TEXT,
    tlp    VARCHAR(15)
);

CREATE TABLE IF NOT EXISTS tb_member (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    nama          VARCHAR(100) NOT NULL,
    alamat        TEXT,
    jenis_kelamin ENUM('L','P') NOT NULL,
    tlp           VARCHAR(15)
);

CREATE TABLE IF NOT EXISTS tb_user (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    nama      VARCHAR(100) NOT NULL,
    username  VARCHAR(30) NOT NULL UNIQUE,
    password  TEXT NOT NULL,
    id_outlet INT DEFAULT NULL,
    role      ENUM('admin','kasir','owner') NOT NULL DEFAULT 'kasir',
    FOREIGN KEY (id_outlet) REFERENCES tb_outlet(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS tb_paket (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    id_outlet   INT DEFAULT NULL,
    jenis       ENUM('kiloan','selimut','bed_cover','kaos','lain') NOT NULL,
    nama_paket  VARCHAR(100) NOT NULL,
    harga       INT NOT NULL,
    FOREIGN KEY (id_outlet) REFERENCES tb_outlet(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS tb_transaksi (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    id_outlet      INT DEFAULT NULL,
    kode_invoice   VARCHAR(100) NOT NULL UNIQUE,
    id_member      INT DEFAULT NULL,
    tgl            DATETIME NOT NULL,
    batas_waktu    DATETIME NOT NULL,
    tgl_bayar      DATETIME DEFAULT NULL,
    biaya_tambahan INT DEFAULT 0,
    diskon         DOUBLE DEFAULT 0,
    pajak          DOUBLE DEFAULT 0,
    status         ENUM('baru','proses','selesai','diambil') NOT NULL DEFAULT 'baru',
    dibayar        ENUM('dibayar','belum_dibayar') NOT NULL DEFAULT 'belum_dibayar',
    id_user        INT DEFAULT NULL,
    FOREIGN KEY (id_outlet) REFERENCES tb_outlet(id) ON DELETE SET NULL,
    FOREIGN KEY (id_member) REFERENCES tb_member(id) ON DELETE SET NULL,
    FOREIGN KEY (id_user)   REFERENCES tb_user(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS tb_detail_transaksi (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    id_transaksi INT NOT NULL,
    id_paket     INT NOT NULL,
    qty          DOUBLE NOT NULL,
    keterangan   TEXT,
    FOREIGN KEY (id_transaksi) REFERENCES tb_transaksi(id) ON DELETE CASCADE,
    FOREIGN KEY (id_paket)     REFERENCES tb_paket(id) ON DELETE RESTRICT
);

-- Default admin: username=admin, password=admin123
INSERT INTO tb_user (nama, username, password, role) VALUES
('Administrator', 'admin', 'admin123', 'admin');
