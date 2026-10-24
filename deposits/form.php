<?php

declare(strict_types=1);

$config = require dirname(__DIR__) . '/includes/bootstrap.php';
$pdo = database($config['db']);
$depositId = positive_id($_GET['id'] ?? $_POST['id_deposit'] ?? null);
$isEdit = $depositId !== null;
$errors = [];
$values = ['name' => '', 'term' => '', 'interest_rate' => ''];

if ($isEdit && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $statement = $pdo->prepare('SELECT name, term, interest_rate FROM Deposit WHERE id_deposit = :id_deposit');
    $statement->execute(['id_deposit' => $depositId]);
    $deposit = $statement->fetch();
    if ($deposit === false) {
        flash('error', 'Вклад не найден.');
        redirect('/deposits/');
    }
    $values = $deposit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values['name'] = trim((string) ($_POST['name'] ?? ''));
    $values['term'] = trim((string) ($_POST['term'] ?? ''));
    $values['interest_rate'] = str_replace(',', '.', trim((string) ($_POST['interest_rate'] ?? '')));

    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $errors['form'] = 'Срок действия формы истёк. Обновите страницу и повторите попытку.';
    }
    if ($values['name'] === '') {
        $errors['name'] = 'Название обязательно для заполнения.';
    } elseif (text_length($values['name']) > 50) {
        $errors['name'] = 'Название не должно превышать 50 символов.';
    }

    $term = filter_var($values['term'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 600]]);
    if ($term === false) {
        $errors['term'] = 'Введите целый срок от 1 до 600 месяцев.';
    }

    if (!preg_match('/^\d{1,3}(?:\.\d{1,2})?$/', $values['interest_rate'])) {
        $errors['interest_rate'] = 'Введите ставку с точностью до двух знаков.';
    } elseif ((float) $values['interest_rate'] <= 0 || (float) $values['interest_rate'] > 100) {
        $errors['interest_rate'] = 'Ставка должна быть больше 0 и не превышать 100%.';
    }

    if (!isset($errors['name'])) {
        $sql = 'SELECT COUNT(*) FROM Deposit WHERE name = :name';
        $params = ['name' => $values['name']];
        if ($isEdit) {
            $sql .= ' AND id_deposit <> :id_deposit';
            $params['id_deposit'] = $depositId;
        }
        $statement = $pdo->prepare($sql);
        $statement->execute($params);
        if ((int) $statement->fetchColumn() > 0) {
            $errors['name'] = 'Вклад с таким названием уже существует.';
        }
    }

    if ($errors === []) {
        $params = [
            'name' => $values['name'],
            'term' => (int) $term,
            'interest_rate' => number_format((float) $values['interest_rate'], 2, '.', ''),
        ];
        if ($isEdit) {
            $statement = $pdo->prepare('UPDATE Deposit SET name = :name, term = :term, interest_rate = :interest_rate WHERE id_deposit = :id_deposit');
            $statement->execute($params + ['id_deposit' => $depositId]);
            flash('success', 'Данные вклада обновлены.');
        } else {
            $statement = $pdo->prepare('INSERT INTO Deposit (name, term, interest_rate) VALUES (:name, :term, :interest_rate)');
            $statement->execute($params);
            flash('success', 'Вклад добавлен.');
        }
        redirect('/deposits/');
    }
}

$pageTitle = $isEdit ? 'Редактирование вклада' : 'Новый вклад';
$currentSection = 'deposits';
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="page-heading">
    <div><h1><?= $isEdit ? 'Редактирование вклада' : 'Новый вклад' ?></h1><p>Укажите название, срок и годовую ставку.</p></div>
    <a class="button button-secondary" href="/deposits/">Назад к списку</a>
</section>
<?php if (isset($errors['form'])): ?><div class="alert alert-error" role="alert"><?= e($errors['form']) ?></div><?php endif; ?>
<form class="form-card" method="post" novalidate>
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <?php if ($isEdit): ?><input type="hidden" name="id_deposit" value="<?= e($depositId) ?>"><?php endif; ?>
    <div class="form-grid">
        <div class="form-field form-field-full">
            <label for="name">Название</label>
            <input id="name" name="name" value="<?= e($values['name']) ?>" maxlength="50" required <?= isset($errors['name']) ? 'aria-invalid="true"' : '' ?>>
            <?php if (isset($errors['name'])): ?><p class="field-error"><?= e($errors['name']) ?></p><?php endif; ?>
        </div>
        <div class="form-field">
            <label for="term">Срок хранения, месяцев</label>
            <input id="term" name="term" type="number" min="1" max="600" step="1" value="<?= e($values['term']) ?>" required <?= isset($errors['term']) ? 'aria-invalid="true"' : '' ?>>
            <?php if (isset($errors['term'])): ?><p class="field-error"><?= e($errors['term']) ?></p><?php endif; ?>
        </div>
        <div class="form-field">
            <label for="interest_rate">Ставка годовых, %</label>
            <input id="interest_rate" name="interest_rate" inputmode="decimal" value="<?= e($values['interest_rate']) ?>" required <?= isset($errors['interest_rate']) ? 'aria-invalid="true"' : '' ?>>
            <?php if (isset($errors['interest_rate'])): ?><p class="field-error"><?= e($errors['interest_rate']) ?></p><?php endif; ?>
        </div>
    </div>
    <div class="form-actions"><button class="button button-primary" type="submit"><?= $isEdit ? 'Сохранить изменения' : 'Добавить вклад' ?></button><a class="button button-secondary" href="/deposits/">Отмена</a></div>
</form>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
