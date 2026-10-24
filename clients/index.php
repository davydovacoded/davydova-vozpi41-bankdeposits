<?php

declare(strict_types=1);

$config = require dirname(__DIR__) . '/includes/bootstrap.php';
$pdo = database($config['db']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash('error', 'Срок действия формы истёк. Повторите попытку.');
        redirect('/clients/');
    }

    $clientId = positive_id($_POST['id_client'] ?? null);

    if ($clientId === null) {
        flash('error', 'Не удалось определить клиента для удаления.');
        redirect('/clients/');
    }

    try {
        $statement = $pdo->prepare('DELETE FROM Client WHERE id_client = :id_client');
        $statement->execute(['id_client' => $clientId]);

        flash(
            $statement->rowCount() === 1 ? 'success' : 'error',
            $statement->rowCount() === 1
                ? 'Клиент удалён.'
                : 'Клиент не найден.'
        );
    } catch (PDOException $exception) {
        if ($exception->getCode() === '23000') {
            flash('error', 'Нельзя удалить клиента, пока с ним связаны банковские счета.');
        } else {
            error_log('[Bank Deposits] Client deletion failed: ' . $exception->getMessage());
            flash('error', 'Не удалось удалить клиента. Повторите попытку позже.');
        }
    }

    redirect('/clients/');
}

$clients = $pdo->query(
    'SELECT id_client, surname, name, patronymic, passport, phone
     FROM Client
     ORDER BY surname, name, patronymic, id_client'
)->fetchAll();

$pageTitle = 'Клиенты';
$currentSection = 'clients';
$flashMessage = consume_flash();

require dirname(__DIR__) . '/includes/header.php';
?>
<div class="records-page" data-enhanced-table>
<section class="page-heading records-heading">
    <h1>Клиенты</h1>
    <a class="button button-primary records-add-button" href="/clients/form.php">+ Добавить клиента</a>
</section>

<?php if ($flashMessage !== null): ?>
    <div class="alert alert-<?= $flashMessage['type'] === 'success' ? 'success' : 'error' ?>" role="status">
        <?= e($flashMessage['message'] ?? '') ?>
    </div>
<?php endif; ?>

<section class="records-section" aria-labelledby="clients-list-title">
    <div class="records-toolbar">
        <h2 id="clients-list-title">Список клиентов</h2>
        <label class="table-search"><span class="visually-hidden">Поиск по клиентам</span><input type="search" placeholder="Поиск по таблице" data-table-search autocomplete="off"></label>
    </div>
    <?php if ($clients === []): ?>
        <div class="empty-state">Клиенты пока не добавлены.</div>
    <?php else: ?>
        <div class="table-wrap records-table-wrap">
            <table class="data-table">
                <thead>
                <tr>
                    <th><button class="sort-button" type="button" data-sort-key="id" data-sort-type="number">ID <span class="sort-indicator" aria-hidden="true">↕</span></button></th>
                    <th><button class="sort-button" type="button" data-sort-key="surname">Фамилия <span class="sort-indicator" aria-hidden="true">↕</span></button></th>
                    <th><button class="sort-button" type="button" data-sort-key="name">Имя <span class="sort-indicator" aria-hidden="true">↕</span></button></th>
                    <th><button class="sort-button" type="button" data-sort-key="patronymic">Отчество <span class="sort-indicator" aria-hidden="true">↕</span></button></th>
                    <th><button class="sort-button" type="button" data-sort-key="passport">Паспорт <span class="sort-indicator" aria-hidden="true">↕</span></button></th>
                    <th><button class="sort-button" type="button" data-sort-key="phone">Телефон <span class="sort-indicator" aria-hidden="true">↕</span></button></th>
                    <th class="row-actions-heading"><span class="visually-hidden">Действия</span></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($clients as $client): ?>
                    <tr data-table-row>
                        <td data-column="id" data-sort-value="<?= e($client['id_client']) ?>"><?= e($client['id_client']) ?></td>
                        <td data-column="surname"><strong><?= e($client['surname']) ?></strong></td>
                        <td data-column="name"><?= e($client['name']) ?></td>
                        <td data-column="patronymic"><?= e($client['patronymic']) ?></td>
                        <td data-column="passport"><?= e($client['passport']) ?></td>
                        <td data-column="phone"><?= e($client['phone']) ?></td>
                        <td class="row-actions-cell">
                            <div class="row-actions">
                                <a class="icon-action icon-action-edit" href="/clients/form.php?id=<?= e($client['id_client']) ?>" aria-label="Редактировать клиента <?= e($client['surname']) ?>">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L8 18l-4 1 1-4z"/></svg>
                                </a>
                                <form method="post"
                                      onsubmit="return confirm('Удалить клиента? Это действие нельзя отменить.');">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="id_client" value="<?= e($client['id_client']) ?>">
                                    <button class="icon-action icon-action-delete" type="submit" aria-label="Удалить клиента <?= e($client['surname']) ?>">
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2M19 6l-1 15H6L5 6M10 11v6M14 11v6"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
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
