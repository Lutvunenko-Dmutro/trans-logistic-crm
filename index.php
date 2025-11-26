<?php
session_start();
require_once 'db.php';

// --- КОНФІГУРАЦІЯ ---
const SERVICES = ['Вантажні', 'Пасажирські', 'Евакуатор', 'Склад', 'Логістика'];

function clean($data) { return htmlspecialchars(stripslashes(trim($data ?? ''))); }

// Валідація 
function validate_phone($phone) {
    if (!preg_match('/^\+380\d{9}$/', $phone)) return "Формат: +380XXXXXXXXX";
    $code = substr($phone, 4, 2);
    return in_array($code, ['50','66','95','99','67','68','96','97','98','63','73','93','91','92','94']) ? true : "Код ($code) невідомий";
}

// --- CONTROLLER ---
$action = $_GET['action'] ?? null;

if ($action === 'export') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=orders.csv');
    $out = fopen('php://output', 'w');
    fputs($out, "\xEF\xBB\xBF");
    fputcsv($out, ['ID', 'Клієнт', 'Email', 'Телефон', 'Послуга', 'Деталі', 'Статус', 'Дата']);
    $res = $conn->query("SELECT * FROM orders ORDER BY created_at DESC");
    while ($row = $res->fetch_assoc()) fputcsv($out, $row);
    fclose($out); exit;
}

if ($action === 'status' && isset($_GET['id'], $_GET['val'])) {
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $_GET['val'], $_GET['id']);
    $stmt->execute();
    header("Location: index.php"); exit;
}

if ($action === 'delete' && isset($_GET['id'])) {
    $stmt = $conn->prepare("DELETE FROM orders WHERE id = ?");
    $stmt->bind_param("i", $_GET['id']);
    $stmt->execute();
    $_SESSION['msg'] = ['text' => 'Запис видалено.', 'type' => 'warning'];
    header("Location: index.php"); exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $data = [
        'name' => clean($_POST['client_name']),
        'email' => clean($_POST['email']),
        'phone' => clean($_POST['phone']),
        'service' => clean($_POST['service_type']),
        'details' => clean($_POST['details'])
    ];
    
    $errors = [];
    if (mb_strlen($data['name']) < 3) $errors['client_name'] = "Ім'я закоротке";
    if (($res = validate_phone($data['phone'])) !== true) $errors['phone'] = $res;
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = "Невірний email";

    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        $_SESSION['old'] = $data;
        $_SESSION['msg'] = ['text' => 'Виправте помилки!', 'type' => 'danger'];
    } else {
        $stmt = $conn->prepare("INSERT INTO orders (client_name, email, phone, service_type, details, status) VALUES (?, ?, ?, ?, ?, 'new')");
        $stmt->bind_param("sssss", $data['name'], $data['email'], $data['phone'], $data['service'], $data['details']);
        if ($stmt->execute()) {
            $_SESSION['msg'] = ['text' => 'Успішно створено!', 'type' => 'success'];
            unset($_SESSION['old']);
        } else {
            $_SESSION['msg'] = ['text' => 'Помилка БД', 'type' => 'danger'];
        }
    }
    header("Location: index.php"); exit;
}

// --- DATA ---
$stats = $conn->query("SELECT COUNT(*) as total, SUM(status='new') as new FROM orders")->fetch_assoc();
$chartData = $conn->query("SELECT service_type, COUNT(*) as c FROM orders GROUP BY service_type");
$labels = []; $counts = [];
while ($r = $chartData->fetch_assoc()) { $labels[] = $r['service_type']; $counts[] = $r['c']; }

