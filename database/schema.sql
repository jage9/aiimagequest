-- AI Image Quest Database Schema
-- Usage: mysql -u <user> -p <database> < database/schema.sql

SET NAMES utf8mb4;
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS categories (
    id          INT UNSIGNED     AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(255)     NOT NULL,
    description TEXT,
    created_at  TIMESTAMP        DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS images (
    id          INT UNSIGNED     AUTO_INCREMENT PRIMARY KEY,
    filename    VARCHAR(255)     NOT NULL UNIQUE,
    title       VARCHAR(500)     NOT NULL,
    image_hash  CHAR(64)         NOT NULL UNIQUE,
    description TEXT,
    source      VARCHAR(2000),
    category_id INT UNSIGNED     NOT NULL,
    created_at  TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS questions (
    id             INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    image_id       INT UNSIGNED  NOT NULL,
    question       TEXT          NOT NULL,
    correct_answer VARCHAR(1000) NOT NULL,
    created_at     TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS models (
    id             INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    provider       VARCHAR(255)  NOT NULL,
    api_identifier VARCHAR(255)  NOT NULL UNIQUE,
    created_at     TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS runs (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    question_id       INT UNSIGNED NOT NULL,
    model_id          INT UNSIGNED NOT NULL,
    response          TEXT,
    score             ENUM('Correct','Incorrect','Not Found','Refusal') NOT NULL,
    prompt_version    VARCHAR(50),
    latency_ms        INT UNSIGNED,
    prompt_tokens     INT UNSIGNED,
    completion_tokens INT UNSIGNED,
    cost              DECIMAL(12,8),
    created_at        TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (question_id) REFERENCES questions(id),
    FOREIGN KEY (model_id)    REFERENCES models(id),
    INDEX idx_question_model (question_id, model_id),
    INDEX idx_model (model_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS error_logs (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    question_id   INT UNSIGNED NOT NULL,
    model_id      INT UNSIGNED NOT NULL,
    error_message TEXT,
    created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (question_id) REFERENCES questions(id),
    FOREIGN KEY (model_id)    REFERENCES models(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
