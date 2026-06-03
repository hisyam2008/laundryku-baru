<?php
class UserModel {
    private $db;
    public function __construct($db) { $this->db = $db; }

    public function all() {
        return $this->db->query("SELECT u.*, o.nama as nama_outlet FROM tb_user u LEFT JOIN tb_outlet o ON u.id_outlet = o.id ORDER BY u.id ASC")->fetchAll();
    }
    public function findByUsername($username) {
        $s = $this->db->prepare("SELECT * FROM tb_user WHERE username = ?");
        $s->execute([$username]); return $s->fetch();
    }
    public function find($id) {
        $s = $this->db->prepare("SELECT * FROM tb_user WHERE id = ?");
        $s->execute([$id]); return $s->fetch();
    }
    public function create($nama, $username, $password, $role, $id_outlet) {
        $this->db->prepare("INSERT INTO tb_user (nama, username, password, role, id_outlet) VALUES (?,?,?,?,?)")
            ->execute([$nama, $username, password_hash($password, PASSWORD_DEFAULT), $role, $id_outlet]);
    }
    public function delete($id) {
        $this->db->prepare("DELETE FROM tb_user WHERE id = ?")->execute([$id]);
    }
    public function count() {
        return $this->db->query("SELECT COUNT(*) FROM tb_user")->fetchColumn();
    }
}
