<?php

class Database {
    private $host = '127.0.0.1';
    private $db   = 'Laundry_ku';
    private $user = 'root';
    private $pass = '';
    private $charset = 'utf8mb4';

    private $pdo;

    public function connect() {
        if ($this->pdo === null) {
            $dsn = "mysql:host={$this->host};dbname={$this->db};charset={$this->charset}";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ];
            try {
                $this->pdo = new PDO($dsn, $this->user, $this->pass, $options);
            } catch (PDOException $e) {
                die("<div style='font-family:monospace;background:#fff3cd;padding:20px;margin:20px;border:1px solid #ffc107;border-radius:8px;'>
                    <b>Database Error:</b><br>" . $e->getMessage() . "
                </div>");
            }
        }
        return $this->pdo;
    }
}
