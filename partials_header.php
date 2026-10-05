<?php require_once __DIR__.'/config/config.php'; $current = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php'); ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#6d28d9"><meta name="description" content="SAM & Associates — Accounts, Tax and Compliance support in Faridabad.">
<title><?= e($title ?? 'SAM & Associates') ?></title>
<script>document.documentElement.classList.add('js');</script>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(base_url('assets/style.css')) ?>">
</head>
<body>
<div class="progress"></div>
<div class="page-loader" aria-hidden="true"><span></span></div>
<header class="site-header">
  <div class="container nav">
    <a class="brand" href="<?=e(base_url('index.php'))?>"><img src="<?=e(base_url('assets/logo.png'))?>" alt="SAM & Associates"></a>
    <button class="menu-toggle" aria-label="Open menu" aria-expanded="false">☰</button>
    <nav class="nav-links">
      <a class="<?= $current==='index.php'?'active':'' ?>" href="<?=e(base_url('index.php'))?>">Home</a>
      <a class="<?= $current==='about.php'?'active':'' ?>" href="<?=e(base_url('about.php'))?>">About</a>
      <a class="<?= $current==='services.php'?'active':'' ?>" href="<?=e(base_url('services.php'))?>">Services</a>
      <a class="<?= $current==='appointment.php'?'active':'' ?>" href="<?=e(base_url('appointment.php'))?>">Consultation</a>
      <a class="<?= $current==='contact.php'?'active':'' ?>" href="<?=e(base_url('contact.php'))?>">Contact</a>
      <?php if(!empty($_SESSION['user'])): ?>
        <a class="nav-login" href="<?=e(base_url($_SESSION['user']['role']==='admin'?'admin/dashboard.php':'client/dashboard.php'))?>">Dashboard</a>
      <?php else: ?>
        <a class="nav-login" href="<?=e(base_url('auth/login.php'))?>">Client Login</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<main>
