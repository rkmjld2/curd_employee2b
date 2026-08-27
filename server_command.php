<?php
/*
============================================================
 CURD-EMPLOYEE2B
 REMOTE LOCAL SERVER CONTROL
============================================================

Remote page:
    server_command.php

Local PC:
    MY-PC

Local XAMPP:
    C:\xampp

Database:
    employeer

Table:
    local_server_control

Commands:
    START
    STOP
    NONE

Status:
    ONLINE
    OFFLINE

============================================================
*/


/* =========================================================
   TIMEZONE
========================================================= */

date_default_timezone_set("Asia/Kolkata");


/* =========================================================
   SESSION
========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* =========================================================
   LICENSE
========================================================= */

require_once __DIR__ . "/license_guard.php";


/* =========================================================
   DATABASE
========================================================= */

require_once __DIR__ . "/db.php";


/* =========================================================
   SERVER
========================================================= */

$server_id = "MY-PC";


/* =========================================================
   LOGIN CHECK
========================================================= */

if (
    !isset($_SESSION["app_user_id"]) ||
    $_SESSION["app_user_id"] === ""
) {

    header("Location: login.php");

    exit;
}


/* =========================================================
   USER
========================================================= */

$current_user_id =
    $_SESSION["app_user_id"];

$current_user_name =
    $_SESSION["app_user_name"] ?? "";


/* =========================================================
   MESSAGE
========================================================= */

$message = "";

$message_type = "";


/* =========================================================
   SEND COMMAND
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["server_command"])
) {

    $requested_command =
        strtoupper(
            trim(
                $_POST["server_command"]
            )
        );


    /* -----------------------------------------------------
       Validate
    ----------------------------------------------------- */

    if (
        $requested_command !== "START" &&
        $requested_command !== "STOP"
    ) {

        $message =
            "Invalid server command.";

        $message_type =
            "error";

    } else {


        /* -------------------------------------------------
           Make sure record exists
        ------------------------------------------------- */

        $check =
            $conn->prepare(
                "
                SELECT id
                FROM local_server_control
                WHERE server_id = ?
                LIMIT 1
                "
            );


        if (!$check) {

            $message =
                "Database error: " .
                $conn->error;

            $message_type =
                "error";

        } else {

            $check->bind_param(
                "s",
                $server_id
            );

            $check->execute();

            $result =
                $check->get_result();

            $exists =
                (
                    $result &&
                    $result->num_rows > 0
                );

            $check->close();


            /* ---------------------------------------------
               Create record if missing
            --------------------------------------------- */

            if (!$exists) {

                $insert =
                    $conn->prepare(
                        "
                        INSERT INTO local_server_control
                        (
                            server_id,
                            command,
                            status,
                            last_seen,
                            command_time,
                            executed_time
                        )
                        VALUES
                        (
                            ?,
                            'NONE',
                            'OFFLINE',
                            NULL,
                            NULL,
                            NULL
                        )
                        "
                    );


                if ($insert) {

                    $insert->bind_param(
                        "s",
                        $server_id
                    );

                    $insert->execute();

                    $insert->close();
                }
            }


            /* ---------------------------------------------
               Write START / STOP
            --------------------------------------------- */

            $stmt =
                $conn->prepare(
                    "
                    UPDATE local_server_control
                    SET
                        command = ?,
                        command_time = CURRENT_TIMESTAMP
                    WHERE
                        server_id = ?
                    "
                );


            if (!$stmt) {

                $message =
                    "Command preparation failed: " .
                    $conn->error;

                $message_type =
                    "error";

            } else {

                $stmt->bind_param(
                    "ss",
                    $requested_command,
                    $server_id
                );


                if ($stmt->execute()) {

                    $message =
                        "Command " .
                        $requested_command .
                        " sent to " .
                        $server_id .
                        ".";

                    $message_type =
                        "success";

                } else {

                    $message =
                        "Command failed: " .
                        $stmt->error;

                    $message_type =
                        "error";
                }


                $stmt->close();
            }
        }
    }
}


/* =========================================================
   READ SERVER DATA
========================================================= */

$stmt =
    $conn->prepare(
        "
        SELECT
            id,
            server_id,
            command,
            status,
            last_seen,
            command_time,
            executed_time,
            created_at
        FROM local_server_control
        WHERE server_id = ?
        LIMIT 1
        "
    );


$server = null;


