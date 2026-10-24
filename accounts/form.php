<?php

declare(strict_types=1);

$config = require dirname(__DIR__) . '/includes/bootstrap.php';
$pdo = database($config['db']);
$accountId = positive_id($_GET['id'] ?? $_POST['id_account'] ?? null);
$isEdit = $accountId !== null;
$errors = [];
$values = [
    'account_number' => '',
    'open_date' => '',
    'close_date' => '',
    'amount' => '',
    'id_client' => '',
    'id_deposit' => '',
];
$clients = $pdo->query('SELECT id_client, surname, name, patronymic FROM Client ORDER BY surname, name, patronymic')->fetchAll();
$deposits = $pdo->query('SELECT id_deposit, name, term, interest_rate FROM Deposit ORDER BY name')->fetchAll();

if ($isEdit && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $statement = $pdo->prepare(
        'SELECT account_number, open_date, close_date, amount, id_client, id_deposit
         FROM Account WHERE id_account = :id_account'
    );
    $statement->execute(['id_account' => $accountId]);
    $account = $statement->fetch();
    if ($account === false) {
        flash('error', 'Счёт не найден.');
        redirect('/accounts/');
    }
    $values = $account;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (array_keys($values) as $field) {
        $values[$field] = trim((string) ($_POST[$field] ?? ''));
    }
    $values['amount'] = str_replace(',', '.', $values['amount']);

    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $errors['form'] = 'Срок действия формы истёк. Обновите страницу и повторите попытку.';
    }
    if (!preg_match('/^\d{20}$/', $values['account_number'])) {
        $errors['account_number'] = 'Номер счёта должен содержать ровно 20 цифр.';
    }
    if (!valid_date($values['open_date'])) {
        $errors['open_date'] = 'Укажите корректную дату открытия.';
    }
    if ($values['close_date'] !== '' && !valid_date($values['close_date'])) {
        $errors['close_date'] = 'Укажите корректную дату закрытия.';
    } elseif ($values['close_date'] !== '' && valid_date($values['open_date']) && $values['close_date'] < $values['open_date']) {
        $errors['close_date'] = 'Дата закрытия не может быть раньше даты открытия.';
    }
    if (!preg_match('/^\d{1,10}(?:\.\d{1,2})?$/', $values['amount']) || (float) $values['amount'] <= 0) {
        $errors['amount'] = 'Введите положительную сумму с точностью до двух знаков.';
    }

    $clientId = positive_id($values['id_client']);
    $depositId = positive_id($values['id_deposit']);
    if ($clientId === null) {
        $errors['id_client'] = 'Выберите клиента.';
    } else {
        $statement = $pdo->prepare('SELECT COUNT(*) FROM Client WHERE id_client = :id_client');
        $statement->execute(['id_client' => $clientId]);
        if ((int) $statement->fetchColumn() !== 1) {
            $errors['id_client'] = 'Выбранный клиент не найден.';
        }
    }
    if ($depositId === null) {
        $errors['id_deposit'] = 'Выберите вклад.';
    } else {
        $statement = $pdo->prepare('SELECT COUNT(*) FROM Deposit WHERE id_deposit = :id_deposit');
        $statement->execute(['id_deposit' => $depositId]);
        if ((int) $statement->fetchColumn() !== 1) {
            $errors['id_deposit'] = 'Выбранный вклад не найден.';
        }
    }

    if (!isset($errors['account_number'])) {
        $sql = 'SELECT COUNT(*) FROM Account WHERE account_number = :account_number';
        $params = ['account_number' => $values['account_number']];
        if ($isEdit) {
            $sql .= ' AND id_account <> :id_account';
            $params['id_account'] = $accountId;
        }
        $statement = $pdo->prepare($sql);
        $statement->execute($params);
        if ((int) $statement->fetchColumn() > 0) {
            $errors['account_number'] = 'Счёт с таким номером уже существует.';
        }
    }

    if ($errors === []) {
        $params = [
            'account_number' => $values['account_number'],
            'open_date' => $values['open_date'],
            'close_date' => $values['close_date'] === '' ? null : $values['close_date'],
            'amount' => number_format((float) $values['amount'], 2, '.', ''),
            'id_client' => $clientId,
            'id_deposit' => $depositId,
        ];
        if ($isEdit) {
            $statement = $pdo->prepare(
                'UPDATE Account SET account_number = :account_number, open_date = :open_date,
                 close_date = :close_date, amount = :amount, id_client = :id_client, id_deposit = :id_deposit
                 WHERE id_account = :id_account'
            );
            $statement->execute($params + ['id_account' => $accountId]);
            flash('success', 'Данные счёта обновлены.');
        } else {
            $statement = $pdo->prepare(
                'INSERT INTO Account (account_number, open_date, close_date, amount, id_client, id_deposit)
                 VALUES (:account_number, :open_date, :close_date, :amount, :id_client, :id_deposit)'
            );
            $statement->execute($params);
            flash('success', 'Счёт добавлен.');
        }
        redirect('/accounts/');
    }
}

