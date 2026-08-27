<?php
/*
============================================================
 CURD-EMPLOYEE2B
 REMOTE LOCAL-SERVER CONTROL
============================================================

Purpose:
    Send START / STOP command to the local server.

Database:
    CURD_EMPLOYEE2

Table:
    local_server_control

Actual table fields:
    id
    server_id
    command
    status
    last_seen
    command_time
    executed_time
    created_at

IMPORTANT:
    This file records the command in TiDB Cloud.

    It does NOT directly start or stop XAMPP.

    A local control program will later read the
    command and start/stop the local server.

============================================================
*/


/* =========================================================
   TIMEZONE
========================================================= */

date_default_timezone_set("Asia/Kolkata");


/* =========================================================
   DATABASE
========================================================= */

require_once __DIR__ . "/db.php";


/* =========================================================
   SERVER ID
=========================================================

   IMPORTANT:
   Change this value if you want to control a different
   local server.

========================================================= */

$server_id = "LOCAL_SERVER_1";


/* =========================================================
   ALLOWED COMMANDS
========================================================= */

$allowed_commands = [
    "START",
    "STOP"
];


/* =========================================================
   ONLY POST REQUESTS
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] !== "POST"
) {

    http_response_code(405);

    header(
        "Content-Type: application/json"
    );

    echo json_encode([

        "success" => false,

        "message" =>
            "Only POST requests are allowed."

    ]);

    exit;
}


/* =========================================================
   GET COMMAND
========================================================= */

$command =
    strtoupper(
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

    header(
        "Content-Type: application/json"
    );

    echo json_encode([

        "success" => false,

        "message" =>
            "Invalid command. Use START or STOP."

    ]);

    exit;
}


/* =========================================================
   CURRENT IST TIME
========================================================= */

$command_time =
    date(
        "Y-m-d H:i:s"
    );


/* =========================================================
   CHECK SERVER RECORD
========================================================= */

$check =
    $conn->prepare("
        SELECT
            id
        FROM local_server_control
        WHERE server_id = ?
        LIMIT 1
    ");


if (!$check) {

    http_response_code(500);

    header(
        "Content-Type: application/json"
    );

    echo json_encode([

        "success" => false,

        "message" =>
            "Database preparation failed.",

        "error" =>
            $conn->error

    ]);

    exit;
}


$check->bind_param(
    "s",
    $server_id
);


if (!$check->execute()) {

    $error =
        $check->error;

    $check->close();

    http_response_code(500);

    header(
        "Content-Type: application/json"
    );

    echo json_encode([

        "success" => false,

        "message" =>
            "Unable to check server.",

        "error" =>
            $error

    ]);

    exit;
}


$result =
    $check->get_result();


$existing =
    $result->fetch_assoc();


$check->close();


/* =========================================================
   UPDATE EXISTING SERVER
========================================================= */

if ($existing) {

    $server_db_id =
        (int)$existing["id"];


    $stmt =
        $conn->prepare("
            UPDATE local_server_control
            SET
                command = ?,
                command_time = ?
            WHERE
                id = ?
                AND server_id = ?
        ");


    if (!$stmt) {

        http_response_code(500);

        header(
            "Content-Type: application/json"
        );

        echo json_encode([

            "success" => false,

            "message" =>
                "Update preparation failed.",

            "error" =>
                $conn->error

        ]);

        exit;
    }


    $stmt->bind_param(
        "ssis",
        $command,
        $command_time,
        $server_db_id,
        $server_id
    );


    if (!$stmt->execute()) {

        $error =
            $stmt->error;

        $stmt->close();

        http_response_code(500);

        header(
            "Content-Type: application/json"
        );

        echo json_encode([

            "success" => false,

            "message" =>
                "Unable to update server command.",

            "error" =>
                $error

        ]);

        exit;
    }


    $stmt->close();


/* =========================================================
   CREATE SERVER RECORD
========================================================= */

} else {

    $stmt =
        $conn->prepare("
            INSERT INTO local_server_control
            (
                server_id,
                command,
                status,
                command_time
            )
            VALUES
            (
                ?,
                ?,
                'OFFLINE',
                ?
            )
        ");


    if (!$stmt) {

        http_response_code(500);

        header(
            "Content-Type: application/json"
        );

        echo json_encode([

            "success" => false,

            "message" =>
                "Insert preparation failed.",

            "error" =>
                $conn->error

        ]);

        exit;
    }


    $stmt->bind_param(
        "sss",
        $server_id,
        $command,
        $command_time
    );


    if (!$stmt->execute()) {

        $error =
            $stmt->error;

        $stmt->close();

        http_response_code(500);

        header(
            "Content-Type: application/json"
        );

        echo json_encode([

            "success" => false,

            "message" =>
                "Unable to create server command.",

            "error" =>
                $error

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

    "server_id" =>
        $server_id,

    "command" =>
        $command,

    "command_time" =>
        $command_time,

    "message" =>
        "Command " .
        $command .
        " recorded successfully."

]);


exit;
