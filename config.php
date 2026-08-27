<?php
/*
 * ============================================================
 * CURD_EMPLOYEE2 - config.php
 * ============================================================
 *
 * Central configuration file
 *
 * Database:
 *   TiDB Cloud
 *
 * Database name:
 *   employeer
 *
 * Tables:
 *   employee
 *   app_users
 *
 * Timezone:
 *   Asia/Kolkata
 *
 * Commercial License:
 *   USER001
 *
 * IMPORTANT:
 * Database credentials are read from environment variables.
 * Do NOT put the database password directly in this file.
 * ============================================================
 */


/* ============================================================
   TIMEZONE
============================================================ */

date_default_timezone_set("Asia/Kolkata");


/* ============================================================
   DATABASE ENVIRONMENT VARIABLES
============================================================ */

$DB_HOST     = getenv("DB_HOST");
$DB_USER     = getenv("DB_USER");
$DB_PASSWORD = getenv("DB_PASSWORD");
$DB_NAME     = getenv("DB_NAME");
$DB_PORT     = getenv("DB_PORT");


/* ============================================================
   CHECK DATABASE SETTINGS
============================================================ */

if (
    $DB_HOST === false ||
    $DB_HOST === "" ||
    $DB_USER === false ||
    $DB_USER === "" ||
    $DB_PASSWORD === false ||
    $DB_PASSWORD === "" ||
    $DB_NAME === false ||
    $DB_NAME === ""
) {

    die("Database environment variables are not configured.");

}


/* ============================================================
   DEFAULT DATABASE PORT
============================================================ */

if (
    $DB_PORT === false ||
    $DB_PORT === ""
) {

    $DB_PORT = 4000;

}


/* ============================================================
   INITIALIZE MYSQLI
============================================================ */

$conn = mysqli_init();


if (!$conn) {

    die("Database initialization failed.");

}


/* ============================================================
   SSL FOR TIDB CLOUD
============================================================ */

mysqli_ssl_set(
    $conn,
    NULL,
    NULL,
    NULL,
    NULL,
    NULL
);


/* ============================================================
   CONNECT TO TIDB CLOUD USING SSL
============================================================ */

if (
    !mysqli_real_connect(
        $conn,
        $DB_HOST,
        $DB_USER,
        $DB_PASSWORD,
        $DB_NAME,
        (int)$DB_PORT,
        NULL,
        MYSQLI_CLIENT_SSL
    )
) {

    die(
        "Database connection failed: "
        . mysqli_connect_error()
    );

}


/* ============================================================
   CHARACTER SET
============================================================ */

if (
    !mysqli_set_charset(
        $conn,
        "utf8mb4"
    )
) {

    die(
        "Failed to set database character set: "
        . mysqli_error($conn)
    );

}


/* ============================================================
   APPLICATION SETTINGS
============================================================ */

$APP_NAME =
    "CURD Employee 2";

$APP_TIMEZONE =
    "Asia/Kolkata";


/* ============================================================
   COMMERCIAL LICENSE SETTINGS
============================================================ */

/*
 * This USER_ID belongs to CURD_EMPLOYEE2.
 *
 * It must exist in the remote commercial
 * license database.
 */

$LICENSE_USER_ID =
    "USER001";


/* ============================================================
   REMOTE LICENSE SERVER
============================================================ */

$LICENSE_SERVER_URL =
    "https://license-commercial2-remote.onrender.com/license_check.php";


/* ============================================================
   SESSION SETTINGS
============================================================ */

/*
 * Start session only if it has not already
 * been started.
 */

if (
    session_status() === PHP_SESSION_NONE
) {

    session_start();

}

?>
