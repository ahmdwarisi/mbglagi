<?php
/**
 * Konfigurasi database untuk PAN MBG Dashboard
 * Menyediakan koneksi MySQL/MariaDB yang kompatibel dengan XAMPP/Laragon.
 * Jika MySQL tidak tersedia, sistem otomatis beralih ke SQLite lokal.
 */

$DB_HOST = '127.0.0.1';
$DB_PORT = 3306;
$DB_NAME = 'mbg_audit_db';
$DB_USER = 'root';
$DB_PASS = '';
$DB_SQLITE_PATH = dirname(__DIR__) . '/storage/mbg_dashboard.sqlite';

class MBGResult {
    private $pdoStatement;
    private $mysqliResult;

    public function __construct($pdoStatement = null, $mysqliResult = null)
    {
        $this->pdoStatement = $pdoStatement;
        $this->mysqliResult = $mysqliResult;
    }

    public function fetch_assoc()
    {
        if ($this->pdoStatement !== null) {
            $row = $this->pdoStatement->fetch(PDO::FETCH_ASSOC);
            return $row === false ? [] : $row;
        }

        if ($this->mysqliResult !== null) {
            return $this->mysqliResult->fetch_assoc();
        }

        return [];
    }
}

class MBGConnection {
    private $mysqli;
    private $pdo;
    private $driver;

    public function __construct($mysqli = null, $pdo = null, $driver = 'mysqli')
    {
        $this->mysqli = $mysqli;
        $this->pdo = $pdo;
        $this->driver = $driver;
    }

    public function query($sql)
    {
        if ($this->pdo !== null) {
            $stmt = $this->pdo->query($sql);
            if ($stmt === false) {
                $errorInfo = $this->pdo->errorInfo();
                throw new RuntimeException('Query gagal: ' . ($errorInfo[2] ?? 'unknown'));
            }
            return new MBGResult($stmt, null);
        }

        if ($this->mysqli !== null) {
            $result = $this->mysqli->query($sql);
            if ($result === false) {
                throw new RuntimeException('Query gagal: ' . $this->mysqli->error);
            }
            return new MBGResult(null, $result);
        }

        throw new RuntimeException('Koneksi database belum siap.');
    }

    public function close()
    {
        if ($this->mysqli !== null) {
            $this->mysqli->close();
        }

        $this->pdo = null;
    }

    public function getDriver()
    {
        return $this->driver;
    }
}

function mbg_db_connect() {
    global $DB_HOST, $DB_PORT, $DB_NAME, $DB_USER, $DB_PASS, $DB_SQLITE_PATH;

    if (class_exists('mysqli') && extension_loaded('mysqli')) {
        $mysqli = @new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $DB_PORT);
        if ($mysqli && $mysqli->connect_errno === 0) {
            $mysqli->set_charset('utf8mb4');
            return new MBGConnection($mysqli, null, 'mysqli');
        }
    }

    if (extension_loaded('pdo_sqlite')) {
        $dir = dirname($DB_SQLITE_PATH);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $pdo = new PDO('sqlite:' . $DB_SQLITE_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return new MBGConnection(null, $pdo, 'sqlite');
    }

    throw new RuntimeException('Koneksi database gagal dan driver SQLite tidak tersedia.');
}

function mbg_db_bootstrap() {
    global $DB_HOST, $DB_PORT, $DB_NAME, $DB_USER, $DB_PASS;

    if (class_exists('mysqli') && extension_loaded('mysqli')) {
        $admin = @new mysqli($DB_HOST, $DB_USER, $DB_PASS, '', $DB_PORT);
        if ($admin && $admin->connect_errno === 0) {
            $admin->query("CREATE DATABASE IF NOT EXISTS `{$DB_NAME}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $admin->close();
            return mbg_db_connect();
        }
    }

    return mbg_db_connect();
}

function mbg_db_init() {
    return mbg_db_connect();
}

function mbg_db_close($connection) {
    if ($connection instanceof MBGConnection) {
        $connection->close();
    } elseif ($connection instanceof mysqli) {
        $connection->close();
    }
}
