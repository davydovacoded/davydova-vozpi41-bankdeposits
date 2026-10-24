UPDATE Account AS account
INNER JOIN Deposit AS deposit
    ON deposit.id_deposit = account.id_deposit
SET account.close_date = DATE_ADD(
    account.open_date,
    INTERVAL deposit.term MONTH
)
WHERE account.close_date IS NULL;
