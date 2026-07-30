<?php
require __DIR__ . '/config.php';
require dirname(__DIR__) . '/config/database.php';

$pageTitle = "Dashboard";
$topbarTitle = "PAN MBG Dashboard";

try {
    $connection = mbg_db_init();

    // Fetch main stats safely
    $sppg_res = $connection->query("SELECT COUNT(*) AS c FROM sppg_units WHERE status = 'active'")->fetch_assoc();
    $supplier_res = $connection->query("SELECT COUNT(*) AS c FROM suppliers WHERE status = 'active'")->fetch_assoc();
    $budget_res = $connection->query("SELECT SUM(allocated_amount - used_amount) AS c FROM budgets")->fetch_assoc();
    $alerts_res = $connection->query("SELECT COUNT(*) AS c FROM alerts WHERE severity = 'high'")->fetch_assoc();

    $date_filter = $connection->getDriver() === 'sqlite' ? "DATE(created_at) = DATE('now')" : "DATE(created_at) = CURDATE()";
    $transactions_res = $connection->query("SELECT COUNT(*) AS c FROM transactions WHERE {$date_filter}")->fetch_assoc();

    $stats = [
        'sppg' => (int)($sppg_res['c'] ?? 0),
        'supplier' => (int)($supplier_res['c'] ?? 0),
        'transactions' => (int)($transactions_res['c'] ?? 0),
        'budget' => (float)($budget_res['c'] ?? 0),
        'alerts' => (int)($alerts_res['c'] ?? 0),
    ];

    // Fetch top spending regions
    $top_spending_query = "SELECT region, SUM(used_amount) as total_spent FROM budgets GROUP BY region ORDER BY total_spent DESC LIMIT 5";
    $top_spending_result = $connection->query($top_spending_query);
    $top_spending = [];
    while ($row = $top_spending_result->fetch_assoc()) {
        $top_spending[] = $row;
    }
    $max_spent = !empty($top_spending) ? $top_spending[0]['total_spent'] : 0;

    // Fetch recent transactions
    $recent_transactions_query = "
        SELECT t.transaction_code, t.created_at, t.commodity, t.total_amount, t.status, s.name as supplier_name, u.name as sppg_name
        FROM transactions t
        JOIN suppliers s ON t.supplier_id = s.id
        JOIN sppg_units u ON t.sppg_id = u.id
        ORDER BY t.created_at DESC LIMIT 4";
    $recent_transactions_result = $connection->query($recent_transactions_query);

    // Fetch alerts for display
    $alerts_query = "SELECT title, description, severity, icon FROM alerts ORDER BY created_at DESC LIMIT 3";
    $alerts_result = $connection->query($alerts_query);
    $alerts_cards = [];
    while ($row = $alerts_result->fetch_assoc()) {
        $alerts_cards[] = $row;
    }

} catch (Throwable $e) {
    $stats = ['sppg' => 0, 'supplier' => 0, 'transactions' => 0, 'budget' => 0, 'alerts' => 0];
    $top_spending = [];
    $recent_transactions_result = null;
    $alerts_cards = [];
    error_log("Dashboard Error: " . $e->getMessage()); // Catat error ke log server
}

require __DIR__ . '/includes/header.php';
?>

<!-- Stat cards -->
<div class="grid grid-6 mb-24" style="grid-template-columns:repeat(4,1fr) 1.1fr 1fr 1fr;">
  <div class="stat-card">
    <div class="stat-label-plain">Total SPPG Aktif</div>
    <div class="stat-value"><?php echo number_format($stats['sppg'], 0, ',', '.'); ?></div>
    <div class="stat-tag stat-tag-up">&#8593;12%</div>
  </div>
  <div class="stat-card">
    <div class="stat-label-plain">Total Supplier</div>
    <div class="stat-value"><?php echo number_format($stats['supplier'], 0, ',', '.'); ?></div>
    <div class="stat-tag stat-tag-up">&#8593;8%</div>
  </div>
  <div class="stat-card">
    <div class="stat-label-plain">Transaksi Hari Ini</div>
    <div class="stat-value"><?php echo number_format($stats['transactions'], 0, ',', '.'); ?> <small>Target 95%</small></div>
  </div>
  <div class="stat-card">
    <div class="stat-label-plain">Nilai Transaksi (Rp)</div>
    <div class="stat-value">1.28B <small>Real-time</small></div>
  </div>
  <div class="stat-card">
    <div class="stat-label-plain">Anggaran Digunakan</div>
    <div class="stat-value">Rp 45.2T <small>YTD</small></div>
  </div>
  <div class="stat-card">
    <div class="stat-label-plain">Sisa Anggaran</div>
    <div class="stat-value">Rp <?php echo number_format($stats['budget'] / 1000000000000, 1, ',', '.'); ?>T <small>37.2%</small></div>
  </div>
  <div class="stat-card red">
    <div class="stat-label-plain" style="color:var(--red-text);">Indikasi Kecurangan</div>
    <div class="stat-value"><?php echo number_format($stats['alerts'], 0, ',', '.'); ?> <i data-lucide="alert-triangle" style="width:18px;height:18px;"></i></div>
  </div>
