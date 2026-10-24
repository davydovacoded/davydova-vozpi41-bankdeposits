<?php

declare(strict_types=1);

$config = require __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Банковские вклады';
$currentSection = 'home';
$recentClients = [];
$recentOperations = [];

try {
    $pdo = database($config['db']);

    $recentClients = $pdo->query(
        'SELECT id_client, surname, name, patronymic, phone
         FROM Client
         ORDER BY id_client DESC
         LIMIT 3'
    )->fetchAll();

    $recentOperations = $pdo->query(
        "SELECT event_type, event_date, id_account, account_number, deposit_name,
                surname, client_name, patronymic
         FROM (
             SELECT 'open' AS event_type, a.open_date AS event_date, a.id_account,
                    a.account_number, d.name AS deposit_name, c.surname,
                    c.name AS client_name, c.patronymic
             FROM Account a
             INNER JOIN Client c ON c.id_client = a.id_client
             INNER JOIN Deposit d ON d.id_deposit = a.id_deposit
             UNION ALL
             SELECT 'close' AS event_type, a.close_date AS event_date, a.id_account,
                    a.account_number, d.name AS deposit_name, c.surname,
                    c.name AS client_name, c.patronymic
             FROM Account a
             INNER JOIN Client c ON c.id_client = a.id_client
             INNER JOIN Deposit d ON d.id_deposit = a.id_deposit
             WHERE a.close_date IS NOT NULL AND a.close_date <= CURRENT_DATE()
         ) AS events
         ORDER BY event_date DESC, id_account DESC
         LIMIT 3"
    )->fetchAll();
} catch (Throwable $exception) {
    error_log(sprintf('[Bank Deposits] Home page data failed: %s', $exception->getMessage()));
}

require __DIR__ . '/includes/header.php';
?>
<div class="home-dashboard home-overview">
    <section class="home-welcome" aria-labelledby="welcome-title">
        <img src="/assets/img/home-banner.png" alt="" aria-hidden="true">
        <div class="home-welcome-copy">
            <h1 id="welcome-title">Добро пожаловать!</h1>
            <h2>Система управления банковскими вкладами</h2>
        </div>
    </section>

    <section class="home-section" aria-labelledby="quick-actions-title">
        <h2 id="quick-actions-title">Быстрые действия</h2>
        <div class="home-quick-grid">
            <?php foreach ([
                ['/clients/form.php', 'Добавить клиента', 'Создание нового клиента банка'],
                ['/deposits/form.php', 'Создать вклад', 'Оформление нового вида вклада'],
                ['/report/', 'Сформировать отчёт', 'Переход к разделу отчётов'],
            ] as [$url, $label, $description]): ?>
                <a class="home-quick-card" href="<?= e($url) ?>">
                    <span class="home-quick-plus" aria-hidden="true">+</span>
                    <span class="home-quick-copy">
                        <strong><?= e($label) ?></strong>
                        <small><?= e($description) ?></small>
                    </span>
                    <span class="home-card-arrow" aria-hidden="true">›</span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="home-section" aria-labelledby="report-management-title">
        <h2 id="report-management-title">Управление банковским планом</h2>
        <div class="report-management-card">
            <span class="report-management-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M4 3h16v18H4zM8 8h8M8 12h8M8 16h5"/></svg>
            </span>
            <span class="report-management-copy">
                <strong>Перейти к отчётам</strong>
                <small>Формирование ведомостей и аналитических данных</small>
            </span>
            <a class="button button-primary report-management-button" href="/report/">Перейти к отчётам <span aria-hidden="true">›</span></a>
        </div>
    </section>

    <div class="home-recent-grid">
        <section class="home-recent-card" aria-labelledby="recent-clients-title">
            <h2 id="recent-clients-title">Последние клиенты</h2>
            <?php if ($recentClients === []): ?>
                <p class="home-recent-empty">Клиенты пока не добавлены.</p>
            <?php else: ?>
                <div class="home-recent-list">
                    <?php foreach ($recentClients as $client): ?>
                        <a class="home-recent-row" href="/clients/form.php?id=<?= e($client['id_client']) ?>">
                            <span class="home-recent-copy">
                                <strong><?= e(trim($client['surname'] . ' ' . $client['name'] . ' ' . $client['patronymic'])) ?></strong>
                                <?php if (trim((string) $client['phone']) !== ''): ?><small><?= e($client['phone']) ?></small><?php endif; ?>
                            </span>
                            <span class="home-card-arrow" aria-hidden="true">›</span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="home-recent-card" aria-labelledby="recent-operations-title">
            <h2 id="recent-operations-title">Последние операции</h2>
            <?php if ($recentOperations === []): ?>
                <p class="home-recent-empty">Операции по счетам пока отсутствуют.</p>
            <?php else: ?>
                <div class="home-recent-list">
                    <?php foreach ($recentOperations as $operation): ?>
                        <?php
                        $clientName = trim($operation['surname'] . ' ' . $operation['client_name'] . ' ' . $operation['patronymic']);
                        $operationName = $operation['event_type'] === 'close'
                            ? 'Закрыт счёт «' . $operation['account_number'] . '»'
                            : 'Открыт вклад «' . $operation['deposit_name'] . '»';
                        ?>
                        <a class="home-recent-row home-operation-row" href="/accounts/form.php?id=<?= e($operation['id_account']) ?>">
                            <span class="home-recent-copy">
                                <strong><?= e($operationName) ?></strong>
                                <small><?= e($clientName) ?></small>
                            </span>
                            <time datetime="<?= e($operation['event_date']) ?>"><?= e(format_date_ru($operation['event_date'])) ?></time>
                            <span class="home-card-arrow" aria-hidden="true">›</span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
