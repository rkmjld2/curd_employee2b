<?php
/*
============================================================
 CURD-EMPLOYEE2B
 REMOTE LOCAL SERVER CONTROL
============================================================

Purpose:
    Send START / STOP commands to a Windows PC.

Database:
    employeer

Table:
    local_server_control

Server:
    MY-PC

IMPORTANT:
    This file controls the local Windows program through
    the TiDB Cloud database.

Flow:

    Remote server_command.php
            |
            v
    TiDB Cloud
            |
            v
    local_server_control
            |
            v
    Windows local_server_control.py
            |
            v
    XAMPP

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
========================================================= */

$server_id = "MY-PC";


/* =========================================================
   DEFAULT RESPONSE
========================================================= */

$message = "";

$message_type = "error";


/* =========================================================
   GET COMMAND
========================================================= */

$command = strtoupper(
    trim(
        $_POST["command"] ?? ""
    )
);


/* =========================================================
   VALID COMMAND CHECK
========================================================= */

if (
    !in_array(
        $command,
        ["START", "STOP"],
        true
    )
) {

    $message =
        "Invalid command. Only START or STOP is allowed.";

} else {


    /* =====================================================
       CHECK SERVER RECORD
    ===================================================== */

    $stmt = $conn->prepare("
        SELECT
            server_id,
            command,
            status
        FROM local_server_control
        WHERE server_id = ?
        LIMIT 1
    ");


    if (!$stmt) {

        $message =
            "Database preparation failed.";

    } else {


        $stmt->bind_param(
            "s",
            $server_id
        );


        if (!$stmt->execute()) {

            $message =
                "Database query failed: " .
                $stmt->error;

            $stmt->close();

        } else {


            $result =
                $stmt->get_result();


            /* =================================================
               SERVER NOT FOUND
            ================================================= */

            if (
                !$result ||
                $result->num_rows === 0
            ) {

                $message =
                    "Server MY-PC was not found in local_server_control.";

                $stmt->close();

            } else {

                $server =
                    $result->fetch_assoc();

                $stmt->close();


                /* =============================================
                   CURRENT STATUS
                ============================================= */

                $current_status =
                    strtoupper(
                        trim(
                            $server["status"] ?? "OFFLINE"
                        )
                    );


                /* =============================================
                   PREVENT DUPLICATE COMMANDS
                ============================================= */

                if (
                    $command === "START" &&
                    $current_status === "ONLINE"
                ) {

                    $message =
                        "MY-PC is already ONLINE.";

                    $message_type =
                        "success";

                } elseif (
                    $command === "STOP" &&
                    $current_status === "OFFLINE"
                ) {

                    $message =
                        "MY-PC is already OFFLINE.";

                    $message_type =
                        "success";

                } else {


                    /* =========================================
                       SEND COMMAND
                    ========================================= */

                    $update =
                        $conn->prepare("
                            UPDATE local_server_control
                            SET
                                command = ?,
                                command_time = NOW()
                            WHERE server_id = ?
                        ");


                    if (!$update) {

                        $message =
                            "Command preparation failed.";

                    } else {


                        $update->bind_param(
                            "ss",
                            $command,
                            $server_id
                        );


                        if (
                            $update->execute()
                        ) {

                            if (
                                $update->affected_rows >= 0
                            ) {

                                $message =
                                    "Command " .
                                    $command .
                                    " sent successfully to MY-PC.";

                                $message_type =
                                    "success";

                            } else {

                                $message =
                                    "Command could not be sent.";

                            }

                        } else {

                            $message =
                                "Command failed: " .
                                $update->error;
                        }


                        $update->close();
                    }
                }
            }
        }
    }
}


/* =========================================================
   READ CURRENT SERVER INFORMATION
========================================================= */

$server_info = null;


$stmt =
    $conn->prepare("
        SELECT
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
    ");


if ($stmt) {

    $stmt->bind_param(
        "s",
        $server_id
    );

    if ($stmt->execute()) {

        $result =
            $stmt->get_result();

        if (
            $result &&
            $result->num_rows > 0
        ) {

            $server_info =
                $result->fetch_assoc();
        }
    }

    $stmt->close();
}


/* =========================================================
   CLOSE DATABASE
========================================================= */

mysqli_close($conn);

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
Remote Local Server Control
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

    width: 100%;

    max-width: 700px;

    margin: 60px auto;
}


.card {

    background: white;

    padding: 30px;

    border-radius: 12px;

    box-shadow:
        0 4px 15px
        rgba(0,0,0,0.15);

    text-align: center;
}


h1 {

    color: #1d3557;

    margin-top: 0;

    margin-bottom: 10px;
}


.subtitle {

    color: #666;

    margin-bottom: 25px;
}


.message {

    padding: 14px;

    border-radius: 6px;

    margin-bottom: 25px;

    font-weight: bold;

    line-height: 1.5;
}


.success {

    background: #d1e7dd;

    color: #0f5132;
}


.error {

    background: #f8d7da;

    color: #842029;
}


.server-box {

    background: #f8f9fa;

    border: 1px solid #ddd;

    border-radius: 8px;

    padding: 20px;

    margin-bottom: 25px;

    text-align: left;
}


.server-box table {

    width: 100%;

    border-collapse: collapse;
}


.server-box td {

    padding: 8px;

    border-bottom: 1px solid #ddd;
}


.server-box td:first-child {

    font-weight: bold;

    width: 40%;
}


.online {

    color: green;

    font-weight: bold;
}


.offline {

    color: red;

    font-weight: bold;
}


.command {

    color: #6f42c1;

    font-weight: bold;
}


.buttons {

    display: flex;

    justify-content: center;

    gap: 15px;

    flex-wrap: wrap;

    margin-top: 20px;
}


button {

    border: none;

    border-radius: 7px;

    padding: 13px 30px;

    color: white;

    font-size: 16px;

    font-weight: bold;

    cursor: pointer;

    min-width: 150px;
}


.start {

    background: #198754;
}


.stop {

    background: #dc3545;
}


button:hover {

    opacity: 0.85;
}


.back {

    display: inline-block;

    margin-top: 25px;

    padding: 10px 20px;

    background: #6c757d;

    color: white;

    text-decoration: none;

    border-radius: 6px;
}


.back:hover {

    opacity: 0.85;
}


.note {

    margin-top: 25px;

    background: #fff3cd;

    color: #664d03;

    padding: 15px;

    border-radius: 6px;

    text-align: left;

    line-height: 1.6;
}


</style>

</head>


<body>


<div class="container">


<div class="card">


<h1>
Remote Local Server Control
</h1>


<div class="subtitle">

Control Windows XAMPP server remotely

</div>


<?php

if (
    $message !== ""
) {

?>

<div
    class="message
    <?php
        echo $message_type === "success"
            ? "success"
            : "error";
    ?>"
>

<?php

echo htmlspecialchars(
    $message,
    ENT_QUOTES,
    "UTF-8"
);

?>

</div>

<?php

}

?>


<!-- =====================================================
     SERVER INFORMATION
===================================================== -->

<div class="server-box">

<table>


<tr>

<td>
Server ID
</td>

<td>

<?php

echo htmlspecialchars(
    $server_info["server_id"] ?? $server_id,
    ENT_QUOTES,
    "UTF-8"
);

?>

</td>

</tr>


<tr>

<td>
Command
</td>

<td class="command">

<?php

echo htmlspecialchars(
    $server_info["command"] ?? "NONE",
    ENT_QUOTES,
    "UTF-8"
);

?>

</td>

</tr>


<tr>

<td>
Status
</td>

<td>

<?php

$status =
    strtoupper(
        $server_info["status"] ?? "OFFLINE"
    );

if (
    $status === "ONLINE"
) {

?>

<span class="online">
ONLINE
</span>

<?php

} else {

?>

<span class="offline">
OFFLINE
</span>

<?php

}

?>

</td>

</tr>


<tr>

<td>
Last Seen
</td>

<td>

<?php

echo htmlspecialchars(
    $server_info["last_seen"] ?? "-",
    ENT_QUOTES,
    "UTF-8"
);

?>

</td>

</tr>


<tr>

<td>
Command Time
</td>

<td>

<?php

echo htmlspecialchars(
    $server_info["command_time"] ?? "-",
    ENT_QUOTES,
    "UTF-8"
);

?>

</td>

</tr>


<tr>

<td>
Executed Time
</td>

<td>

<?php

echo htmlspecialchars(
    $server_info["executed_time"] ?? "-",
    ENT_QUOTES,
    "UTF-8"
);

?>

</td>

</tr>


</table>

</div>


<!-- =====================================================
     START / STOP
===================================================== -->

<form
    method="POST"
    action="server_command.php"
>


<div class="buttons">


<button
    type="submit"
    name="command"
    value="START"
    class="start"
    onclick="
        return confirm(
            'Start XAMPP on MY-PC?'
        );
    "
>

START SERVER

</button>


<button
    type="submit"
    name="command"
    value="STOP"
    class="stop"
    onclick="
        return confirm(
            'Stop XAMPP on MY-PC?'
        );
    "
>

STOP SERVER

</button>


</div>

</form>


<div class="note">

<strong>
How it works:
</strong>

<br>

START sends the <strong>START</strong> command to
TiDB Cloud.

<br>

The Windows program running on MY-PC reads the command
and starts XAMPP.

<br>

STOP sends the <strong>STOP</strong> command and the
Windows program stops XAMPP.

<br><br>

Keep
<strong>
local_server_control.py
</strong>
running on MY-PC while using remote control.

</div>


<a
    href="index.php"
    class="back"
>
Back to Main Application
</a>


</div>

</div>


</body>

</html>
