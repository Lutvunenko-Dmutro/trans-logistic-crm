-- Створення таблиці
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    service_type VARCHAR(50) NOT NULL,
    details TEXT,
    status VARCHAR(20) DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Тестові дані
INSERT INTO orders (client_name, email, phone, service_type, details, status, created_at) VALUES
('ТОВ "БудМайстер"', 'office@bud.com', '+380501112233', 'Вантажні', 'Перевезення цегли (20т)', 'completed', NOW() - INTERVAL 5 DAY),
('Олена Коваленко', 'elena.k@gmail.com', '+380975554433', 'Пасажирські', 'Оренда автобуса (30 осіб)', 'new', NOW()),
('Максим Дмитренко', 'max.driver@ukr.net', '+380639998877', 'Евакуатор', 'Зламався на трасі Е-95', 'in_progress', NOW() - INTERVAL 1 DAY),
('IT Solutions', 'admin@itsolutions.ua', '+380441234567', 'Склад', 'Серверне обладнання', 'new', NOW()),
('Андрій Шевченко', 'sheva@football.ua', '+380937777777', 'Евакуатор', 'Забрати авто', 'completed', NOW() - INTERVAL 3 DAY);