<?php
// ============================================================
// db.php - Lidhja me databazën (PDO Singleton)
// ============================================================

// Parandalon aksesin direkt
defined('BASE_PATH') or define('BASE_PATH', dirname(__DIR__));

class Database {

    // Instanca e vetme e klasës (Singleton)
    private static ?Database $instance = null;

    // Objekti PDO
    private PDO $pdo;

    // ---- Konstruktori privat (nuk mund të thirret nga jashtë) ----
    private function __construct() {
        $dsn = 'mysql:host=' . DB_HOST
             . ';dbname=' . DB_NAME
             . ';charset=' . DB_CHARSET;

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // Hedh exception për çdo gabim
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // Kthen array asociativ
            PDO::ATTR_EMULATE_PREPARES   => false,                   // Prepared statements reale
            PDO::ATTR_PERSISTENT         => false,                   // Nuk përdor lidhje persistente
        ];

        try {
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Nuk shfaqim detaje teknike në prodhim
            if (APP_ENV === 'development') {
                die('Gabim lidhje DB: ' . $e->getMessage());
            } else {
                die('Gabim i brendshëm i serverit. Ju lutemi provoni përsëri.');
            }
        }
    }

    // ---- Merr instancën e vetme ----
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    // ---- Merr objektin PDO direkt ----
    public function getConnection(): PDO {
        return $this->pdo;
    }

    // ---- Ekzekuto query me prepared statement ----
    // Kthe PDOStatement të ekzekutuar
    public function query(string $sql, array $params = []): PDOStatement {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            if (APP_ENV === 'development') {
                die('Gabim SQL: ' . $e->getMessage() . '<br>Query: ' . $sql);
            } else {
                die('Gabim gjatë përpunimit të kërkesës.');
            }
        }
    }

    // ---- Merr një rresht të vetëm ----
    public function fetchOne(string $sql, array $params = []): array|false {
        return $this->query($sql, $params)->fetch();
    }

    // ---- Merr të gjithë rreshtat ----
    public function fetchAll(string $sql, array $params = []): array {
        return $this->query($sql, $params)->fetchAll();
    }

    // ---- Ekzekuto INSERT dhe kthe ID-në e fundit ----
    public function insert(string $sql, array $params = []): string|false {
        $this->query($sql, $params);
        return $this->pdo->lastInsertId();
    }

    // ---- Ekzekuto UPDATE/DELETE dhe kthe numrin e rreshtave të prekur ----
    public function execute(string $sql, array $params = []): int {
        return $this->query($sql, $params)->rowCount();
    }

    // ---- Transaksion: fillo ----
    public function beginTransaction(): void {
        $this->pdo->beginTransaction();
    }

    // ---- Transaksion: konfirmo ----
    public function commit(): void {
        $this->pdo->commit();
    }

    // ---- Transaksion: anulo ----
    public function rollBack(): void {
        $this->pdo->rollBack();
    }

    // ---- Parandalon klonimin e instancës ----
    private function __clone() {}

    // ---- Parandalon deserializimin ----
    public function __wakeup() {
        throw new Exception('Deserializimi i Database nuk lejohet.');
    }
}

// ---- Funksion ndihmës global për akses të shpejtë ----
// Përdorim: $db = db(); $db->fetchOne(...)
function db(): Database {
    return Database::getInstance();
}
