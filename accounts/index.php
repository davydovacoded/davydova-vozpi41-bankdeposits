<?php

declare(strict_types=1);

$config = require dirname(__DIR__) . '/includes/bootstrap.php';
$pdo = database($config['db']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash('error', 'Срок действия формы истёк. Повторите попытку.');
        redirect('/accounts/');
    }

    $accountId = positive_id($_POST['id_account'] ?? null);
    if ($accountId === null) {
        flash('error', 'Не удалось определить счёт для удаления.');
        redirect('/accounts/');
    }

    try {
        $statement = $pdo->prepare('DELETE FROM Account WHERE id_account = :id_account');
        $statement->execute(['id_account' => $accountId]);
        flash(
            $statement->rowCount() === 1 ? 'success' : 'error',
            $statement->rowCount() === 1 ? 'Счёт удалён.' : 'Счёт не найден.'
        );
    } catch (PDOException $exception) {
        error_log('[Bank Deposits] Account deletion failed: ' . $exception->getMessage());
        flash('error', 'Не удалось удалить счёт. Повторите попытку позже.');
    }
    redirect('/accounts/');
}

$accounts = $pdo->query(
    'SELECT a.id_account, a.account_number, a.open_date, a.close_date, a.amount,
            CONCAT(c.surname, " ", c.name, " ", c.patronymic) AS client_name,
            d.name AS deposit_name
     FROM Account a
     INNER JOIN Client c ON c.id_client = a.id_client
     INNER JOIN Deposit d ON d.id_deposit = a.id_deposit
     ORDER BY a.id_account'
)->fetchAll();

$today = date('Y-m-d');

$pageTitle = 'Счета';
$currentSection = 'accounts';
$flashMessage = consume_flash();
require dirname(__DIR__) . '/includes/header.php';
?>
<div class="records-page" data-enhanced-table>
<section class="page-heading records-heading">
    <h1>Счета</h1>
    <a class="button button-primary records-add-button" href="/accounts/form.php">+ Открыть счёт</a>
</section>
<?php if ($flashMessage !== null): ?>
    <div class="alert alert-<?= $flashMessage['type'] === 'success' ? 'success' : 'error' ?>" role="status"><?= e($flashMessage['message'] ?? '') ?></div>
<?php endif; ?>
<section class="records-section" aria-labelledby="accounts-list-title">
    <div class="records-toolbar">
        <h2 id="accounts-list-title">Список счетов</h2>
        <label class="table-search"><span class="visually-hidden">Поиск по счетам</span><input type="search" placeholder="Поиск по таблице" data-table-search autocomplete="off"></label>
    </div>
    <?php if ($accounts === []): ?>
        <div class="empty-state">Счета пока не добавлены.</div>
    <?php else: ?>
    <div class="table-wrap records-table-wrap"><table class="data-table accounts-table">
        <thead><tr>
            <th><button class="sort-button" type="button" data-sort-key="id" data-sort-type="number">ID <span class="sort-indicator" aria-hidden="true">↕</span></button></th>
            <th><button class="sort-button" type="button" data-sort-key="number">Номер счёта <span class="sort-indicator" aria-hidden="true">↕</span></button></th>
            <th><button class="sort-button" type="button" data-sort-key="client">Клиент <span class="sort-indicator" aria-hidden="true">↕</span></button></th>
            <th><button class="sort-button" type="button" data-sort-key="deposit">Вклад <span class="sort-indicator" aria-hidden="true">↕</span></button></th>
            <th><button class="sort-button" type="button" data-sort-key="opened" data-sort-type="date">Дата открытия <span class="sort-indicator" aria-hidden="true">↕</span></button></th>
            <th><button class="sort-button" type="button" data-sort-key="closed" data-sort-type="date">Дата закрытия <span class="sort-indicator" aria-hidden="true">↕</span></button></th>
            <th><button class="sort-button" type="button" data-sort-key="amount" data-sort-type="number">Сумма <span class="sort-indicator" aria-hidden="true">↕</span></button></th>
            <th><button class="sort-button" type="button" data-sort-key="status">Статус <span class="sort-indicator" aria-hidden="true">↕</span></button></th>
            <th class="row-actions-heading"><span class="visually-hidden">Действия</span></th>
        </tr></thead>
        <tbody>
        <?php foreach ($accounts as $account): ?>
            <?php
            $closeDate = $account['close_date'];
            $isOpen = $closeDate === null || $closeDate === '' || $closeDate > $today;
            ?>
            <tr data-table-row>
                <td data-column="id" data-sort-value="<?= e($account['id_account']) ?>"><?= e($account['id_account']) ?></td>
                <td data-column="number"><strong><?= e($account['account_number']) ?></strong></td>
                <td data-column="client"><?= e($account['client_name']) ?></td>
                <td data-column="deposit"><?= e($account['deposit_name']) ?></td>
                <td data-column="opened" data-sort-value="<?= e($account['open_date']) ?>"><?= e(format_date_ru($account['open_date'])) ?></td>
                <td data-column="closed" data-sort-value="<?= e($account['close_date'] ?? '') ?>"><?= e(format_date_ru($account['close_date'])) ?></td>
                <td class="amount" data-column="amount" data-sort-value="<?= e($account['amount']) ?>"><?= format_number($account['amount'], 2) ?> ₽</td>
                <td data-column="status" data-sort-value="<?= $isOpen ? 'Открыт' : 'Закрыт' ?>"><span class="account-status <?= $isOpen ? 'status-open' : 'status-closed' ?>"><?= $isOpen ? 'Открыт' : 'Закрыт' ?></span></td>
                <td class="row-actions-cell"><div class="row-actions">
                    <a class="icon-action icon-action-edit" href="/accounts/form.php?id=<?= e($account['id_account']) ?>" aria-label="Редактировать счёт <?= e($account['account_number']) ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L8 18l-4 1 1-4z"/></svg></a>
                    <form method="post" onsubmit="return confirm('Удалить счёт? Это действие нельзя отменить.');">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="id_account" value="<?= e($account['id_account']) ?>">
                        <button class="icon-action icon-action-delete" type="submit" aria-label="Удалить счёт <?= e($account['account_number']) ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2M19 6l-1 15H6L5 6M10 11v6M14 11v6"/></svg></button>
                    </form>
                </div></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table><p class="table-no-results" data-table-empty hidden>По вашему запросу ничего не найдено.</p></div>
    <?php endif; ?>
</section>
</div>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
