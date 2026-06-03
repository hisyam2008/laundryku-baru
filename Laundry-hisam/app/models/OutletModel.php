<?php
class OutletModel {
    private $db;
    public function __construct($db) { $this->db = $db; }

    public function all() {
        return $this->db->query("SELECT * FROM tb_outlet ORDER BY id ASC")->fetchAll();
    }
    public function find($id) {
        $s = $this->db->prepare("SELECT * FROM tb_outlet WHERE id = ?");
        $s->execute([$id]); return $s->fetch();
    }
    public function create($nama, $alamat, $tlp) {
        $this->db->prepare("INSERT INTO tb_outlet (nama, alamat, tlp) VALUES (?,?,?)")->execute([$nama, $alamat, $tlp]);
    }
    public function update($id, $nama, $alamat, $tlp) {
        $this->db->prepare("UPDATE tb_outlet SET nama=?, alamat=?, tlp=? WHERE id=?")->execute([$nama, $alamat, $tlp, $id]);
    }
    public function delete($id) {
        $this->db->prepare("DELETE FROM tb_outlet WHERE id = ?")->execute([$id]);
    }
    public function count() {
        return $this->db->query("SELECT COUNT(*) FROM tb_outlet")->fetchColumn();
    }
    public function latest($limit = 5) {
        return $this->db->query("SELECT * FROM tb_outlet ORDER BY id DESC LIMIT $limit")->fetchAll();
    }
}