if ($stmt) {

    $stmt->bind_param(
        "s",
        $server_id
    );

    $stmt->execute();

    $result =
        $stmt->get_result();


    if (
        $result &&
        $result->num_rows > 0
    ) {

        $server =
            $result->fetch_assoc();
    }


    $stmt->close();
}


/* =========================================================
   DEFAULT DATA
========================================================= */

if (!$server) {

    $server = [
        "server_id"     => $server_id,
        "command"       => "NONE",
        "status"        => "OFFLINE",
        "last_seen"     => null,
        "command_time"  => null,
        "executed_time" => null,
        "created_at"    => null
    ];
}


/* =========================================================
   VALUES
========================================================= */

$command =
    strtoupper(
        $server["command"] ?? "NONE"
    );


$database_status =
    strtoupper(
        $server["status"] ?? "OFFLINE"
    );


$last_seen =
    $server["last_seen"] ?? null;


$command_time =
    $server["command_time"] ?? null;


$executed_time =
    $server["executed_time"] ?? null;


/* =========================================================
   HEARTBEAT ONLINE CHECK
========================================================= */

$is_online = false;


if (!empty($last_seen)) {

    $last_timestamp =
        strtotime($last_seen);


    if ($last_timestamp !== false) {

        $age =
            time() -
            $last_timestamp;


        if (
            $age >= 0 &&
            $age <= 15
        ) {

            /*
             * Heartbeat confirms that the
             * Windows control program is alive.
             */

            if (
                $database_status === "ONLINE"
            ) {

                $is_online = true;
            }
        }
    }
}


/* =========================================================
   DISPLAY STATUS
========================================================= */

$display_status =
    $is_online
        ? "ONLINE"
        : "OFFLINE";


/* =========================================================
   HTML ESCAPE
========================================================= */

function h($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        "UTF-8"
    );
}

?>
<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
CURD-EMPLOYEE2B - Server Control
</title>


<style>

* {
    box-sizing: border-box;
}


body {

    margin: 0;

    padding: 20px;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    background: #f2f4f7;
}


.container {

    max-width: 850px;

    margin: 40px auto;
}


.card {

    background: white;

    padding: 30px;

    border-radius: 12px;

    box-shadow:
        0 4px 15px
        rgba(0,0,0,0.15);
}


h1 {

    text-align: center;

    color: #1d3557;

    margin-top: 0;
}


.subtitle {

    text-align: center;

    color: #666;

    margin-bottom: 25px;
}


/* USER BAR */

.user-bar {

    background: #e7f1ff;

    border: 1px solid #b8d8f5;

    padding: 12px;

    border-radius: 6px;

    margin-bottom: 20px;

    display: flex;

    justify-content: space-between;

    align-items: center;

    flex-wrap: wrap;

    gap: 10px;
}


.user-info {

    color: #084298;

    font-weight: bold;
}


.logout {

    background: #6c757d;

    color: white;

    padding: 8px 14px;

    border-radius: 5px;

    text-decoration: none;
}


/* MESSAGE */

.message {

    padding: 14px;

    border-radius: 6px;

    margin-bottom: 20px;

    text-align: center;

    font-weight: bold;
}


.message.success {

    background: #d1e7dd;

    color: #0f5132;

    border: 1px solid #a3cfbb;
}


.message.error {

    background: #f8d7da;

    color: #842029;

    border: 1px solid #f1aeb5;
}


/* SERVER */

.server-title {

    text-align: center;

    font-size: 25px;

    font-weight: bold;

    color: #1d3557;

    margin-bottom: 20px;
}


.status-box {

    text-align: center;

    padding: 20px;

    border-radius: 10px;

    margin-bottom: 25px;

    font-size: 24px;

    font-weight: bold;
}


.status-online {

    background: #d1e7dd;

    color: #0f5132;

    border: 2px solid #198754;
}


.status-offline {

    background: #f8d7da;

    color: #842029;

    border: 2px solid #dc3545;
}


/* TABLE */

.info-table {

    width: 100%;

    border-collapse: collapse;

    margin-bottom: 25px;
}


.info-table th,
.info-table td {

    border: 1px solid #ddd;

    padding: 12px;

    text-align: left;
}


.info-table th {

    background: #1d3557;

    color: white;

    width: 40%;
}


.info-table td {

    background: #f8f9fa;

    font-weight: bold;
}


/* BUTTONS */

.button-area {

    display: flex;

    justify-content: center;

    gap: 20px;

    flex-wrap: wrap;

    margin-top: 20px;
}


