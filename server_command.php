<?php
/*
============================================================
 CURD-EMPLOYEE2B
 REMOTE SERVER CONTROL
============================================================

Purpose:
    Receives START / STOP commands for the local server.

Database:
    CURD_EMPLOYEE2 database

Table:
    local_server_control

Expected table fields:
    id
    command
    status
    updated_at

IMPORTANT:
    This file ONLY records the command in the remote
    database.

    It does NOT directly start or stop the local
    XAMPP server.

    The local server-control program will periodically
    read this table and execute the command locally.

============================================================
*/


/* =========================================================
   TIMEZONE
========================================================= */

date_default_timezone_set("Asia/Kolkata");


/* =========================================================
   DATABASE CONNECTION
========================================================= */

require_once __DIR__ . "/db.php";


/* =========================================================
   ALLOWED COMMANDS
========================================================= */

$allowed_commands = [
    "START",
    "STOP"
];


/* =========================================================
   REQUEST METHOD
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] !== "POST"
) {

    http_response_code(405);

    header("Content-Type: application/json");

    echo json_encode([
        "success" => false,
        "message" => "Only POST requests are allowed."
    ]);

    exit;
}


/* =========================================================
   GET COMMAND
========================================================= */

$command = strtoupper(
    trim(
        $_POST["command"] ?? ""
    )
);


/* =========================================================
   VALIDATE COMMAND
========================================================= */

if (
    !in_array(
        $command,
        $allowed_commands,
        true
    )
) {

    http_response_code(400);

    header("Content-Type: application/json");

    echo json_encode([
        "success" => false,
        "message" => "Invalid command. Use START or STOP."
    ]);

    exit;
}


/* =========================================================
   CURRENT TIME
========================================================= */

$now = date(
    "Y-m-d H:i:s"
);


/* =========================================================
   CHECK WHETHER RECORD EXISTS
========================================================= */

$check = $conn->prepare("
    SELECT id
    FROM local_server_control
    ORDER BY id ASC
    LIMIT 1
");


if (!$check) {

    http_response_code(500);

    header("Content-Type: application/json");

    echo json_encode([
        "success" => false,
        "message" => "Database preparation failed.",
        "error" => $conn->error
    ]);

    exit;
}


$check->execute();

$result =
    $check->get_result();

$existing =
    $result->fetch_assoc();

$check->close();


/* =========================================================
   UPDATE EXISTING CONTROL RECORD
========================================================= */

if ($existing) {

    $id =
        (int)$existing["id"];


    $stmt = $conn->prepare("
        UPDATE local_server_control
        SET
            command = ?,
            status = ?,
            updated_at = ?
        WHERE id = ?
    ");


    if (!$stmt) {

        http_response_code(500);

        header("Content-Type: application/json");

        echo json_encode([
            "success" => false,
            "message" => "Update preparation failed.",
            "error" => $conn->error
        ]);

        exit;
    }


    /*
     * When START is requested:
     *
     * command = START
     * status  = START
     *
     * When STOP is requested:
     *
     * command = STOP
     * status  = STOP
     */

    $stmt->bind_param(
        "sssi",
        $command,
        $command,
        $now,
        $id
    );


    if (!$stmt->execute()) {

        $error =
            $stmt->error;

        $stmt->close();

        http_response_code(500);

        header("Content-Type: application/json");

        echo json_encode([
            "success" => false,
            "message" => "Unable to update server command.",
            "error" => $error
        ]);

        exit;
    }


    $stmt->close();


/* =========================================================
   CREATE FIRST CONTROL RECORD
========================================================= */

} else {

    $stmt = $conn->prepare("
        INSERT INTO local_server_control
        (
            command,
            status,
            updated_at
        )
        VALUES
        (
            ?,
            ?,
            ?
        )
    ");


    if (!$stmt) {

        http_response_code(500);

        header("Content-Type: application/json");

        echo json_encode([
            "success" => false,
            "message" => "Insert preparation failed.",
            "error" => $conn->error
        ]);

        exit;
    }


    $stmt->bind_param(
        "sss",
        $command,
        $command,
        $now
    );


    if (!$stmt->execute()) {

        $error =
            $stmt->error;

        $stmt->close();

        http_response_code(500);

        header("Content-Type: application/json");

        echo json_encode([
            "success" => false,
            "message" => "Unable to create server command.",
            "error" => $error
        ]);

        exit;
    }


    $stmt->close();
}


/* =========================================================
   SUCCESS RESPONSE
========================================================= */

header(
    "Content-Type: application/json"
);

echo json_encode([

    "success" => true,

    "command" => $command,

    "status" => $command,

    "updated_at" => $now,

    "message" =>
        "Server command " .
        $command .
        " recorded successfully."

]);

exit;
