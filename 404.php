<!DOCTYPE html>
<html lang="uk" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <title>404 | Сторінку не знайдено</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--bg-dark);
            overflow: hidden;
        }
        .error-card {
            text-align: center;
            padding: 3rem;
            max-width: 500px;
            position: relative;
            z-index: 2;
        }
        .error-code {
            font-size: 8rem;
            font-weight: 800;
            line-height: 1;
            background: linear-gradient(135deg, #0d6efd 0%, #0043ce 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
        }
        .bg-icon {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 20rem;
            opacity: 0.03;
            z-index: -1;
            color: white;
        }
    </style>
</head>
<body>

    <i class="bi bi-exclamation-triangle-fill bg-icon"></i>

    <div class="error-card fade-in-up">
        <h1 class="error-code">404</h1>
        <h2 class="text-white mb-3">Упс! Сторінку не знайдено</h2>
        <p class="text-muted mb-4">
            Здається, ви намагаєтесь отримати доступ до ресурсу, який було переміщено або видалено.
        </p>
        <a href="index.php" class="btn btn-primary btn-lg px-4 fw-bold">
            <i class="bi bi-house-door-fill me-2"></i>На головну
        </a>
    </div>

</body>
</html>