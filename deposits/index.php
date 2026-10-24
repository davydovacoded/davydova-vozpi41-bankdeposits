<?php

declare(strict_types=1);

$config = require dirname(__DIR__) . '/includes/bootstrap.php';
$pdo = database($config['db']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash('error', 'Срок действия формы истёк. Повторите попытку.');
        redirect('/deposits/');
    }

    $depositId = positive_id($_POST['id_deposit'] ?? null);
    if ($depositId === null) {
        flash('error', 'Не удалось определить вклад для удаления.');
        redirect('/deposits/');
    }

    try {
        $statement = $pdo->prepare('DELETE FROM Deposit WHERE id_deposit = :id_deposit');
        $statement->execute(['id_deposit' => $depositId]);
        flash(
            $statement->rowCount() === 1 ? 'success' : 'error',
            $statement->rowCount() === 1 ? 'Вклад удалён.' : 'Вклад не найден.'
        );
    } catch (PDOException $exception) {
        if ($exception->getCode() === '23000') {
            flash('error', 'Нельзя удалить вклад, пока с ним связаны банковские счета.');
        } else {
            error_log('[Bank Deposits] Deposit deletion failed: ' . $exception->getMessage());
            flash('error', 'Не удалось удалить вклад. Повторите попытку позже.');
        }
    }

    redirect('/deposits/');
}

$deposits = $pdo->query(
    'SELECT id_deposit, name, term, interest_rate FROM Deposit ORDER BY name, id_deposit'
)->fetchAll();
$pageTitle = 'Вклады';
$currentSection = 'deposits';
$flashMessage = consume_flash();

require dirname(__DIR__) . '/includes/header.php';
?>
<div class="records-page" data-enhanced-table>
<section class="page-heading records-heading">
    <h1>Вклады</h1>
    <a class="button button-primary records-add-button" href="/deposits/form.php">+ Создать вклад</a>
</section>
<?php if ($flashMessage !== null): ?>
    <div class="alert alert-<?= $flashMessage['type'] === 'success' ? 'success' : 'error' ?>" role="status"><?= e($flashMessage['message'] ?? '') ?></div>
<?php endif; ?>
<section class="records-section" aria-labelledby="deposits-list-title">
    <div class="records-toolbar">
        <h2 id="deposits-list-title">Список вкладов</h2>
        <label class="table-search"><span class="visually-hidden">Поиск по вкладам</span><input type="search" placeholder="Поиск по таблице" data-table-search autocomplete="off"></label>
    </div>
    <?php if ($deposits === []): ?>
        <div class="empty-state">Вклады пока не добавлены.</div>
    <?php else: ?>
    <div class="table-wrap records-table-wrap">
        <table class="data-table">
            <thead><tr>
                <th><button class="sort-button" type="button" data-sort-key="id" data-sort-type="number">ID <span class="sort-indicator" aria-hidden="true">↕</span></button></th>
                <th><button class="sort-button" type="button" data-sort-key="name">Название вклада <span class="sort-indicator" aria-hidden="true">↕</span></button></th>
                <th><button class="sort-button" type="button" data-sort-key="term" data-sort-type="number">Срок (мес.) <span class="sort-indicator" aria-hidden="true">↕</span></button></th>
                <th><button class="sort-button" type="button" data-sort-key="rate" data-sort-type="number">Ставка (% годовых) <span class="sort-indicator" aria-hidden="true">↕</span></button></th>
                <th class="row-actions-heading"><span class="visually-hidden">Действия</span></th>
            </tr></thead>
            <tbody>
            <?php foreach ($deposits as $deposit): ?>
                <tr data-table-row>
                    <td data-column="id" data-sort-value="<?= e($deposit['id_deposit']) ?>"><?= e($deposit['id_deposit']) ?></td>
                    <td data-column="name"><strong><?= e($deposit['name']) ?></strong></td>
                    <td data-column="term" data-sort-value="<?= e($deposit['term']) ?>"><?= e($deposit['term']) ?> мес.</td>
                    <td data-column="rate" data-sort-value="<?= e($deposit['interest_rate']) ?>"><?= format_number($deposit['interest_rate'], 2) ?>%</td>
                    <td class="row-actions-cell"><div class="row-actions">
                        <a class="icon-action icon-action-edit" href="/deposits/form.php?id=<?= e($deposit['id_deposit']) ?>" aria-label="Редактировать вклад <?= e($deposit['name']) ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L8 18l-4 1 1-4z"/></svg></a>
                        <form method="post" onsubmit="return confirm('Удалить вклад? Это действие нельзя отменить.');">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="id_deposit" value="<?= e($deposit['id_deposit']) ?>">
                            <button class="icon-action icon-action-delete" type="submit" aria-label="Удалить вклад <?= e($deposit['name']) ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2M19 6l-1 15H6L5 6M10 11v6M14 11v6"/></svg></button>
                        </form>
                    </div></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <p class="table-no-results" data-table-empty hidden>По вашему запросу ничего не найдено.</p>
    </div>
    <?php endif; ?>
</section>
</div>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
