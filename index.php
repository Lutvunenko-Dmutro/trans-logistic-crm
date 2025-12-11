
<?php
session_start();
require_once 'db.php';
require_once 'functions.php';
// --- ВИМОГА: HTTPS ---
// Якщо сайт не на локальному сервері, змушуємо використовувати HTTPS
if ($_SERVER['HTTP_HOST'] !== 'localhost' && $_SERVER['HTTP_HOST'] !== '127.0.0.1') {
    if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === "off") {
        $location = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        header('HTTP/1.1 301 Moved Permanently');
        header('Location: ' . $location);
        exit;
    }
}
// Перевірка авторизації
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// --- ОТРИМАННЯ ДАНИХ ---
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
<html lang="uk" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | TransLogistic CRM</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <link rel="stylesheet" href="style.css">
    
    <style>
        /* Додаткові стилі для красивої таблиці та аватарок */
        .avatar-initials {
            width: 40px; height: 40px;
            background-color: rgba(13, 110, 253, 0.15);
            color: #0d6efd;
            font-weight: 700;
            display: flex; align-items: center; justify-content: center;
            border-radius: 12px;
            margin-right: 15px;
            font-size: 1.1em;
        }
        
        .status-badge {
            font-size: 0.7rem; 
            font-weight: 700; 
            padding: 6px 12px; 
            border-radius: 30px;
            display: inline-flex; align-items: center; gap: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Стиль кнопок дій (квадратні іконки) */
        .btn-icon {
            width: 32px; height: 32px; 
            padding: 0; 
            display: flex; align-items: center; justify-content: center;
            border-radius: 8px;
            transition: all 0.2s;
        }
        .btn-icon:hover { transform: translateY(-2px); }

        /* Таблиця */
        .custom-table th { 
            font-size: 0.75rem; 
            text-transform: uppercase; 
            letter-spacing: 0.05em; 
            opacity: 0.6; 
            font-weight: 600; 
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.05) !important;
        }
        .custom-table td { 
            padding: 1rem 0.5rem; 
            vertical-align: middle; 
            border-bottom: 1px solid rgba(255,255,255,0.03); 
        }
        
        /* Картки Dashboard */
        .stat-card-blue {
            background: linear-gradient(135deg, #0d6efd 0%, #0043ce 100%);
            color: white;
            border: none;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg sticky-top no-print py-3" style="background-color: var(--bg-dark); border-bottom: 1px solid rgba(255,255,255,0.05);">
    <div class="container-fluid px-4">
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold text-white" href="#">
            <i class="bi bi-truck-front-fill text-primary fs-4"></i>
            TransLogistic <span class="text-secondary small fw-normal ms-2 opacity-50">SECURE MODE</span>
        </a>
        
        <div class="d-flex align-items-center gap-3">
            <div class="d-none d-md-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-white bg-opacity-10">
                <i class="bi bi-person-circle"></i>
                <span class="small fw-medium"><?= htmlspecialchars($_SESSION['username']) ?></span>
            </div>
            
            <a href="process.php?action=logout" class="btn btn-sm btn-outline-danger border-0" title="Вийти">
                <i class="bi bi-box-arrow-right fs-5"></i>
            </a>
            
            <button class="btn btn-sm btn-outline-secondary border-0" id="themeToggle"><i class="bi bi-sun-fill fs-5" id="themeIcon"></i></button>
            <a href="process.php?action=export" class="btn btn-sm btn-success fw-bold px-3"><i class="bi bi-file-earmark-excel me-1"></i> Excel</a>
        </div>
    </div>
</nav>

<div class="container-fluid px-4 py-4">
    
    <?php if (isset($_SESSION['msg'])): ?>
        <div class="alert alert-<?= $_SESSION['msg']['type'] ?> alert-dismissible fade show border-0 shadow-sm rounded-3 d-flex align-items-center gap-3 mb-4" role="alert">
            <i class="bi bi-info-circle-fill fs-4"></i>
            <div><?= $_SESSION['msg']['text'] ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['msg']); ?>
    <?php endif; ?>

    <div class="row g-4 mb-4">
        <div class="col-xl-4 col-md-5">
            <div class="card stat-card-blue h-100 p-4 position-relative overflow-hidden shadow-lg rounded-4">
                <div class="d-flex flex-column h-100 justify-content-between position-relative z-1">
                    <div>
                        <h1 class="display-3 fw-bold mb-1"><?= $stats['total'] ?></h1>
                        <p class="opacity-75 fs-5">Всього замовлень</p>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-end border-top border-white border-opacity-25 pt-4 mt-2">
                        <span class="fs-5">Нових:</span>
                        <span class="badge bg-warning text-dark fs-6 rounded-3 px-3 py-2"><?= $stats['new'] ?></span>
                    </div>
                </div>
                <i class="bi bi-bar-chart-fill position-absolute" style="font-size: 10rem; right: -20px; bottom: -40px; opacity: 0.15; transform: rotate(-10deg);"></i>
            </div>
        </div>

        <div class="col-xl-8 col-md-7">
            <div class="card h-100 border-0 shadow-sm rounded-4" style="background: #1e293b;">
                <div class="card-body p-4">
                    <h5 class="text-secondary opacity-75 mb-4">Аналітика послуг</h5>
                    <div class="d-flex justify-content-center align-items-center" style="height: 200px;">
                        <canvas id="kpiChart" data-labels='<?= json_encode($labels) ?>' data-counts='<?= json_encode($counts) ?>'></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        
        <div class="col-lg-4 no-print">
            <div class="card h-100 border-0 shadow-sm rounded-4" style="background: #1e293b;">
                <div class="card-header border-bottom border-secondary border-opacity-10 py-3">
                    <h6 class="mb-0 fw-bold text-primary"><i class="bi bi-pen me-2"></i>Нова заявка</h6>
                </div>
                <div class="card-body p-4">
                    <form action="process.php" method="POST" class="needs-validation">
                        <input type="hidden" name="action" value="create">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        
                        <div class="mb-3">
                            <label class="form-label small text-secondary">Клієнт</label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-secondary border-opacity-25 text-secondary"><i class="bi bi-person"></i></span>
                                <input type="text" name="client_name" class="form-control bg-dark border-secondary border-opacity-25 text-white" placeholder="Ім'я" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small text-secondary">Контакти</label>
                            <input type="email" name="email" class="form-control bg-dark border-secondary border-opacity-25 text-white mb-2" placeholder="Email" required>
                            <input type="text" name="phone" class="form-control bg-dark border-secondary border-opacity-25 text-white" placeholder="+380..." required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small text-secondary">Послуга</label>
                            <select name="service_type" class="form-select bg-dark border-secondary border-opacity-25 text-white">
                                <?php foreach (SERVICES as $s): ?><option value="<?=$s?>"><?=$s?></option><?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-4">
                            <textarea name="details" class="form-control bg-dark border-secondary border-opacity-25 text-white" rows="3" placeholder="Деталі..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">Створити</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card h-100 border-0 shadow-sm rounded-4" style="background: #1e293b;">
                <div class="card-header border-bottom border-secondary border-opacity-10 py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-table me-2 text-primary"></i>Реєстр</h6>
                    <form class="d-flex no-print position-relative" method="GET">
                        <input type="search" name="search" class="form-control ps-3 bg-dark border-secondary border-opacity-25 text-white form-control-sm" placeholder="Пошук..." value="<?= $search ?>" style="width: 200px;">
                        <button class="btn btn-sm btn-primary ms-2"><i class="bi bi-search"></i></button>
                    </form>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table custom-table table-hover mb-0 text-white">
                            <thead>
                                <tr>
                                    <th class="ps-4">Клієнт</th>
                                    <th>Послуга</th>
                                    <th>Статус</th>
                                    <th class="text-end pe-4">Дії</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($orders->num_rows === 0): ?>
                                    <tr><td colspan="4" class="text-center py-5 text-secondary opacity-50">Немає записів</td></tr>
                                <?php else: ?>
                                    <?php while($row = $orders->fetch_assoc()): 
                                        $initial = mb_substr($row['client_name'], 0, 1);
                                        $statusData = match($row['status']) {
                                            'new' => ['color' => 'success', 'label' => 'НОВЕ'],
                                            'in_progress' => ['color' => 'warning', 'label' => 'В РОБОТІ'],
                                            'completed' => ['color' => 'secondary', 'label' => 'ГОТОВО'],
                                            default => ['color' => 'primary', 'label' => $row['status']]
                                        };
                                        $c = $statusData['color'];
                                    ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-initials"><?= $initial ?></div>
                                                <div>
                                                    <div class="fw-bold"><?= $row['client_name'] ?></div>
                                                    <div class="text-secondary small opacity-75"><?= $row['phone'] ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="badge bg-dark border border-secondary border-opacity-25 text-secondary fw-normal mb-1">
                                                <?= $row['service_type'] ?>
                                            </div>
                                            <div class="text-secondary small text-truncate opacity-75" style="max-width: 150px;"><?= $row['details'] ?></div>
                                        </td>
                                        <td>
                                            <span class="status-badge bg-<?= $c ?> bg-opacity-10 text-<?= $c ?> border border-<?= $c ?> border-opacity-25">
                                                <?= $statusData['label'] ?>
                                            </span>
                                        </td>
                                        <td class="text-end pe-4">
                                            <div class="d-flex justify-content-end gap-2">
                                                
                                                <?php if($row['status'] === 'new'): ?>
                                                <form method="POST" action="process.php">
                                                    <input type="hidden" name="action" value="status">
                                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                    <input type="hidden" name="val" value="in_progress">
                                                    <button type="submit" class="btn btn-icon btn-outline-warning border-0" title="В роботу">
                                                        <i class="bi bi-gear-fill fs-6"></i>
                                                    </button>
                                                </form>
                                                <?php endif; ?>

                                                <?php if($row['status'] !== 'completed'): ?>
                                                <form method="POST" action="process.php">
                                                    <input type="hidden" name="action" value="status">
                                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                    <input type="hidden" name="val" value="completed">
                                                    <button type="submit" class="btn btn-icon btn-outline-success border-0" title="Завершити">
                                                        <i class="bi bi-check-lg fs-5"></i>
                                                    </button>
                                                </form>
                                                <?php endif; ?>

                                                <form method="POST" action="process.php" onsubmit="return confirm('Видалити?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                    <button type="submit" class="btn btn-icon btn-outline-danger border-0" title="Видалити">
                                                        <i class="bi bi-trash fs-6"></i>
                                                    </button>
                                                </form>
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
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="script.js"></script>
</body>
</html>
