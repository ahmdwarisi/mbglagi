<?php

header('Content-Type: text/plain; charset=utf-8');

require __DIR__ . '/config/database.php';

echo "Memulai proses instalasi...\n";

try {
    // 1. Buat database jika belum ada (hanya untuk MySQL)
    echo "Mencoba membuat database '{$DB_NAME}' jika belum ada...\n";
    $connection = mbg_db_bootstrap();
    echo "Koneksi berhasil menggunakan driver: " . $connection->getDriver() . "\n\n";

    // 2. Definisikan struktur tabel
    $driver = $connection->getDriver();
    $pk = $driver === 'sqlite' ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'INT AUTO_INCREMENT PRIMARY KEY';

    $tables = [];
    $tables[] = "CREATE TABLE IF NOT EXISTS users (
        id {$pk},
        role VARCHAR(50) NOT NULL,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    $tables[] = "CREATE TABLE IF NOT EXISTS suppliers (
        id {$pk},
        name VARCHAR(150) NOT NULL,
        category VARCHAR(100) NOT NULL,
        region VARCHAR(100) NOT NULL,
        status VARCHAR(50) NOT NULL DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    $tables[] = "CREATE TABLE IF NOT EXISTS sppg_units (
        id {$pk},
        name VARCHAR(150) NOT NULL,
        region VARCHAR(100) NOT NULL,
        status VARCHAR(50) NOT NULL DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    $tables[] = "CREATE TABLE IF NOT EXISTS transactions (
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

    $tables[] = "CREATE TABLE IF NOT EXISTS procurement_orders (
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

    $tables[] = "CREATE TABLE IF NOT EXISTS budgets (
        id {$pk},
        name VARCHAR(150) NOT NULL,
        allocated_amount DECIMAL(15,2) NOT NULL,
        used_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
        region VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    $tables[] = "CREATE TABLE IF NOT EXISTS alerts (
        id {$pk},
        title VARCHAR(150) NOT NULL,
        description TEXT NOT NULL,
        severity VARCHAR(50) NOT NULL,
        icon VARCHAR(50) DEFAULT 'alert-triangle',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    echo "Membuat tabel...\n";
    foreach ($tables as $query) {
        $connection->query($query);
    }
    echo "Semua tabel berhasil dibuat atau sudah ada.\n\n";

    // 3. Isi data awal (seeding)
    $insertIgnore = $driver === 'sqlite' ? 'INSERT OR IGNORE' : 'INSERT IGNORE';

    $seeds = [];
    $seeds[] = "{$insertIgnore} INTO users (role, name, email, password) VALUES
        ('audit', 'Auditor Utama', 'audit@example.com', '$2y$10$7nBt7gY1qvNQ5Z2q4SPP4uWVaKj9KkR7zBt4VvjW9Z12nqV9IY8kO'),
        ('sppg', 'Admin SPPG', 'sppg@example.com', '$2y$10$7nBt7gY1qvNQ5Z2q4SPP4uWVaKj9KkR7zBt4VvjW9Z12nqV9IY8kO'),
        ('supplier', 'Supplier Mandiri', 'supplier@example.com', '$2y$10$7nBt7gY1qvNQ5Z2q4SPP4uWVaKj9KkR7zBt4VvjW9Z12nqV9IY8kO')";

    $seeds[] = "{$insertIgnore} INTO suppliers (name, category, region, status) VALUES
        ('CV. Tani Sejahtera', 'Beras', 'Surabaya', 'active'),
        ('UD. Jaya Lautan', 'Ikan', 'Makassar', 'active'),
        ('PT. Agro Maju', 'Telur', 'Bandung', 'active')";

    $seeds[] = "{$insertIgnore} INTO sppg_units (name, region, status) VALUES
        ('SPPG Surabaya Barat', 'Surabaya', 'active'),
        ('SPPG Medan Baru', 'Medan', 'active'),
        ('SPPG Jakarta Pusat', 'Jakarta', 'active')";

    $seeds[] = "{$insertIgnore} INTO transactions (transaction_code, sppg_id, supplier_id, commodity, total_amount, status) VALUES
        ('MBG-772910', 1, 1, 'Beras Premium', 12400000, 'Selesai'),
        ('MBG-772911', 2, 2, 'Ikan Segar', 8750000, 'Diproses'),
        ('MBG-772912', 3, 3, 'Susu UHT', 45000000, 'Dibatalkan')";

    $seeds[] = "{$insertIgnore} INTO procurement_orders (order_code, supplier_id, sppg_id, commodity, quantity_kg, total_amount, status) VALUES
        ('PO-1001', 1, 1, 'Beras Medium', 500, 6250000, 'pending'),
        ('PO-1002', 2, 2, 'Ikan Tuna', 300, 4500000, 'processed'),
        ('PO-1003', 3, 3, 'Telur Ayam', 800, 9100000, 'shipped')";

    $seeds[] = "{$insertIgnore} INTO budgets (name, allocated_amount, used_amount, region) VALUES
        ('Anggaran Surabaya', 50000000000, 18000000000, 'Surabaya'),
        ('Anggaran Medan', 30000000000, 12000000000, 'Medan'),
        ('Anggaran Jakarta', 45000000000, 22000000000, 'Jakarta')";

    $seeds[] = "{$insertIgnore} INTO alerts (title, description, severity, icon) VALUES
        ('Harga Melebihi Acuan', 'Telur Ayam di SPPG Bandung Utara naik 45% dalam 2 jam.', 'high', 'trending-up'),
        ('Indikasi Transaksi Ganda', '3 ID Transaksi serupa ditemukan pada Supplier Tani Makmur.', 'medium', 'copy'),
        ('Anomali Lokasi Pengiriman', 'Logistik SPPG Makassar terdeteksi di luar rute operasional.', 'high', 'map-pin-off')";

    echo "Mengisi data awal (seed)...\n";
    foreach ($seeds as $query) {
        $connection->query($query);
    }
    echo "Data awal berhasil dimasukkan.\n\n";

    $connection->close();

    echo "==================================================\n";
    echo "INSTALASI SELESAI.\n";
    echo "Silakan hapus file 'install.php' ini demi keamanan.\n";
    echo "==================================================\n";

} catch (Throwable $e) {
    echo "\n\n!!! TERJADI ERROR !!!\n";
    echo "Pesan: " . $e->getMessage() . "\n";
}

?>