</div>

<!-- Chart row -->
<div class="grid grid-2 mb-24">
  <div class="card">
    <div class="card-head-row">
      <div class="card-title">Tren Transaksi Harian</div>
      <div class="dropdown"><span>7 Hari Terakhir</span><i data-lucide="chevron-down"></i></div>
    </div>
    <div style="height:260px;"><canvas id="trenTransaksiChart"></canvas></div>
  </div>
  <div class="card">
    <div class="card-title mb-20">Pengeluaran Teratas (Provinsi)</div>
    <?php if (empty($top_spending)): ?>
      <p style="text-align:center; color: #666; padding: 20px 0;">Data pengeluaran belum tersedia.</p>
    <?php else: ?>
      <?php foreach ($top_spending as $item): ?>
        <div class="bar-list-item">
          <div class="bar-list-top"><b><?php echo htmlspecialchars($item['region']); ?></b><span>Rp <?php echo number_format($item['total_spent'] / 1000000, 0, ',', '.'); ?>M</span></div>
          <?php $percentage = ($max_spent > 0) ? ($item['total_spent'] / $max_spent) * 100 : 0; ?>
          <?php echo progress_bar($percentage, 'dark'); ?>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<!-- Map + donut row -->
<div class="grid grid-2 mb-24">
  <div class="card" style="padding:0;overflow:hidden;">
    <div style="padding:22px 22px 0;">
      <div class="card-head-row" style="margin-bottom:12px;">
        <div class="card-title">Sebaran Nasional &amp; Intensitas Audit</div>
        <div class="map-legend" style="position:static;background:transparent;padding:0;">
          <span><i style="background:#bcd4fb"></i>Rendah</span>
          <span><i style="background:var(--navy-900)"></i>Tinggi</span>
        </div>
      </div>
    </div>
    <div class="map-box" style="border-radius:0;min-height:340px;">
      <div class="map-caption">
        <strong>Peta Intensitas Audit Nasional - Indonesia</strong>
        <span>Dashboard Pusat Audit Nasional MBG</span>
      </div>
      <img src="<?php echo asset_url('assets/img/map-placeholder.svg'); ?>" alt="Peta Indonesia">
      <div class="map-legend-bottom">
        <span>Intensitas Audit</span>
        <span><i class="dot" style="background:#bcd4fb"></i>Low &nbsp; <i class="dot" style="background:var(--navy-900)"></i>High</span>
      </div>
    </div>
  </div>
  <div class="card" style="display:flex;flex-direction:column;">
    <div class="card-title mb-20">Volume Komoditas Utama</div>
    <div class="donut-wrap" style="height:210px;">
      <canvas id="volumeKomoditasChart"></canvas>
      <div class="donut-center"><b>850K</b><span>TON TOTAL</span></div>
    </div>
    <div class="legend-grid">
      <div class="legend-grid-item"><i style="background:var(--navy-900)"></i>Beras (35%)</div>
      <div class="legend-grid-item"><i style="background:var(--blue-500)"></i>Telur (25%)</div>
      <div class="legend-grid-item"><i style="background:#7fb0f5"></i>Ayam (20%)</div>
      <div class="legend-grid-item"><i style="background:#f5b95c"></i>Susu (12%)</div>
      <div class="legend-grid-item"><i style="background:#cbd5e1"></i>Sayuran (8%)</div>
    </div>
  </div>
</div>

<!-- Peringatan Deteksi Kecurangan -->
<h3 style="display:flex;align-items:center;gap:8px;font-size:16px;margin-bottom:14px;color:var(--text-900);">
  <i data-lucide="siren" style="width:18px;height:18px;color:var(--red-solid);"></i>
  Peringatan Deteksi Kecurangan (Audit AI)
