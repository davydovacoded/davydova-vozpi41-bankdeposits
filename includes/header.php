<?php

declare(strict_types=1);

$pageTitle = $pageTitle ?? 'банк';
$currentSection = $currentSection ?? 'home';
$sectionNames = [
    'clients' => ['Клиенты', '/clients/'],
    'deposits' => ['Вклады', '/deposits/'],
    'accounts' => ['Счета', '/accounts/'],
    'report' => ['Отчёты', '/report/'],
];
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title><?= e($pageTitle) ?> — банк</title>
    <link rel="icon" type="image/png" href="/assets/img/bank-logo.png">
    <script>
        try {
            if (localStorage.getItem('bank-sidebar-collapsed') === '1') {
                document.documentElement.classList.add('sidebar-is-collapsed');
            }
        } catch (error) {}
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&amp;family=Onest:wght@500;600;700;800&amp;display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/app.css?v=20261024-21">
    <script src="/assets/js/app.js?v=20261024-21" defer></script>
</head>
<body class="<?= $currentSection === 'home' ? 'home-view' : 'data-view' ?>">
<div class="app-layout">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a class="brand" href="/" aria-label="На главную">
                <span class="brand-logo" aria-hidden="true"><img src="/assets/img/bank-logo.png" alt=""></span>
                <span class="brand-copy"><strong>банк</strong></span>
            </a>
            <button class="sidebar-toggle" id="sidebar-toggle" type="button" aria-label="Свернуть меню" aria-expanded="true">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg>
            </button>
        </div>
        <nav class="sidebar-nav" aria-label="Основная навигация">
            <a class="<?= $currentSection === 'home' ? 'active' : '' ?>" href="/" title="Главная"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 11.5L12 4l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/></svg><span>Главная</span></a>
            <a class="<?= $currentSection === 'clients' ? 'active' : '' ?>" href="/clients/" title="Клиенты"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg><span>Клиенты</span></a>
            <a class="<?= $currentSection === 'deposits' ? 'active' : '' ?>" href="/deposits/" title="Вклады"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M8 16L16 8M9 8h.01M15 16h.01"/></svg><span>Вклады</span></a>
            <a class="<?= $currentSection === 'accounts' ? 'active' : '' ?>" href="/accounts/" title="Счета"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 7h18v12H3zM3 10h18M7 15h4"/></svg><span>Счета</span></a>
            <a class="<?= $currentSection === 'report' ? 'active' : '' ?>" href="/report/" title="Отчёты"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/></svg><span>Отчёты</span></a>
        </nav>
        <button class="project-info-button" id="project-info-button" type="button" aria-haspopup="dialog" aria-controls="project-modal">
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7h.01"/></svg>
            <span>Информация<br>о проекте</span>
        </button>
    </aside>
    <div class="workspace">
        <header class="topbar <?= $currentSection === 'home' ? 'topbar-home' : '' ?>">
            <?php if ($currentSection !== 'home' && $currentSection !== 'report'): ?><strong><?= e($pageTitle) ?></strong><?php else: ?><span></span><?php endif; ?>
            <span class="admin-badge">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="7" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
                <span>Администратор</span>
            </span>
        </header>
        <main class="main-content">
            <?php if ($currentSection !== 'home' && isset($sectionNames[$currentSection])): ?>
                <?php [$sectionTitle, $sectionUrl] = $sectionNames[$currentSection]; ?>
                <nav class="breadcrumbs" aria-label="Хлебные крошки">
                    <a href="/">Главная</a><span>/</span>
                    <?php if ($pageTitle !== $sectionTitle): ?>
                        <a href="<?= e($sectionUrl) ?>"><?= e($sectionTitle) ?></a><span>/</span>
                    <?php endif; ?>
                    <span aria-current="page"><?= e($pageTitle) ?></span>
                </nav>
            <?php endif; ?>