$pageTitle = $isEdit ? 'Редактирование счёта' : 'Новый счёт';
$currentSection = 'accounts';
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="page-heading">
    <div><h1><?= $isEdit ? 'Редактирование счёта' : 'Новый счёт' ?></h1><p>Выберите клиента и вклад, затем заполните параметры счёта.</p></div>
    <a class="button button-secondary" href="/accounts/">Назад к списку</a>
</section>
<?php if (isset($errors['form'])): ?><div class="alert alert-error" role="alert"><?= e($errors['form']) ?></div><?php endif; ?>
<form class="form-card" method="post" novalidate>
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <?php if ($isEdit): ?><input type="hidden" name="id_account" value="<?= e($accountId) ?>"><?php endif; ?>
    <div class="form-grid">
        <div class="form-field form-field-full"><label for="account_number">Номер счёта</label><input id="account_number" name="account_number" inputmode="numeric" maxlength="20" value="<?= e($values['account_number']) ?>" required <?= isset($errors['account_number']) ? 'aria-invalid="true"' : '' ?>><?php if (isset($errors['account_number'])): ?><p class="field-error"><?= e($errors['account_number']) ?></p><?php endif; ?></div>
        <div class="form-field"><label for="id_client">Клиент</label><select id="id_client" name="id_client" required <?= isset($errors['id_client']) ? 'aria-invalid="true"' : '' ?>><option value="">Выберите клиента</option><?php foreach ($clients as $client): ?><option value="<?= e($client['id_client']) ?>" <?= (string) $values['id_client'] === (string) $client['id_client'] ? 'selected' : '' ?>><?= e($client['surname'] . ' ' . $client['name'] . ' ' . $client['patronymic']) ?></option><?php endforeach; ?></select><?php if (isset($errors['id_client'])): ?><p class="field-error"><?= e($errors['id_client']) ?></p><?php endif; ?></div>
        <div class="form-field"><label for="id_deposit">Вклад</label><select id="id_deposit" name="id_deposit" required <?= isset($errors['id_deposit']) ? 'aria-invalid="true"' : '' ?>><option value="">Выберите вклад</option><?php foreach ($deposits as $deposit): ?><option value="<?= e($deposit['id_deposit']) ?>" <?= (string) $values['id_deposit'] === (string) $deposit['id_deposit'] ? 'selected' : '' ?>><?= e($deposit['name']) ?> — <?= e($deposit['term']) ?> мес., <?= format_number($deposit['interest_rate'], 2) ?>%</option><?php endforeach; ?></select><?php if (isset($errors['id_deposit'])): ?><p class="field-error"><?= e($errors['id_deposit']) ?></p><?php endif; ?></div>
        <div class="form-field"><label for="open_date">Дата открытия</label><input id="open_date" name="open_date" type="date" value="<?= e($values['open_date']) ?>" required <?= isset($errors['open_date']) ? 'aria-invalid="true"' : '' ?>><?php if (isset($errors['open_date'])): ?><p class="field-error"><?= e($errors['open_date']) ?></p><?php endif; ?></div>
        <div class="form-field"><label for="close_date">Дата закрытия</label><input id="close_date" name="close_date" type="date" value="<?= e($values['close_date']) ?>" <?= isset($errors['close_date']) ? 'aria-invalid="true"' : '' ?>><span class="field-hint">Оставьте пустым для действующего счёта.</span><?php if (isset($errors['close_date'])): ?><p class="field-error"><?= e($errors['close_date']) ?></p><?php endif; ?></div>
        <div class="form-field form-field-full"><label for="amount">Вложенная сумма, ₽</label><input id="amount" name="amount" inputmode="decimal" value="<?= e($values['amount']) ?>" required <?= isset($errors['amount']) ? 'aria-invalid="true"' : '' ?>><?php if (isset($errors['amount'])): ?><p class="field-error"><?= e($errors['amount']) ?></p><?php endif; ?></div>
    </div>
    <div class="form-actions"><button class="button button-primary" type="submit"><?= $isEdit ? 'Сохранить изменения' : 'Добавить счёт' ?></button><a class="button button-secondary" href="/accounts/">Отмена</a></div>
</form>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
