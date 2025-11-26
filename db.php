<?php
$servername = "localhost";
$username = "root"; // За замовчуванням у XAMPP
$password = "";     // За замовчуванням порожній
$dbname = "transport_db";

// Створення підключення
$conn = new mysqli($servername, $username, $password, $dbname);

// Перевірка підключення
if ($conn->connect_error) {
    die("Помилка підключення: " . $conn->connect_error);
}
?>