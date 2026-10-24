<?php

declare(strict_types=1);

function database(array $databaseConfig): PDO
{
    static $connection = null;

    if ($connection instanceof PDO) {
        return $connection;
    }

    $requiredKeys = ['host', 'port', 'name', 'user', 'password', 'charset'];

    foreach ($requiredKeys as $key) {
        if (!array_key_exists($key, $databaseConfig) || $databaseConfig[$key] === '') {
            throw new RuntimeException('Не заполнен параметр подключения к базе данных.');
        }
    }

    if ($databaseConfig['password'] === 'CHANGE_ME_IN_INFINITYFREE') {
        throw new RuntimeException('Пароль базы данных ещё не настроен.');
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $databaseConfig['host'],
        (int) $databaseConfig['port'],
        $databaseConfig['name'],
        $databaseConfig['charset']
    );

    $connection = new PDO(
        $dsn,
        $databaseConfig['user'],
        $databaseConfig['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
        ]
    );

    return $connection;
}
