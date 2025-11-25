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
    <li><a href="?p=requests">Pengajuan Dokumen</a></li>
    <li><a href="?p=issued">Dokumen Selesai</a></li>
    <li><a href="?p=reports">Laporan</a></li>
    <li><a href="?p=logout" class="logout-link">Log Out</a></li>
  </ul>
  
  <style>
    .dropdown {
      position: relative;
    }
    .dropdown-toggle {
      display: block;
      cursor: pointer;
    }
    .dropdown-toggle::after {
      content: ' ▼';
      font-size: 10px;
      margin-left: 5px;
      transition: transform 0.3s;
    }
    .dropdown.active .dropdown-toggle::after {
      transform: rotate(180deg);
    }
    .dropdown-menu {
      display: none;
      list-style: none;
      padding-left: 20px;
      margin: 5px 0;
      background: rgba(255, 255, 255, 0.05);
      border-radius: 4px;
      padding: 8px 0 8px 20px;
      max-height: 0;
      overflow: hidden;
      transition: max-height 0.3s ease;
    }
    .dropdown.active .dropdown-menu {
      display: block;
      max-height: 200px;
    }
    .dropdown-menu li {
      margin: 5px 0;
    }
    .dropdown-menu a {
      font-size: 14px;
      opacity: 0.9;
      display: block;
      padding: 5px 10px;
      transition: all 0.2s;
    }
    .dropdown-menu a:hover {
      opacity: 1;
      padding-left: 15px;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 4px;
    }
  </style>
  
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const dropdown = document.querySelector('.dropdown');
      const dropdownToggle = dropdown.querySelector('.dropdown-toggle');
      
      dropdownToggle.addEventListener('click', function(e) {
        e.preventDefault();
        dropdown.classList.toggle('active');
      });
    });
  </script>
</nav>