<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'db.php';
require_once 'functions.php';

// Перевірка входу
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$action = $_REQUEST['action'] ?? null;

// --- 1. ВИХІД ---
if ($action === 'logout') {
    session_destroy();
    header("Location: login.php");
    exit;
}

// --- 2. ЕКСПОРТ ---
if ($action === 'export') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=orders.csv');
    $out = fopen('php://output', 'w');
    fputs($out, "\xEF\xBB\xBF");
    fputcsv($out, ['ID', 'Клієнт', 'Email', 'Телефон', 'Послуга', 'Деталі', 'Статус', 'Дата']);
    $res = $conn->query("SELECT * FROM orders ORDER BY created_at DESC");
    while ($row = $res->fetch_assoc()) fputcsv($out, $row);
    fclose($out); 
    exit;
}

// --- 3. ЗМІНА СТАТУСУ ---
if ($action === 'status') {
    $id = $_REQUEST['id'] ?? null;
    $val = $_REQUEST['val'] ?? null;

    if ($id && $val) {
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $val, $id);
        
        if (!$stmt->execute()) {
            // Якщо помилка SQL - запишемо її
            $_SESSION['msg'] = ['text' => 'Помилка зміни статусу: ' . $stmt->error, 'type' => 'danger'];
        }
    }
    header("Location: index.php"); 
    exit;
}

// --- 4. ВИДАЛЕННЯ ---
if ($action === 'delete' && isset($_POST['id'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $_SESSION['msg'] = ['text' => 'Помилка безпеки (CSRF). Оновіть сторінку.', 'type' => 'danger'];
        header("Location: index.php"); exit;
    }
    
    $stmt = $conn->prepare("DELETE FROM orders WHERE id = ?");
    $stmt->bind_param("i", $_POST['id']);
    
    if ($stmt->execute()) {
        $_SESSION['msg'] = ['text' => 'Запис видалено.', 'type' => 'warning'];
    } else {
        $_SESSION['msg'] = ['text' => 'Помилка видалення: ' . $stmt->error, 'type' => 'danger'];
    }
    header("Location: index.php"); exit;
}

// --- 5. СТВОРЕННЯ (POST) ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && $action === 'create') {
    // 1. Перевірка CSRF
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $_SESSION['msg'] = ['text' => 'Помилка безпеки (CSRF). Оновіть сторінку.', 'type' => 'danger'];
        header("Location: index.php"); exit;
    }

    $data = [
        'name' => clean($_POST['client_name']),
        'email' => clean($_POST['email']),
        'phone' => clean($_POST['phone']),
        'service' => clean($_POST['service_type']),
        'details' => clean($_POST['details'])
    ];
    
    $errors = [];
    if (mb_strlen($data['name']) < 3) $errors['client_name'] = "Ім'я закоротке";
    
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        $_SESSION['old'] = $data;
        $_SESSION['msg'] = ['text' => implode('. ', $errors), 'type' => 'danger'];
    } else {
        $stmt = $conn->prepare("INSERT INTO orders (client_name, email, phone, service_type, details, status) VALUES (?, ?, ?, ?, ?, 'new')");
        if ($stmt === false) {
            $_SESSION['msg'] = ['text' => 'Помилка SQL Prepare: ' . $conn->error, 'type' => 'danger'];
        } else {
            $stmt->bind_param("sssss", $data['name'], $data['email'], $data['phone'], $data['service'], $data['details']);
            
            if ($stmt->execute()) {
                $_SESSION['msg'] = ['text' => 'Заявку успішно створено!', 'type' => 'success'];
                unset($_SESSION['old']);
            } else {
                $_SESSION['msg'] = ['text' => 'Помилка збереження: ' . $stmt->error, 'type' => 'danger'];
                $_SESSION['old'] = $data;
            }
        }
    }
    header("Location: index.php"); exit;
}

header("Location: index.php");

?>
