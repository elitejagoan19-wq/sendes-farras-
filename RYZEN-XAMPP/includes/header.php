<?php
$user = current_user();
$flash = get_flash();
$pageTitle = $pageTitle ?? 'Dashboard';
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> · RYZEN</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css">
</head>
<body>
<div class="app-shell">
<aside class="sidebar" id="sidebar">
  <a class="brand" href="<?= BASE_URL ?>/index.php?page=dashboard">
    <span class="brand-mark">R</span><span><b>RYZEN</b><small>EDUCATION PORTAL</small></span>
  </a>
  <div class="side-caption">MENU UTAMA</div>
  <nav class="side-nav">
    <a class="<?= ($page ?? '')==='dashboard'?'active':'' ?>" href="<?= BASE_URL ?>/index.php?page=dashboard"><span>◫</span> Ringkasan</a>
    <a class="<?= ($page ?? '')==='announcements'?'active':'' ?>" href="<?= BASE_URL ?>/index.php?page=announcements"><span>▤</span> Pengumuman</a>
    <a class="<?= ($page ?? '')==='schedule'?'active':'' ?>" href="<?= BASE_URL ?>/index.php?page=schedule"><span>▦</span> Jadwal Pelajaran</a>
    <?php if (in_array($user['role'], ['admin','guru'], true)): ?>
      <a class="<?= ($page ?? '')==='attendance'?'active':'' ?>" href="<?= BASE_URL ?>/index.php?page=attendance"><span>✓</span> Absensi</a>
    <?php else: ?>
      <a class="<?= ($page ?? '')==='my-attendance'?'active':'' ?>" href="<?= BASE_URL ?>/index.php?page=my-attendance"><span>✓</span> Kehadiran</a>
    <?php endif; ?>
    <?php if ($user['role']==='admin'): ?>
      <div class="side-caption side-caption-gap">PENGELOLAAN</div>
      <a class="<?= ($page ?? '')==='students'?'active':'' ?>" href="<?= BASE_URL ?>/index.php?page=students"><span>♧</span> Data Siswa</a>
      <a class="<?= ($page ?? '')==='users'?'active':'' ?>" href="<?= BASE_URL ?>/index.php?page=users"><span>♙</span> Pengguna</a>
    <?php endif; ?>
    <?php if ($user['role']==='orangtua'): ?>
      <a class="<?= ($page ?? '')==='child'?'active':'' ?>" href="<?= BASE_URL ?>/index.php?page=child"><span>♧</span> Informasi Anak</a>
    <?php endif; ?>
  </nav>
  <div class="sidebar-bottom">
    <div class="help-card"><div class="help-icon">✦</div><b>Butuh bantuan?</b><p>Hubungi administrator sekolah untuk bantuan akun.</p></div>
    <a class="logout-link" href="<?= BASE_URL ?>/index.php?logout=1">↪ <span>Keluar akun</span></a>
  </div>
</aside>
<main class="main-area">
<header class="topbar">
  <button class="mobile-menu" id="mobileMenu" aria-label="Buka menu">☰</button>
  <div><div class="eyebrow">SMKN 1 TANJUNG SELOR</div><div class="top-title"><?= e($pageTitle) ?></div></div>
  <div class="top-user"><div class="avatar"><?= e(initials($user['name'])) ?></div><div class="top-user-text"><b><?= e($user['name']) ?></b><small><?= e(role_label($user['role'])) ?></small></div></div>
</header>
<section class="content-wrap">
<?php if ($flash): ?><div class="alert <?= e($flash['type']) ?>"><?= e($flash['message']) ?><button onclick="this.parentElement.remove()">×</button></div><?php endif; ?>
