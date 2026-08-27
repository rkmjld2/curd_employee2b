-- ============================================================
-- CURD_EMPLOYEE2
-- APPLICATION USER / ACCESS CONTROL TABLE
-- ============================================================

CREATE DATABASE IF NOT EXISTS employeer;

USE employeer;

CREATE TABLE IF NOT EXISTS app_users (
    id INT AUTO_INCREMENT PRIMARY KEY,

    user_id VARCHAR(50) NOT NULL UNIQUE,

    user_name VARCHAR(100) NOT NULL,

    password_hash VARCHAR(255) NOT NULL,

    active TINYINT(1) NOT NULL DEFAULT 0,

    start_time DATETIME NULL,

    stop_time DATETIME NULL,

    last_login DATETIME NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
);