.control-button {

    border: none;

    border-radius: 8px;

    padding: 15px 35px;

    color: white;

    font-size: 18px;

    font-weight: bold;

    cursor: pointer;

    min-width: 220px;
}


.start-button {

    background: #198754;
}


.start-button:hover {

    background: #157347;
}


.stop-button {

    background: #dc3545;
}


.stop-button:hover {

    background: #bb2d3b;
}


/* NOTE */

.note {

    background: #fff3cd;

    color: #664d03;

    border: 1px solid #ffecb5;

    padding: 15px;

    border-radius: 6px;

    line-height: 1.6;

    margin-top: 25px;
}


/* REFRESH */

.refresh-info {

    text-align: center;

    color: #777;

    font-size: 13px;

    margin-top: 15px;
}


@media (max-width: 600px) {

    .card {
        padding: 20px;
    }

    .control-button {
        width: 100%;
    }

}

</style>

</head>


<body>


<div class="container">


<!-- USER BAR -->

<div class="user-bar">

<div class="user-info">

Logged in:

<?= h($current_user_name) ?>

&nbsp;&nbsp;

(User ID:
<?= h($current_user_id) ?>
)

</div>


<a
    href="logout.php"
    class="logout"
>
Logout
</a>

</div>


<!-- MAIN CARD -->

<div class="card">


<h1>
CURD-EMPLOYEE2B
</h1>


<div class="subtitle">
Remote Local Server Control
</div>


<?php if ($message !== ""): ?>

<div
    class="message
    <?= $message_type === "success"
        ? "success"
        : "error"
    ?>"
>

<?= h($message) ?>

</div>

<?php endif; ?>


<div class="server-title">

SERVER:
<?= h($server_id) ?>

</div>


<!-- STATUS -->

<div
    class="status-box
    <?= $is_online
        ? "status-online"
        : "status-offline"
    ?>"
>

<?= $is_online
    ? "● ONLINE"
    : "● OFFLINE"
?>

</div>


<!-- SERVER DATA -->

<table class="info-table">

<tr>

<th>
Server ID
</th>

<td>
<?= h($server_id) ?>
</td>

</tr>


<tr>

<th>
Current Command
</th>

<td>
<?= h($command) ?>
</td>

</tr>


<tr>

<th>
Server Status
</th>

<td>
<?= h($display_status) ?>
</td>

</tr>


<tr>

<th>
Last Seen
</th>

<td>
<?= h(
    $last_seen ?: "Never"
) ?>
</td>

</tr>


<tr>

<th>
Command Time
</th>

<td>
<?= h(
    $command_time ?: "Never"
) ?>
</td>

</tr>


<tr>

<th>
Executed Time
</th>

<td>
<?= h(
    $executed_time ?: "Never"
) ?>
</td>

</tr>

</table>


<!-- BUTTONS -->

<div class="button-area">


<form
    method="POST"
    action="server_command.php"
    onsubmit="
        return confirm(
            'START the local XAMPP server on MY-PC?'
        );
    "
>

<input
    type="hidden"
    name="server_command"
    value="START"
>


<button
    type="submit"
    class="control-button start-button"
>

START LOCAL SERVER

</button>

</form>


<form
    method="POST"
    action="server_command.php"
    onsubmit="
        return confirm(
            'STOP the local XAMPP server on MY-PC?'
        );
    "
>

<input
    type="hidden"
    name="server_command"
    value="STOP"
>


<button
    type="submit"
    class="control-button stop-button"
>

STOP LOCAL SERVER

</button>

</form>


</div>


<!-- HOW IT WORKS -->

<div class="note">

<strong>
How it works:
</strong>

<br><br>

1. Click START or STOP.

<br>

2. The command is written to the
<code>local_server_control</code>
table in TiDB Cloud.

<br>

3. The Windows program
<code>local_server_control.py</code>
running on MY-PC checks TiDB every 4 seconds.

<br>

4. The Windows program executes the command
against XAMPP.

<br>

5. The Windows program checks the actual Apache
state and updates
<code>status</code>,
<code>last_seen</code> and
<code>executed_time</code>.

<br>

6. After successful execution, the command is
returned to <code>NONE</code>.

</div>


<div class="refresh-info">

This page automatically refreshes every 5 seconds.

</div>


</div>


</div>


<script>

setTimeout(
    function () {

        window.location.reload();

    },
    5000
);

</script>


</body>

</html>

<?php

if (isset($conn)) {
    mysqli_close($conn);
}

?>
