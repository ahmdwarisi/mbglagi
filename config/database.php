<?php
/**
 * Konfigurasi database untuk PAN MBG Dashboard
 * Menyediakan koneksi MySQL/MariaDB yang kompatibel dengan XAMPP/Laragon.
 * Jika MySQL tidak tersedia, sistem otomatis beralih ke SQLite lokal.
 */

$DB_HOST = '127.0.0.1';
$DB_PORT = 3306;
$DB_NAME = 'mbg_dashboard';
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
    $connection = mbg_db_bootstrap();
    $driver = $connection->getDriver();
    $pk = $driver === 'sqlite' ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'INT AUTO_INCREMENT PRIMARY KEY';
    $insertIgnore = $driver === 'sqlite' ? 'INSERT OR IGNORE' : 'INSERT IGNORE';

    $sql = [];
    $sql[] = "CREATE TABLE IF NOT EXISTS users (
        id {$pk},
        role VARCHAR(50) NOT NULL,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    $sql[] = "CREATE TABLE IF NOT EXISTS suppliers (
        id {$pk},
        name VARCHAR(150) NOT NULL,
        category VARCHAR(100) NOT NULL,
        region VARCHAR(100) NOT NULL,
        status VARCHAR(50) NOT NULL DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    $sql[] = "CREATE TABLE IF NOT EXISTS sppg_units (
        id {$pk},
        name VARCHAR(150) NOT NULL,
        region VARCHAR(100) NOT NULL,
        status VARCHAR(50) NOT NULL DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    $sql[] = "CREATE TABLE IF NOT EXISTS transactions (
        id {$pk},
        transaction_code VARCHAR(100) UNIQUE NOT NULL,
        sppg_id INT NOT NULL,
        supplier_id INT NOT NULL,
        commodity VARCHAR(100) NOT NULL,
        total_amount DECIMAL(15,2) NOT NULL,
        status VARCHAR(50) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (sppg_id) REFERENCES sppg_units(id),
        FOREIGN KEY (supplier_id) REFERENCES suppliers(id)
    )";

    $sql[] = "CREATE TABLE IF NOT EXISTS procurement_orders (
        id {$pk},
        order_code VARCHAR(100) UNIQUE NOT NULL,
        supplier_id INT NOT NULL,
        sppg_id INT NOT NULL,
        commodity VARCHAR(100) NOT NULL,
        quantity_kg DECIMAL(12,2) NOT NULL,
        total_amount DECIMAL(15,2) NOT NULL,
        status VARCHAR(50) NOT NULL DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
        FOREIGN KEY (sppg_id) REFERENCES sppg_units(id)
    )";

    $sql[] = "CREATE TABLE IF NOT EXISTS budgets (
        id {$pk},
        name VARCHAR(150) NOT NULL,
        allocated_amount DECIMAL(15,2) NOT NULL,
        used_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
        region VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    $sql[] = "CREATE TABLE IF NOT EXISTS alerts (
        id {$pk},
        title VARCHAR(150) NOT NULL,
        description TEXT NOT NULL,
        severity VARCHAR(50) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    foreach ($sql as $query) {
        $connection->query($query);
    }

    $seed = [];
    $seed[] = "{$insertIgnore} INTO users (role, name, email, password) VALUES
        ('audit', 'Auditor Utama', 'audit@example.com', '$2y$10$7nBt7gY1qvNQ5Z2q4SPP4uWVaKj9KkR7zBt4VvjW9Z12nqV9IY8kO'),
        ('sppg', 'Admin SPPG', 'sppg@example.com', '$2y$10$7nBt7gY1qvNQ5Z2q4SPP4uWVaKj9KkR7zBt4VvjW9Z12nqV9IY8kO'),
        ('supplier', 'Supplier Mandiri', 'supplier@example.com', '$2y$10$7nBt7gY1qvNQ5Z2q4SPP4uWVaKj9KkR7zBt4VvjW9Z12nqV9IY8kO')";

    $seed[] = "{$insertIgnore} INTO suppliers (name, category, region, status) VALUES
        ('CV. Tani Sejahtera', 'Beras', 'Surabaya', 'active'),
        ('UD. Jaya Lautan', 'Ikan', 'Makassar', 'active'),
        ('PT. Agro Maju', 'Telur', 'Bandung', 'active')";

    $seed[] = "{$insertIgnore} INTO sppg_units (name, region, status) VALUES
        ('SPPG Surabaya Barat', 'Surabaya', 'active'),
        ('SPPG Medan Baru', 'Medan', 'active'),
        ('SPPG Jakarta Pusat', 'Jakarta', 'active')";

    $seed[] = "{$insertIgnore} INTO transactions (transaction_code, sppg_id, supplier_id, commodity, total_amount, status) VALUES
        ('MBG-772910', 1, 1, 'Beras Premium', 12400000, 'Selesai'),
        ('MBG-772911', 2, 2, 'Ikan Segar', 8750000, 'Diproses'),
        ('MBG-772912', 3, 3, 'Susu UHT', 45000000, 'Dibatalkan')";

    $seed[] = "{$insertIgnore} INTO procurement_orders (order_code, supplier_id, sppg_id, commodity, quantity_kg, total_amount, status) VALUES
        ('PO-1001', 1, 1, 'Beras Medium', 500, 6250000, 'pending'),
        ('PO-1002', 2, 2, 'Ikan Tuna', 300, 4500000, 'processed'),
        ('PO-1003', 3, 3, 'Telur Ayam', 800, 9100000, 'shipped')";

    $seed[] = "{$insertIgnore} INTO budgets (name, allocated_amount, used_amount, region) VALUES
        ('Anggaran Surabaya', 50000000000, 18000000000, 'Surabaya'),
        ('Anggaran Medan', 30000000000, 12000000000, 'Medan'),
        ('Anggaran Jakarta', 45000000000, 22000000000, 'Jakarta')";

    $seed[] = "{$insertIgnore} INTO alerts (title, description, severity) VALUES
        ('Harga melebihi acuan', 'Telur ayam mengalami lonjakan harga', 'high'),
        ('Transaksi ganda', 'Terdapat duplikasi transaksi pada supplier tertentu', 'medium'),
        ('Anomali lokasi', 'Logistik terdeteksi di luar lintasan', 'high')";

    foreach ($seed as $query) {
        $connection->query($query);
    }

    return $connection;
}

function mbg_db_close($connection) {
    if ($connection instanceof MBGConnection) {
        $connection->close();
    } elseif ($connection instanceof mysqli) {
        $connection->close();
    }
}