</h3>
<div class="grid grid-3 mb-24">
  <?php
    $severity_map = [
        'high' => ['color' => 'red', 'label' => 'RESIKO TINGGI'],
        'medium' => ['color' => 'amber', 'label' => 'RESIKO MENENGAH'],
        'low' => ['color' => 'blue', 'label' => 'RESIKO RENDAH'],
    ];
    foreach ($alerts_cards as $alert):
      $style = $severity_map[$alert['severity']] ?? ['color' => 'neutral', 'label' => 'INFO'];
  ?>
    <div class="insight-card <?php echo $style['color']; ?>">
      <div class="insight-icon"><i data-lucide="<?php echo htmlspecialchars($alert['icon']); ?>"></i></div>
      <div>
        <h4><?php echo htmlspecialchars($alert['title']); ?></h4>
        <p><?php echo htmlspecialchars($alert['description']); ?></p>
        <div class="insight-tag"><?php echo $style['label']; ?></div>
      </div>
    </div>
  <?php endforeach; ?>
  <?php if (empty($alerts_cards)): ?>
    <div class="card" style="grid-column: 1 / -1; text-align: center; padding: 40px;">
        <p>Tidak ada peringatan kecurangan yang aktif saat ini.</p>
    </div>
  <?php endif; ?>
</div>

<!-- Transaksi Terbaru -->
<div class="card mb-24" style="padding:0;">
  <div class="card-head-row" style="padding:22px 22px 0;">
    <div class="card-title">Transaksi Terbaru</div>
    <span class="link-btn">Lihat Semua &rsaquo;</span>
  </div>
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th>ID Transaksi</th><th>Waktu</th><th>SPPG</th><th>Supplier</th><th>Komoditas</th><th>Total Pembelian</th><th>Status</th><th></th>
        </tr>
      </thead>
      <tbody>
        <?php
          $status_map = ['Selesai' => 'success', 'Diproses' => 'info', 'Dibatalkan' => 'danger'];
          if ($recent_transactions_result):
            while ($row = $recent_transactions_result->fetch_assoc()):
        ?>
          <tr>
            <td class="cell-strong"><?php echo htmlspecialchars($row['transaction_code']); ?></td>
            <td><?php echo date('H:i', strtotime($row['created_at'])); ?> WIB</td>
            <td><?php echo htmlspecialchars($row['sppg_name']); ?></td>
            <td><?php echo htmlspecialchars($row['supplier_name']); ?></td>
            <td><?php echo htmlspecialchars($row['commodity']); ?></td>
            <td class="cell-money">Rp <?php echo number_format($row['total_amount'], 0, ',', '.'); ?></td>
            <td><?php echo badge(strtoupper($row['status']), $status_map[$row['status']] ?? 'neutral'); ?></td>
            <td><span class="action-eye"><i data-lucide="eye"></i></span></td>
          </tr>
        <?php endwhile; endif; ?>
      </tbody>
    </table>
  </div>
  <div style="height:6px;"></div>
</div>

<div class="grid grid-2">
  <div class="card">
    <div class="card-title mb-20">Supplier Menunggu Verifikasi</div>
    <div class="verify-row">
      <div class="verify-icon"><i data-lucide="landmark"></i></div>
      <div style="flex:1;">
        <div class="verify-name">CV. Berkah Tani</div>
        <div class="verify-loc">Cianjur, Jawa Barat</div>
      </div>
      <button class="btn btn-primary btn-sm">Verifikasi</button>
    </div>
    <div class="verify-row">
      <div class="verify-icon"><i data-lucide="landmark"></i></div>
      <div style="flex:1;">
        <div class="verify-name">UD. Jaya Lautan</div>
        <div class="verify-loc">Makassar, Sulsel</div>
      </div>
      <button class="btn btn-primary btn-sm">Verifikasi</button>
    </div>
  </div>
  <div class="card">
    <div class="card-title mb-20">Aktivitas Sistem Terbaru</div>
    <div class="timeline-item">
      <div class="timeline-dot" style="background:var(--navy-900);"></div>
      <div><p>10:45 WIB</p><span>Audit bulanan SPPG Wilayah Timur telah dimulai.</span></div>
    </div>
    <div class="timeline-item">
      <div class="timeline-dot" style="background:var(--red-solid);"></div>
      <div><p>10:12 WIB</p><span>Sistem AI mendeteksi lonjakan harga telur di 12 SPPG secara serentak.</span></div>
    </div>
    <div class="timeline-item">
      <div class="timeline-dot" style="background:var(--blue-500);"></div>
      <div><p>09:30 WIB</p><span>Laporan anggaran Q2 MBG Nasional telah dipublikasikan ke panel pengawas.</span></div>
    </div>
  </div>
</div>

<?php
if (isset($connection)) {
    mbg_db_close($connection);
}

require __DIR__ . '/includes/footer.php';
?>
