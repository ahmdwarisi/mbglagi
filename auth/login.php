<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - Sistem Monitoring dan Pengadaan MBG Nasional</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/auth.css">
</head>
<body>

<div class="auth-wrap">
  <div class="auth-card">

    <div class="auth-left">
      <div class="auth-logo"><i class="fa-solid fa-chart-line"></i></div>

      <h1>Sistem Monitoring dan Pengadaan MBG Nasional</h1>
      <p class="sub">Platform Terintegrasi Pengadaan dan Pengawasan Program Makan Bergizi Gratis</p>

      <form action="#" method="post">
        <div class="field">
          <div class="field-row"><label for="email">Email</label></div>
          <div class="input-wrap">
            <i class="fa-regular fa-envelope left-icon"></i>
            <input type="email" id="email" name="email" placeholder="nama@email.com" required>
          </div>
        </div>

        <div class="field">
          <div class="field-row">
            <label for="password">Kata Sandi</label>
            <a href="#" class="fwd">Lupa Kata Sandi?</a>
          </div>
          <div class="input-wrap">
            <i class="fa-solid fa-lock left-icon"></i>
            <input type="password" id="password" name="password" placeholder="••••••••" required>
            <i class="fa-regular fa-eye right-icon" onclick="togglePassword()"></i>
          </div>
        </div>

        <div class="checkbox-row">
          <input type="checkbox" id="remember" name="remember">
          <label for="remember">Ingat Saya</label>
        </div>

        <button type="submit" class="btn-primary">Masuk <i class="fa-solid fa-arrow-right-to-bracket"></i></button>
      </form>

      <hr class="or-divider">

      <p class="center-text">Belum memiliki akun Supplier?</p>
      <a href="registrasi-supplier.php" class="btn-outline-full" style="display:block;text-align:center;">Daftar Sebagai Supplier</a>
    </div>

    <div class="auth-right">
      <div class="illust-card">
        <div class="illust-top">
          <span style="width:18px;height:18px;border-radius:5px;background:#dc2626;display:inline-flex;align-items:center;justify-content:center;color:#fff;font-size:9px;"><i class="fa-solid fa-database"></i></span>
          SATU DATA INDONESIA
        </div>
        <svg class="illust-img" viewBox="0 0 560 300" xmlns="http://www.w3.org/2000/svg">
          <rect width="560" height="300" rx="8" fill="#eef4fb"/>
          <rect x="330" y="60" width="130" height="150" rx="6" fill="#dce9f7"/>
          <rect x="345" y="75" width="20" height="20" fill="#9db8d6"/>
          <rect x="375" y="75" width="20" height="20" fill="#9db8d6"/>
          <rect x="405" y="75" width="20" height="20" fill="#9db8d6"/>
          <rect x="345" y="105" width="20" height="20" fill="#c3d6ec"/>
          <rect x="375" y="105" width="20" height="20" fill="#c3d6ec"/>
          <rect x="405" y="105" width="20" height="20" fill="#c3d6ec"/>
          <circle cx="405" cy="55" r="18" fill="#fbbf24"/>
          <rect x="60" y="140" width="60" height="45" rx="4" fill="#cbd9ec"/>
          <rect x="130" y="120" width="60" height="65" rx="4" fill="#b7cbe6"/>
          <circle cx="230" cy="150" r="16" fill="#c9d8ec"/>
          <rect x="205" y="170" width="50" height="55" rx="6" fill="#0f2a4a"/>
          <circle cx="280" cy="150" r="16" fill="#c9d8ec"/>
          <rect x="255" y="170" width="50" height="55" rx="6" fill="#1d5fd6"/>
          <rect x="470" y="150" width="40" height="60" rx="4" fill="#dfeadd"/>
          <rect x="0" y="230" width="560" height="70" fill="#dce9f7"/>
          <rect x="150" y="215" width="70" height="30" rx="4" fill="#1d5fd6"/>
          <rect x="240" y="220" width="60" height="25" rx="4" fill="#16a34a"/>
          <circle cx="470" cy="200" r="22" fill="#f4c98a"/>
          <rect x="450" y="222" width="40" height="35" rx="4" fill="#f59e0b"/>
        </svg>
      </div>
      <div class="floating-chip chip-top">
        <div class="fc-icon"><i class="fa-solid fa-arrow-trend-up"></i></div>
        <div><div class="fc-title">Monitoring Real-time</div><div class="fc-sub">Data Pengadaan Nasional</div></div>
      </div>
      <div class="floating-chip chip-bottom">
        <div class="fc-icon"><i class="fa-solid fa-shield-halved"></i></div>
        <div><div class="fc-title">Kualitas Terjamin</div><div class="fc-sub">Standar Gizi Terpadu</div></div>
      </div>
    </div>

  </div>
</div>

<script>
function togglePassword(){
  const pw = document.getElementById('password');
  const icon = document.querySelector('.right-icon');
  if (pw.type === 'password'){ pw.type = 'text'; icon.classList.replace('fa-eye','fa-eye-slash'); }
  else { pw.type = 'password'; icon.classList.replace('fa-eye-slash','fa-eye'); }
}
</script>
</body>
</html>
