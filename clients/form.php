<?php

declare(strict_types=1);

$config = require dirname(__DIR__) . '/includes/bootstrap.php';
$pdo = database($config['db']);

$clientId = positive_id($_GET['id'] ?? $_POST['id_client'] ?? null);
$isEdit = $clientId !== null;
$errors = [];
$values = [
    'surname' => '',
    'name' => '',
    'patronymic' => '',
    'passport' => '',
    'phone' => '',
];

if ($isEdit && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $statement = $pdo->prepare(
        'SELECT surname, name, patronymic, passport, phone
         FROM Client
         WHERE id_client = :id_client'
    );
    $statement->execute(['id_client' => $clientId]);
    $client = $statement->fetch();

    if ($client === false) {
        flash('error', 'Клиент не найден.');
        redirect('/clients/');
    }

    $values = $client;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (array_keys($values) as $field) {
        $values[$field] = trim((string) ($_POST[$field] ?? ''));
    }

    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $errors['form'] = 'Срок действия формы истёк. Обновите страницу и повторите попытку.';
    }

    $nameLabels = [
        'surname' => 'Фамилия',
        'name' => 'Имя',
        'patronymic' => 'Отчество',
    ];

    foreach ($nameLabels as $field => $label) {
        if ($values[$field] === '') {
            $errors[$field] = $label . ' обязательно для заполнения.';
        } elseif (text_length($values[$field]) > 50) {
            $errors[$field] = $label . ' не должно превышать 50 символов.';
        } elseif (!preg_match('/^[\p{L} -]+$/u', $values[$field])) {
            $errors[$field] = $label . ' может содержать только буквы, пробел и дефис.';
        }
    }

    if ($values['passport'] === '') {
        $errors['passport'] = 'Паспорт обязателен для заполнения.';
    } elseif (text_length($values['passport']) > 20) {
        $errors['passport'] = 'Паспорт не должен превышать 20 символов.';
    } elseif (!preg_match('/^[\p{L}\p{N} -]{5,20}$/u', $values['passport'])) {
        $errors['passport'] = 'Используйте от 5 до 20 букв, цифр, пробелов или дефисов.';
    }

    if ($values['phone'] === '') {
        $errors['phone'] = 'Телефон обязателен для заполнения.';
    } elseif (!preg_match('/^[0-9+() -]{7,20}$/', $values['phone'])) {
        $errors['phone'] = 'Введите от 7 до 20 цифр; допустимы +, скобки, пробелы и дефисы.';
    }

    if (!isset($errors['passport'])) {
        $duplicateSql = 'SELECT COUNT(*) FROM Client WHERE passport = :passport';
        $duplicateParams = ['passport' => $values['passport']];

        if ($isEdit) {
            $duplicateSql .= ' AND id_client <> :id_client';
            $duplicateParams['id_client'] = $clientId;
        }

        $duplicateStatement = $pdo->prepare($duplicateSql);
        $duplicateStatement->execute($duplicateParams);

        if ((int) $duplicateStatement->fetchColumn() > 0) {
            $errors['passport'] = 'Клиент с таким паспортом уже существует.';
        }
    }

    if ($errors === []) {
        if ($isEdit) {
            $statement = $pdo->prepare(
                'UPDATE Client
                 SET surname = :surname,
                     name = :name,
                     patronymic = :patronymic,
                     passport = :passport,
                     phone = :phone
                 WHERE id_client = :id_client'
            );
            $statement->execute($values + ['id_client' => $clientId]);
            flash('success', 'Данные клиента обновлены.');
        } else {
            $statement = $pdo->prepare(
                'INSERT INTO Client (surname, name, patronymic, passport, phone)
                 VALUES (:surname, :name, :patronymic, :passport, :phone)'
            );
            $statement->execute($values);
            flash('success', 'Клиент добавлен.');
        }

        redirect('/clients/');
    }
}

$pageTitle = $isEdit ? 'Редактирование клиента' : 'Новый клиент';
$currentSection = 'clients';

require dirname(__DIR__) . '/includes/header.php';
?>
<section class="page-heading">
    <div>
        <h1><?= $isEdit ? 'Редактирование клиента' : 'Новый клиент' ?></h1>
        <p>Заполните все поля. Проверка выполняется на сервере.</p>
    </div>
    <a class="button button-secondary" href="/clients/">Назад к списку</a>
</section>

<?php if (isset($errors['form'])): ?>
    <div class="alert alert-error" role="alert"><?= e($errors['form']) ?></div>
<?php endif; ?>

<form class="form-card" method="post" novalidate>
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <?php if ($isEdit): ?>
        <input type="hidden" name="id_client" value="<?= e($clientId) ?>">
    <?php endif; ?>

    <div class="form-grid">
        <?php
        $fields = [
            'surname' => ['Фамилия', 'Например, Иванова', 'text', '50'],
            'name' => ['Имя', 'Например, Анна', 'text', '50'],
            'patronymic' => ['Отчество', 'Например, Сергеевна', 'text', '50'],
            'passport' => ['Паспорт', 'Например, 4510 123456', 'text', '20'],
            'phone' => ['Телефон', 'Например, +7 (999) 123-45-67', 'tel', '20'],
        ];
        ?>
        <?php foreach ($fields as $field => [$label, $placeholder, $type, $maxLength]): ?>
            <div class="form-field <?= $field === 'phone' ? 'form-field-full' : '' ?>">
                <label for="<?= e($field) ?>"><?= e($label) ?></label>
                <input
                    id="<?= e($field) ?>"
                    name="<?= e($field) ?>"
                    type="<?= e($type) ?>"
                    value="<?= e($values[$field]) ?>"
                    placeholder="<?= e($placeholder) ?>"
                    maxlength="<?= e($maxLength) ?>"
                    required
                    <?= isset($errors[$field]) ? 'aria-invalid="true" aria-describedby="error-' . e($field) . '"' : '' ?>
                >
                <?php if (isset($errors[$field])): ?>
                    <p class="field-error" id="error-<?= e($field) ?>"><?= e($errors[$field]) ?></p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="form-actions">
        <button class="button button-primary" type="submit">
            <?= $isEdit ? 'Сохранить изменения' : 'Добавить клиента' ?>
        </button>
        <a class="button button-secondary" href="/clients/">Отмена</a>
    </div>
</form>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
