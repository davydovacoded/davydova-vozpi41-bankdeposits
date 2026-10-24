<?php

declare(strict_types=1);

$config = require dirname(__DIR__) . '/includes/bootstrap.php';
$pdo = database($config['db']);

$availableYears = array_map(
    'intval',
    $pdo->query(
        'SELECT DISTINCT YEAR(close_date) AS close_year
         FROM Account
         WHERE close_date IS NOT NULL
         ORDER BY close_year'
    )->fetchAll(PDO::FETCH_COLUMN)
);

$requestedYear = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1900, 'max_range' => 2100],
]);
$currentYear = (int) date('Y');
$selectedYear = $requestedYear !== false && $requestedYear !== null
    ? (int) $requestedYear
    : (in_array($currentYear, $availableYears, true) ? $currentYear : ($availableYears[0] ?? $currentYear));

$statement = $pdo->prepare(
    'SELECT a.id_account, a.close_date, a.amount,
            c.id_client, c.surname, c.name AS client_name, c.patronymic,
            d.name AS deposit_name, d.term, d.interest_rate,
            ROUND(a.amount * (d.term * d.interest_rate / 12) / 100, 2) AS accumulation
     FROM Account a
     INNER JOIN Client c ON c.id_client = a.id_client
     INNER JOIN Deposit d ON d.id_deposit = a.id_deposit
     WHERE a.close_date >= :date_from AND a.close_date < :date_to
     ORDER BY c.surname, c.name, c.patronymic, a.close_date, a.id_account'
);
$statement->execute([
    'date_from' => sprintf('%04d-01-01', $selectedYear),
    'date_to' => sprintf('%04d-01-01', $selectedYear + 1),
]);
$rows = $statement->fetchAll();

$clients = [];
$bankAmount = 0.0;
$bankAccumulation = 0.0;
foreach ($rows as $row) {
    $clientId = (int) $row['id_client'];
    if (!isset($clients[$clientId])) {
        $clients[$clientId] = [
            'full_name' => $row['surname'] . ' ' . $row['client_name'] . ' ' . $row['patronymic'],
            'rows' => [],
            'amount' => 0.0,
            'accumulation' => 0.0,
        ];
    }
    $clients[$clientId]['rows'][] = $row;
    $clients[$clientId]['amount'] += (float) $row['amount'];
    $clients[$clientId]['accumulation'] += (float) $row['accumulation'];
    $bankAmount += (float) $row['amount'];
    $bankAccumulation += (float) $row['accumulation'];
}

$pageTitle = 'Ведомость доходов';
$currentSection = 'report';
$accountCountLabel = static function (int $count): string {
    $lastTwo = $count % 100;
    $last = $count % 10;
    if ($lastTwo >= 11 && $lastTwo <= 14) {
        return $count . ' закрытых счетов';
    }
    if ($last === 1) {
        return $count . ' закрытый счёт';
    }
    if ($last >= 2 && $last <= 4) {
        return $count . ' закрытых счёта';
    }
    return $count . ' закрытых счетов';
};
require dirname(__DIR__) . '/includes/header.php';
?>
<div class="report-page">
<section class="page-heading report-page-heading">
    <div>
        <h1>Ведомость получения доходов</h1>
        <p>По закрытым счетам клиентов банка за <?= e($selectedYear) ?> год.</p>
    </div>
</section>

<div class="report-toolbar">
    <div class="report-toolbar-heading">
        <label for="year">Год закрытия счетов</label>
        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01M16 18h.01"/></svg>
    </div>
    <form class="report-filter" method="get">
        <select id="year" name="year">
            <?php foreach ($availableYears as $year): ?>
                <option value="<?= e($year) ?>" <?= $selectedYear === $year ? 'selected' : '' ?>><?= e($year) ?></option>
            <?php endforeach; ?>
            <?php if ($availableYears === []): ?><option value="<?= e($selectedYear) ?>"><?= e($selectedYear) ?></option><?php endif; ?>
        </select>
        <button class="button button-primary report-action-button" type="submit">Сформировать</button>
        <button class="button button-secondary report-action-button report-print-button" type="button" onclick="window.print()">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 9V3h10v6M7 18H5a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2M7 14h10v7H7z"/></svg>
            <span>Печать</span>
        </button>
    </form>
</div>

<div class="report-note">
    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7h.01"/></svg>
    <span>Расчет по формуле методических указаний</span>
</div>

<?php if ($clients === []): ?>
    <div class="table-card empty-state report-empty">За выбранный год закрытые счета не найдены</div>
<?php else: ?>
    <?php foreach ($clients as $client): ?>
        <section class="table-card report-client" aria-label="Клиент <?= e($client['full_name']) ?>">
            <div class="report-client-heading">
                <h2><?= e($client['full_name']) ?></h2>
                <span class="muted"><?= e($accountCountLabel(count($client['rows']))) ?></span>
            </div>
            <div class="report-client-content">
                <?php foreach ($client['rows'] as $row): ?>
                    <article class="report-account">
                        <div class="report-account-grid">
                            <div class="report-field"><span>Вклад</span><strong><?= e($row['deposit_name']) ?></strong></div>
                            <div class="report-field"><span>Дата закрытия</span><strong><?= e(format_date_ru($row['close_date'])) ?></strong></div>
                            <div class="report-field"><span>Срок / ставка</span><strong><?= e($row['term']) ?> мес. / <?= format_number($row['interest_rate'], 2) ?>%</strong></div>
                            <div class="report-field"><span>Вложенная сумма</span><strong><?= format_number($row['amount'], 2) ?> ₽</strong></div>
                        </div>
                        <div class="report-account-accumulation">
                            <span>Сумма накопления</span>
                            <strong><?= format_number($row['accumulation'], 2) ?> ₽</strong>
                        </div>
                    </article>
                <?php endforeach; ?>
                <?php if (count($client['rows']) > 1): ?>
                    <div class="report-client-total">
                        <span>Итого по клиенту</span>
                        <div><small>Вложено</small><strong><?= format_number($client['amount'], 2) ?> ₽</strong></div>
                        <div><small>Накоплено</small><strong><?= format_number($client['accumulation'], 2) ?> ₽</strong></div>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    <?php endforeach; ?>

    <section class="bank-total" aria-label="Общие итоги по банку">
        <div><span>Общая вложенная сумма</span><strong><?= format_number($bankAmount, 2) ?> ₽</strong></div>
        <div><span>Общая сумма накопления</span><strong><?= format_number($bankAccumulation, 2) ?> ₽</strong></div>
    </section>
<?php endif; ?>
</div>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
