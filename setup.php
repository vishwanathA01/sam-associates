<?php
require __DIR__.'/config/config.php';
$msg='';
try {
  $pdo=db(true);
  $pdo->exec("CREATE DATABASE IF NOT EXISTS `".DB_NAME."` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
  $pdo=db();
  $pdo->exec("CREATE TABLE IF NOT EXISTS users(
    id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(120) NOT NULL, email VARCHAR(180) UNIQUE NOT NULL,
    phone VARCHAR(30), password VARCHAR(255) NOT NULL, role ENUM('client','admin') NOT NULL DEFAULT 'client',
    status TINYINT(1) DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
  $pdo->exec("CREATE TABLE IF NOT EXISTS appointments(
    id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NULL, name VARCHAR(120), email VARCHAR(180), phone VARCHAR(30),
    service VARCHAR(120), appointment_date DATE, appointment_time TIME, notes TEXT, status VARCHAR(30) DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
  $pdo->exec("CREATE TABLE IF NOT EXISTS enquiries(
    id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(120), email VARCHAR(180), phone VARCHAR(30), message TEXT,
    status VARCHAR(30) DEFAULT 'New', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
  $pdo->exec("CREATE TABLE IF NOT EXISTS notifications(
    id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, title VARCHAR(180), message TEXT, is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
  $pdo->exec("CREATE TABLE IF NOT EXISTS invoices(
    id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, invoice_no VARCHAR(60) UNIQUE, amount DECIMAL(12,2) DEFAULT 0, status VARCHAR(30) DEFAULT 'Pending', due_date DATE NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
  $pdo->exec("CREATE TABLE IF NOT EXISTS documents(
    id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, title VARCHAR(180), file_path VARCHAR(255), status VARCHAR(30) DEFAULT 'Available', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
  $hash=password_hash('mahesh@1234', PASSWORD_DEFAULT);
  $stmt=$pdo->prepare("INSERT INTO users(name,email,phone,password,role,status) VALUES('SAM Admin','info.sam2026@yahoo.com','+91 6202426418',?,'admin',1)
    ON DUPLICATE KEY UPDATE role='admin',password=VALUES(password),status=1");
  $stmt->execute([$hash]);
  $pdo->exec("UPDATE users SET role='client',status=0 WHERE email='admin@samassociates.in'");
  $msg='Database ready. Admin: info.sam2026@yahoo.com / mahesh@1234';
} catch(Throwable $e) { $msg='Setup error: '.$e->getMessage(); }
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width"><link rel="stylesheet" href="<?=e(base_url('assets/style.css'))?>"><title>Setup • SAM & Associates</title></head><body class="setup-page"><main class="setup-card"><img src="<?=e(base_url('assets/logo.png'))?>" class="setup-logo"><h1>Website Setup</h1><p><?=e($msg)?></p><a class="btn btn-primary" href="<?=e(base_url('index.php'))?>">Open Website</a></main></body></html>