<?php
session_start();
require_once 'db.php';
require_once 'functions.php';

// Якщо сайт не на локальному сервері, змушуємо використовувати HTTPS
if ($_SERVER['HTTP_HOST'] !== 'localhost' && $_SERVER['HTTP_HOST'] !== '127.0.0.1') {
    if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === "off") {
        $location = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        header('HTTP/1.1 301 Moved Permanently');
        header('Location: ' . $location);
        exit;
    }
}

// Генерація CSRF токена 
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Перевірка токена
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = "Помилка безпеки: сесія застаріла. Оновіть сторінку.";
    } else {
        $username = clean($_POST['username']);
        $password = $_POST['password'];

        // Запит до БД (Prepared Statements)
        $stmt = $conn->prepare("SELECT id, password FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            if (password_verify($password, $row['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $username;
                header("Location: index.php");
                exit;
            }
        }
        $error = "Невірний логін або пароль";
    }
}
?>
<!DOCTYPE html>
<html lang="uk" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вхід | TransLogistic CRM</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">

    <style>
        /* Специфічні стилі для сторінки входу (Split Screen) */
        body, html { height: 100%; overflow: hidden; font-family: 'Inter', sans-serif; }
        
        .login-wrapper {
            height: 100vh;
            width: 100vw;
        }

        /* ЛІВА ЧАСТИНА (ФОРМА) */
        .left-panel {
            background-color: #1e2029;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 3rem;
            position: relative;
            z-index: 2;
            box-shadow: 10px 0 30px rgba(0,0,0,0.3);
        }

        .login-card-minimal {
            width: 100%;
            max-width: 380px;
            margin: 0 auto;
        }

        .brand-icon {
            font-size: 3rem;
            color: #0d6efd;
            margin-bottom: 1rem;
        }

        /* Стилізація полів вводу */
        .form-control {
            background-color: #2b2d36;
            border: 1px solid #3f4250;
            color: #fff;
            padding: 0.8rem 1rem;
            border-radius: 8px;
        }
        
        .form-control:focus {
            background-color: #2b2d36;
            border-color: #0d6efd;
            color: #fff;
            box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.15);
        }

        .form-label {
            font-size: 0.85rem;
            color: #adb5bd;
            margin-bottom: 0.4rem;
        }

        /* ПРАВА ЧАСТИНА (КАРТИНКА) */
        .right-panel {
            /* Переконайтеся, що файл login-bg.jpg лежить у тій же папці */
            background-image: url('login-bg.jpg');
            background-size: cover;
            background-position: center;
            position: relative;
        }

        .right-panel::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(13, 20, 40, 0.85) 0%, rgba(13, 20, 40, 0.6) 100%);
        }

        .overlay-content {
            position: relative;
            z-index: 2;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 4rem;
            color: white;
        }

        .big-logo-text {
            font-size: 3.5rem;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 1rem;
            background: linear-gradient(90deg, #fff, #aebad4);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Анімація появи */
        .fade-in-up {
            animation: fadeInUp 0.8s ease-out forwards;
            opacity: 0;
            transform: translateY(20px);
        }
        @keyframes fadeInUp {
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<div class="container-fluid p-0 login-wrapper">
    <div class="row g-0 h-100">
        
        <div class="col-lg-4 col-md-5 left-panel">
            <div class="login-card-minimal fade-in-up">
                <div class="text-center mb-5">
                    <i class="bi bi-truck-front-fill brand-icon"></i>
                    <h3 class="fw-bold text-white mb-1">Log In</h3>
                    <p class="text-muted small">Ласкаво просимо назад!</p>
                </div>

                <?php if($error): ?>
                    <div class="alert alert-danger border-0 d-flex align-items-center p-3 mb-4 rounded-3 small" style="background: rgba(220, 53, 69, 0.1); color: #ea868f;">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= $error ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Логін</label>
                        <input type="text" class="form-control" name="username" placeholder="Введіть логін" required autofocus>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">Пароль</label>
                        <input type="password" class="form-control" name="password" placeholder="••••••••" required>
                    </div>

                    <button class="btn btn-primary w-100 py-3 fw-bold shadow-lg mt-2" type="submit">
                        Увійти в систему <i class="bi bi-arrow-right ms-2"></i>
                    </button>
                    
                    <div class="text-center mt-5">
                        <small class="text-muted opacity-50">© 2025 TransLogistic Inc.</small>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-8 col-md-7 d-none d-md-block right-panel">
            <div class="overlay-content">
                <div style="max-width: 600px;" class="fade-in-up" style="animation-delay: 0.2s;">
                    
                    <img src="Avatar.png"
                        alt="TransLogistic Logo"
                        loading="lazy"
                        class="mb-4 shadow-lg rounded-circle"
                        width="100"
                        height="100"
                        style="object-fit: cover;">
                    
                    <h1 class="big-logo-text">TransLogistic<br>CRM</h1>
                    
                    <p class="lead text-white-50 mt-3" style="font-size: 1.2rem; max-width: 80%;">
                        Сучасна система управління логістичними процесами. Безпека, швидкість та повний контроль вашого вантажу.
                    </p>
                    
                    <div class="mt-5 d-flex gap-3 text-white-50">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-check-circle-fill text-success"></i> <span>Моніторинг 24/7</span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-check-circle-fill text-success"></i> <span>Secure Data</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

</body>
</html>


