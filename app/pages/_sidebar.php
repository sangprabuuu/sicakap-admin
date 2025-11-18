<?php
// Sidebar sederhana yang menggunakan APP_URL untuk asset path
$base = rtrim(APP_URL, '/');
?>
<nav class="sidebar">
  <div class="sidebar-top">
    <a class="logo-link" href="<?= h($base) ?>">
      <?php
      // Cek apakah file logo.png ada dan tidak kosong
      $logo_path = __DIR__ . '/../../public/assets/images/logo.png';
      $use_svg = !file_exists($logo_path) || filesize($logo_path) == 0;
      $logo_src = $use_svg ? '/assets/images/logo.svg' : '/assets/images/logo.png';
      ?>
      <img src="<?= h($base . $logo_src) ?>" alt="SiCakap Logo" class="sidebar-logo">
      <div class="brand-text">
        <span class="brand-name">SiCakap</span>
        <span class="brand-tagline">Admin Panel</span>
      </div>
    </a>
  </div>
  <ul class="menu">
    <li><a href="?p=dashboard">Halaman Utama</a></li>
    <li class="dropdown">
      <a href="#" class="dropdown-toggle">Buat Surat</a>
      <ul class="dropdown-menu">
        <li><a href="?p=sppd">Surat SPPD</a></li>
        <li><a href="?p=undangan">Surat Undangan</a></li>
      </ul>
    </li>
    <li><a href="?p=requests">Pengajuan Surat</a></li>
    <li><a href="?p=issued">Surat Selesai</a></li>
    <li><a href="?p=reports">Laporan</a></li>
    <li><a href="?p=logout" class="logout-link">Log Out</a></li>
  </ul>
  
  <style>
    .dropdown {
      position: relative;
    }
    .dropdown-toggle::after {
      content: ' ▼';
      font-size: 10px;
      margin-left: 5px;
    }
    .dropdown-menu {
      display: none;
      list-style: none;
      padding-left: 20px;
      margin: 5px 0;
    }
    .dropdown:hover .dropdown-menu {
      display: block;
    }
    .dropdown-menu li {
      margin: 5px 0;
    }
    .dropdown-menu a {
      font-size: 14px;
      opacity: 0.9;
    }
    .dropdown-menu a:hover {
      opacity: 1;
      padding-left: 5px;
      transition: padding-left 0.2s;
    }
  </style>
</nav>