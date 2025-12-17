<?php
// Налаштування заголовків для JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once 'db.php';
require_once 'functions.php';

// Отримання пошукового запиту
$search = clean($_GET['search'] ?? '');

// Базовий запит
$sql = "SELECT id, client_name, email, phone, service_type, status, created_at FROM orders";

// Додаємо фільтрацію, якщо є параметр пошуку
if ($search) {
    $sql .= " WHERE client_name LIKE '%$search%' OR phone LIKE '%$search%'";
}

$sql .= " ORDER BY created_at DESC";

$result = $conn->query($sql);
$orders = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
}

// Вивід результату у форматі JSON
echo json_encode($orders, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>