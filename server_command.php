<?php

/*
============================================================
 CURD-EMPLOYEE2
 REMOTE LOCAL SERVER CONTROL API
============================================================

Purpose:

    Send START / STOP commands to a local Windows computer.

Server ID:

    MY-PC

Commands:

    START
    STOP
    NONE

The local computer will poll this file periodically.

============================================================
*/

date_default_timezone_set("Asia/Kolkata");

header("Content-Type: application/json");

require_once __DIR__ . "/db.php";


/* =========================================================
   CONFIGURATION
========================================================= */

$SERVER_ID = "MY-PC";


/*
 * IMPORTANT SECURITY KEY
 *
 * Change this to your own long random value.
 */

$API_KEY =
    "CHANGE_THIS_TO_A_LONG_SECRET_KEY_2026";


/* =========================================================
   CHECK API KEY
========================================================= */

$received_key =
    $_GET["key"] ?? "";


if (
    !hash_equals(
        $API_KEY,
        $received_key
    )
) {

    http_response_code(403);

    echo json_encode(
        [
            "success" => false,
            "message" => "Unauthorized"
        ]
    );

    exit;
}


/* =========================================================
   GET REQUEST
========================================================= */

$action =
    strtoupper(
        trim(
            $_GET["action"] ?? "GET"
        )
    );


/* =========================================================
   VALID ACTIONS
========================================================= */

$allowed_actions = [

    "GET",
    "START",
    "STOP",
    "ONLINE",
    "OFFLINE"

];


if (
    !in_array(
        $action,
        $allowed_actions,
        true
    )
) {

    http_response_code(400);

    echo json_encode(
        [
            "success" => false,
            "message" => "Invalid action."
        ]
    );

    exit;
}


/* =========================================================
   CURRENT TIME
========================================================= */

$now =
    date(
        "Y-m-d H:i:s"
    );


/* =========================================================
   START COMMAND
========================================================= */

if (
    $action === "START"
) {

    $stmt =
        $conn->prepare("
            UPDATE local_server_control
            SET
                command = 'START',
                command_time = ?
            WHERE
                server_id = ?
        ");

    if (!$stmt) {

        http_response_code(500);

        echo json_encode(
            [
                "success" => false,
                "message" => "Database preparation failed."
            ]
        );

        exit;
    }


    $stmt->bind_param(
        "ss",
        $now,
        $SERVER_ID
    );


    if (!$stmt->execute()) {

        http_response_code(500);

        echo json_encode(
            [
                "success" => false,
                "message" => "START command failed."
            ]
        );

        $stmt->close();

        exit;
    }


    $stmt->close();


    echo json_encode(
        [
            "success" => true,
            "server_id" => $SERVER_ID,
            "command" => "START",
            "message" => "START command sent."
        ]
    );

    exit;
}


/* =========================================================
   STOP COMMAND
========================================================= */

if (
    $action === "STOP"
) {

    $stmt =
        $conn->prepare("
            UPDATE local_server_control
            SET
                command = 'STOP',
                command_time = ?
            WHERE
                server_id = ?
        ");

    if (!$stmt) {

        http_response_code(500);

        echo json_encode(
            [
                "success" => false,
                "message" => "Database preparation failed."
            ]
        );

        exit;
    }


    $stmt->bind_param(
        "ss",
        $now,
        $SERVER_ID
    );


    if (!$stmt->execute()) {

        http_response_code(500);

        echo json_encode(
            [
                "success" => false,
                "message" => "STOP command failed."
            ]
        );

        $stmt->close();

        exit;
    }


    $stmt->close();


    echo json_encode(
        [
            "success" => true,
            "server_id" => $SERVER_ID,
            "command" => "STOP",
            "message" => "STOP command sent."
        ]
    );

    exit;
}


/* =========================================================
   LOCAL COMPUTER ONLINE
========================================================= */

if (
    $action === "ONLINE"
) {

    $stmt =
        $conn->prepare("
            UPDATE local_server_control
            SET
                status = 'ONLINE',
                last_seen = ?
            WHERE
                server_id = ?
        ");

    if ($stmt) {

        $stmt->bind_param(
            "ss",
            $now,
            $SERVER_ID
        );

        $stmt->execute();

        $stmt->close();
    }


    echo json_encode(
        [
            "success" => true,
            "server_id" => $SERVER_ID,
            "status" => "ONLINE"
        ]
    );

    exit;
}


/* =========================================================
   LOCAL COMPUTER OFFLINE
========================================================= */

if (
    $action === "OFFLINE"
) {

    $stmt =
        $conn->prepare("
            UPDATE local_server_control
            SET
                status = 'OFFLINE',
                last_seen = ?
            WHERE
                server_id = ?
        ");

    if ($stmt) {

        $stmt->bind_param(
            "ss",
            $now,
            $SERVER_ID
        );

        $stmt->execute();

        $stmt->close();
    }


    echo json_encode(
        [
            "success" => true,
            "server_id" => $SERVER_ID,
            "status" => "OFFLINE"
        ]
    );

    exit;
}


/* =========================================================
   GET CURRENT COMMAND
========================================================= */

$stmt =
    $conn->prepare("
        SELECT
            server_id,
            command,
            status,
            last_seen,
            command_time,
            executed_time
        FROM local_server_control
        WHERE server_id = ?
        LIMIT 1
    ");


if (!$stmt) {

    http_response_code(500);

    echo json_encode(
        [
            "success" => false,
            "message" => "Database preparation failed."
        ]
    );

    exit;
}


$stmt->bind_param(
    "s",
    $SERVER_ID
);


$stmt->execute();


$result =
    $stmt->get_result();


if (
    !$result ||
    $result->num_rows === 0
) {

    $stmt->close();

    http_response_code(404);

    echo json_encode(
        [
            "success" => false,
            "message" => "Server ID not found."
        ]
    );

    exit;
}


$row =
    $result->fetch_assoc();


$stmt->close();


/* =========================================================
   RETURN INFORMATION
========================================================= */

echo json_encode(
    [
        "success" => true,
        "server_id" => $row["server_id"],
        "command" => $row["command"],
        "status" => $row["status"],
        "last_seen" => $row["last_seen"],
        "command_time" => $row["command_time"],
        "executed_time" => $row["executed_time"]
    ]
);

exit;