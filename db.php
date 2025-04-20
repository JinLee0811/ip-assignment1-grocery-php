<?php
// 데이터베이스 연결 설정
$host = 'localhost';
$dbname = 'fresh_market';
$username = 'root';
$password = 'wjdwls3025!';  // MySQL root 비밀번호

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("데이터베이스 연결 실패: " . $e->getMessage());
}

// 세션 시작
session_start();

// 장바구니 초기화 (세션이 없는 경우)
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
?> 