SELECT 'Client' AS table_name, COUNT(*) AS row_count FROM Client
UNION ALL
SELECT 'Deposit', COUNT(*) FROM Deposit
UNION ALL
SELECT 'Account', COUNT(*) FROM Account;

SELECT
    account.id_account,
    account.account_number,
    CONCAT_WS(' ', client.surname, client.name, client.patronymic) AS client_name,
    deposit.name AS deposit_name,
    account.open_date,
    account.close_date,
    account.amount
FROM Account AS account
INNER JOIN Client AS client
    ON client.id_client = account.id_client
INNER JOIN Deposit AS deposit
    ON deposit.id_deposit = account.id_deposit
ORDER BY account.id_account;

SELECT DISTINCT YEAR(close_date) AS report_year
FROM Account
WHERE close_date IS NOT NULL
ORDER BY report_year;
