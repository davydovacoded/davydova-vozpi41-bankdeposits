SET NAMES utf8mb4;

CREATE TABLE Client (
    id_client INT NOT NULL AUTO_INCREMENT,
    surname VARCHAR(50) NOT NULL,
    name VARCHAR(50) NOT NULL,
    patronymic VARCHAR(50) NOT NULL,
    passport VARCHAR(20) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    PRIMARY KEY (id_client)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE Deposit (
    id_deposit INT NOT NULL AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    term INT NOT NULL,
    interest_rate DECIMAL(5,2) NOT NULL,
    PRIMARY KEY (id_deposit)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE Account (
    id_account INT NOT NULL AUTO_INCREMENT,
    account_number VARCHAR(20) NOT NULL,
    open_date DATE NOT NULL,
    close_date DATE NULL,
    amount DECIMAL(12,2) NOT NULL,
    id_client INT NOT NULL,
    id_deposit INT NOT NULL,
    PRIMARY KEY (id_account),
    CONSTRAINT fk_account_client
        FOREIGN KEY (id_client)
        REFERENCES Client (id_client)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_account_deposit
        FOREIGN KEY (id_deposit)
        REFERENCES Deposit (id_deposit)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
