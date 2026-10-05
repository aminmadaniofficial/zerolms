<?php

$host = getenv('DB_HOST') ?: 'db';
$db   = getenv('DB_NAME') ?: 'school_online'; 
$user = getenv('DB_USER') ?: 'zerolms';          
$pass = getenv('DB_PASS') ?: '1597538264Mm';              
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     error_log("Database Connection Error: " . $e->getMessage());
     throw new \PDOException("خطا در برقراری ارتباط با پایگاه داده. لطفاً پیکربندی سرور را بررسی فرمایید.", (int)$e->getCode());
}