$search = clean($_GET['search'] ?? '');
$sql = "SELECT * FROM orders WHERE client_name LIKE ? OR phone LIKE ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$like = "%$search%";
$stmt->bind_param("ss", $like, $like);
$stmt->execute();
$orders = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="uk" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TransLogistic CRM v4.0</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Плавний перехід тем */
        body { transition: background-color 0.3s, color 0.3s; }
        .card { transition: background-color 0.3s, border-color 0.3s; border: none; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        
        /* Градієнти (виглядають добре і в темній, і в світлій) */
        .bg-gradient-primary { background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%); }
        .bg-gradient-dark { background: linear-gradient(135deg, #212529 0%, #343a40 100%); }
        
        /* Специфічні стилі для темної теми */
        [data-bs-theme="dark"] .card { background-color: #2b3035; }
        [data-bs-theme="dark"] body { background-color: #212529; }
        
        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg sticky-top border-bottom bg-body-tertiary no-print">
    <div class="container">
        <a class="navbar-brand fw-bold text-primary" href="#">
            <i class="bi bi-truck-front-fill me-2"></i>TransLogistic
        </a>
        
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-outline-secondary rounded-circle" id="themeToggle" title="Змінити тему">
                <i class="bi bi-moon-stars-fill" id="themeIcon"></i>
            </button>
            
            <div class="vr mx-2"></div>
            
            <a href="?action=export" class="btn btn-sm btn-success"><i class="bi bi-file-earmark-excel"></i> Excel</a>
            <button class="btn btn-sm btn-dark" onclick="window.print()"><i class="bi bi-printer"></i></button>
        </div>
    </div>
</nav>

<div class="container py-4">

    <?php if (isset($_SESSION['msg'])): ?>
        <div class="alert alert-<?= $_SESSION['msg']['type'] ?> alert-dismissible fade show shadow-sm" id="mainAlert">
            <?= $_SESSION['msg']['text'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['msg']); ?>
    <?php endif; ?>

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card bg-gradient-primary text-white p-4 h-100 position-relative overflow-hidden">
                <h3 class="display-5 fw-bold"><?= $stats['total'] ?? 0 ?></h3>
                <p class="mb-0 opacity-75">Всього замовлень</p>
                <div class="mt-4 pt-3 border-top border-white border-opacity-25 d-flex justify-content-between">
                    <span>Нових:</span>
                    <span class="badge bg-warning text-dark"><?= $stats['new'] ?? 0 ?></span>
                </div>
                <i class="bi bi-bar-chart-fill position-absolute" style="font-size: 8rem; right: -20px; bottom: -20px; opacity: 0.1;"></i>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card h-100 p-3">
                <h6 class="card-title text-body-secondary mb-3">Аналітика послуг</h6>
                <div style="height: 200px; position: relative;">
                    <canvas id="kpiChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4 no-print">
            <div class="card h-100">
                <div class="card-header bg-body-tertiary fw-bold text-primary">
                    <i class="bi bi-pen me-2"></i>Нова заявка
                </div>
                <div class="card-body">
                    <form method="POST" id="orderForm" novalidate>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Клієнт</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" name="client_name" class="form-control <?= isset($_SESSION['errors']['client_name']) ? 'is-invalid' : '' ?>" value="<?= $_SESSION['old']['name'] ?? '' ?>">
                            </div>
                            <div class="invalid-feedback d-block"><?= $_SESSION['errors']['client_name'] ?? '' ?></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Контакти</label>
                            <input type="email" name="email" class="form-control mb-2 <?= isset($_SESSION['errors']['email']) ? 'is-invalid' : '' ?>" placeholder="Email" value="<?= $_SESSION['old']['email'] ?? '' ?>">
                            <input type="text" name="phone" class="form-control <?= isset($_SESSION['errors']['phone']) ? 'is-invalid' : '' ?>" placeholder="+380..." value="<?= $_SESSION['old']['phone'] ?? '+380' ?>">
                            <div class="invalid-feedback d-block"><?= $_SESSION['errors']['phone'] ?? ($_SESSION['errors']['email'] ?? '') ?></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Послуга</label>
                            <select name="service_type" class="form-select">
                                <?php foreach (SERVICES as $srv): ?>
                                    <option value="<?= $srv ?>" <?= (($_SESSION['old']['service'] ?? '') === $srv) ? 'selected' : '' ?>><?= $srv ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <textarea name="details" class="form-control" rows="2" placeholder="Деталі..."><?= $_SESSION['old']['details'] ?? '' ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 fw-bold" id="submitBtn">
                            Створити
                        </button>
                    </form>
                    <?php unset($_SESSION['errors'], $_SESSION['old']); ?>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header bg-body-tertiary d-flex justify-content-between align-items-center">
                    <span class="fw-bold text-primary"><i class="bi bi-table me-2"></i>Реєстр</span>
                    <form class="d-flex no-print" method="GET">
                        <input type="search" name="search" class="form-control form-control-sm me-2" placeholder="Пошук..." value="<?= $search ?>">
                        <button class="btn btn-sm btn-outline-primary"><i class="bi bi-search"></i></button>
                    </form>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="table-light">
                            <tr><th>Клієнт</th><th>Послуга</th><th>Статус</th><th class="no-print text-end">Дії</th></tr>
                        </thead>
                        <tbody>
                            <?php if ($orders->num_rows === 0): ?>
                                <tr><td colspan="4" class="text-center p-4 text-muted">Записів не знайдено</td></tr>
                            <?php else: ?>
                                <?php while ($row = $orders->fetch_assoc()): ?>
                                    <?php 
                                    $badge = match ($row['status']) {
                                        'new' => ['success', 'НОВЕ'],
                                        'in_progress' => ['warning', 'В РОБОТІ'],
                                        'completed' => ['secondary', 'ГОТОВО'],
                                        default => ['primary', $row['status']]
                                    };
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold"><?= $row['client_name'] ?></div>
                                            <div class="text-secondary" style="font-size: 0.85em;"><?= $row['phone'] ?></div>
                                        </td>
                                        <td>
                                            <span class="badge bg-body-secondary text-body-secondary border"><?= $row['service_type'] ?></span>
                                            <div class="text-truncate text-secondary" style="max-width: 150px;"><?= $row['details'] ?></div>
                                        </td>
                                        <td><span class="badge bg-<?= $badge[0] ?> bg-opacity-75"><?= $badge[1] ?></span></td>
                                        <td class="text-end no-print">
                                            <div class="btn-group btn-group-sm">
                                                <a href="?action=status&val=in_progress&id=<?= $row['id'] ?>" class="btn btn-outline-warning"><i class="bi bi-gear-fill"></i></a>
                                                <a href="?action=status&val=completed&id=<?= $row['id'] ?>" class="btn btn-outline-success"><i class="bi bi-check-lg"></i></a>
                                                <a href="?action=delete&id=<?= $row['id'] ?>" class="btn btn-outline-danger" onclick="return confirm('Видалити?')"><i class="bi bi-trash"></i></a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // --- DARK MODE LOGIC ---
    const html = document.documentElement;
    const themeToggle = document.getElementById('themeToggle');
    const themeIcon = document.getElementById('themeIcon');
    
    // 1. Завантаження збереженої теми
    const savedTheme = localStorage.getItem('theme') || 'light';
    html.setAttribute('data-bs-theme', savedTheme);
    updateIcon(savedTheme);

    // 2. Обробник кліку
    themeToggle.addEventListener('click', () => {
        const currentTheme = html.getAttribute('data-bs-theme');
        const newTheme = currentTheme === 'light' ? 'dark' : 'light';
        
        html.setAttribute('data-bs-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        updateIcon(newTheme);
        updateChartColors(newTheme); // Оновлюємо графік
    });

    function updateIcon(theme) {
        if (theme === 'dark') {
            themeIcon.classList.remove('bi-moon-stars-fill');
            themeIcon.classList.add('bi-sun-fill');
        } else {
            themeIcon.classList.remove('bi-sun-fill');
            themeIcon.classList.add('bi-moon-stars-fill');
        }
    }

    // --- CHART CONFIG ---
    let myChart = null;
    const chartCtx = document.getElementById('kpiChart');

    function initChart(theme) {
        const textColor = theme === 'dark' ? '#adb5bd' : '#495057';
        
        const config = {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [{ 
                    data: <?= json_encode($counts) ?>, 
                    backgroundColor: ['#0d6efd', '#198754', '#0dcaf0', '#ffc107', '#dc3545'],
                    borderWidth: 0
                }]
            },
            options: { 
                maintainAspectRatio: false, 
                plugins: { 
                    legend: { 
                        position: 'right',
                        labels: { color: textColor } 
                    } 
                },
                cutout: '75%'
            }
        };

        if (myChart) myChart.destroy();
        myChart = new Chart(chartCtx, config);
    }

    function updateChartColors(theme) {
        initChart(theme);
    }

    // Запуск графіка
    initChart(savedTheme);

    // --- UTILS ---
    document.getElementById('orderForm').addEventListener('submit', function() {
        const btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Збереження...';
    });

    setTimeout(() => {
        let alert = document.getElementById('mainAlert');
        if (alert) new bootstrap.Alert(alert).close();
    }, 4000);
</script>

</body>
</html>