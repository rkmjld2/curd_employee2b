<?php

/*
=========================================================
TiDB CLOUD CONNECTION
=========================================================
*/

$DB_HOST = getenv("DB_HOST");
$DB_USER = getenv("DB_USER");
$DB_PASSWORD = getenv("DB_PASSWORD");
$DB_NAME = getenv("DB_NAME");
$DB_PORT = getenv("DB_PORT");


/*
=========================================================
CONNECT TO TIDB CLOUD
WITHOUT SELECTING DATABASE
=========================================================
*/

$conn = mysqli_init();

mysqli_ssl_set(
    $conn,
    NULL,
    NULL,
    NULL,
    NULL,
    NULL
);


if (!mysqli_real_connect(
    $conn,
    $DB_HOST,
    $DB_USER,
    $DB_PASSWORD,
    "",
    intval($DB_PORT),
    NULL,
    MYSQLI_CLIENT_SSL
)) {

    die(
        "TiDB Cloud connection failed: "
        . mysqli_connect_error()
    );
}


/*
=========================================================
CREATE DATABASE
=========================================================
*/

$sql = "CREATE DATABASE IF NOT EXISTS `$DB_NAME`";


if (!mysqli_query($conn, $sql)) {

    die(
        "Database creation failed: "
        . mysqli_error($conn)
    );
}


/*
=========================================================
SELECT DATABASE
=========================================================
*/

if (!mysqli_select_db($conn, $DB_NAME)) {

    die(
        "Cannot select database: "
        . mysqli_error($conn)
    );
}


/*
=========================================================
CREATE EMPLOYEE TABLE
=========================================================
*/

$sql = "

CREATE TABLE IF NOT EXISTS employee (

    Employee_name VARCHAR(100) NOT NULL DEFAULT '',

    id INT AUTO_INCREMENT PRIMARY KEY,

    BASIC_PAY DECIMAL(12,2) NOT NULL DEFAULT 0,

    DA_PERCENT DECIMAL(8,2) NOT NULL DEFAULT 0,

    DA_AMOUNT DECIMAL(12,2) NOT NULL DEFAULT 0,

    HRA_PERCENT DECIMAL(8,2) NOT NULL DEFAULT 0,

    HRA_AMOUNT DECIMAL(12,2) NOT NULL DEFAULT 0,

    PF_DEDUCTION DECIMAL(12,2) NOT NULL DEFAULT 0,

    ANY_OTHER_ALLOWANCE DECIMAL(12,2) NOT NULL DEFAULT 0,

    TOTAL_PAYMENT DECIMAL(12,2) NOT NULL DEFAULT 0

)

";


if (!mysqli_query($conn, $sql)) {

    die(
        "Table creation failed: "
        . mysqli_error($conn)
    );
}


/*
=========================================================
IF OLD TABLE EXISTS WITHOUT Employee_name,
ADD THE COLUMN
=========================================================
*/

$check_sql = "
SHOW COLUMNS FROM employee
LIKE 'Employee_name'
";

$check_result = mysqli_query(
    $conn,
    $check_sql
);


if (
    $check_result
    &&
    mysqli_num_rows($check_result) == 0
) {

    $alter_sql = "

    ALTER TABLE employee

    ADD COLUMN Employee_name
    VARCHAR(100)
    NOT NULL
    DEFAULT ''
    FIRST

    ";

    mysqli_query($conn, $alter_sql);
}


/*
=========================================================
CHARACTER SET
=========================================================
*/

mysqli_set_charset(
    $conn,
    "utf8mb4"
);