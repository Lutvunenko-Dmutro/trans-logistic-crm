<?php
const SERVICES = ['Вантажні', 'Пасажирські', 'Евакуатор', 'Склад', 'Логістика'];

function clean($data) { 
    return htmlspecialchars(stripslashes(trim($data ?? ''))); 
}

function validate_phone($phone) {
    if (!preg_match('/^\+380\d{9}$/', $phone)) return "Формат: +380XXXXXXXXX";
    return true;
}
?>