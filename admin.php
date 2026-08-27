<?php
/*
============================================================
 CURD-EMPLOYEE2
 ADMIN USER MANAGEMENT
============================================================

Database:
    employee

User table:
    app_users

Purpose:
    Administrator can:

        1. View users
        2. Activate users
        3. Deactivate users
        4. Set Start Time
        5. Set Stop Time
        6. Clear Start Time
        7. Clear Stop Time

IMPORTANT:
    This file ONLY modifies app_users.

    It DOES NOT modify:
        employee

Timezone:
    Asia/Kolkata
============================================================
*/

date_default_timezone_set("Asia/Kolkata");

session_start();

require_once __DIR__ . "/db.php";


/* =========================================================
   ADMIN LOGIN PROTECTION
========================================================= */

if (
    !isset($_SESSION["admin_logged_in"]) ||
    $_SESSION["admin_logged_in"] !== true
) {

    header("Location: admin_login.php");

    exit;
}


/* =========================================================
   MESSAGE
========================================================= */

$message = "";

$message_type = "";


/* =========================================================
   ADMIN LOGOUT
========================================================= */

if (isset($_GET["logout"])) {

    $_SESSION = [];

    session_destroy();

    header("Location: admin_login.php");

    exit;
}


/* =========================================================
   ACTIVATE USER
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["activate_user"])
) {

    $id =
        intval($_POST["id"] ?? 0);


    if ($id <= 0) {

        $message =
            "Invalid user ID.";

        $message_type =
            "error";

    } else {

        /*
         * IMPORTANT:
         *
         * Only app_users is changed.
         */

        $stmt = $conn->prepare("
            UPDATE app_users
            SET active = 1
            WHERE id = ?
        ");


        if ($stmt) {

            $stmt->bind_param(
                "i",
                $id
            );


            if ($stmt->execute()) {

                $message =
                    "User activated successfully.";

                $message_type =
                    "success";

            } else {

                $message =
                    "Could not activate user.";

                $message_type =
                    "error";
            }


            $stmt->close();

        } else {

            $message =
                "Activation preparation failed.";

            $message_type =
                "error";
        }
    }
}


/* =========================================================
   DEACTIVATE USER
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["deactivate_user"])
) {

    $id =
        intval($_POST["id"] ?? 0);


    if ($id <= 0) {

        $message =
            "Invalid user ID.";

        $message_type =
            "error";

    } else {

        /*
         * IMPORTANT:
         *
         * Only app_users is changed.
         *
         * Employee records remain untouched.
         */

        $stmt = $conn->prepare("
            UPDATE app_users
            SET active = 0
            WHERE id = ?
        ");


        if ($stmt) {

            $stmt->bind_param(
                "i",
                $id
            );


            if ($stmt->execute()) {

                $message =
                    "User deactivated successfully.";

                $message_type =
                    "success";

            } else {

                $message =
                    "Could not deactivate user.";

                $message_type =
                    "error";
            }


            $stmt->close();

        } else {

            $message =
                "Deactivation preparation failed.";

            $message_type =
                "error";
        }
    }
}


/* =========================================================
   SAVE START TIME
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["save_start"])
) {

    $id =
        intval($_POST["id"] ?? 0);

    $start_time =
        trim($_POST["start_time"] ?? "");


    if ($id <= 0) {

        $message =
            "Invalid user ID.";

        $message_type =
            "error";

    } elseif ($start_time === "") {

        $message =
            "Start time is required.";

        $message_type =
            "error";

    } else {

        /*
         * Convert HTML datetime-local:
         *
         * 2026-08-21T18:30
         *
         * to:
         *
         * 2026-08-21 18:30:00
         */

        $start_datetime =
            str_replace(
                "T",
                " ",
                $start_time
            );


        if (
            strlen($start_datetime) === 16
        ) {

            $start_datetime .= ":00";
        }


        $stmt = $conn->prepare("
            UPDATE app_users
            SET start_time = ?
            WHERE id = ?
        ");


        if ($stmt) {

            $stmt->bind_param(
                "si",
                $start_datetime,
                $id
            );


            if ($stmt->execute()) {

                $message =
                    "Start time saved successfully.";

                $message_type =
                    "success";

            } else {

                $message =
                    "Could not save start time.";

                $message_type =
                    "error";
            }


            $stmt->close();

        } else {

            $message =
                "Start time preparation failed.";

            $message_type =
                "error";
        }
    }
}


/* =========================================================
   CLEAR START TIME
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["clear_start"])
) {

    $id =
        intval($_POST["id"] ?? 0);


    if ($id > 0) {

        $stmt = $conn->prepare("
            UPDATE app_users
            SET start_time = NULL
            WHERE id = ?
        ");


        if ($stmt) {

            $stmt->bind_param(
                "i",
                $id
            );


            if ($stmt->execute()) {

                $message =
                    "Start time cleared.";

                $message_type =
                    "success";

            } else {

                $message =
                    "Could not clear start time.";

                $message_type =
                    "error";
            }


            $stmt->close();

        } else {

            $message =
                "Clear start preparation failed.";

            $message_type =
                "error";
        }
    }
}


/* =========================================================
   SAVE STOP TIME
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["save_stop"])
) {

    $id =
        intval($_POST["id"] ?? 0);

    $stop_time =
        trim($_POST["stop_time"] ?? "");


    if ($id <= 0) {

        $message =
            "Invalid user ID.";

        $message_type =
            "error";

    } elseif ($stop_time === "") {

        $message =
            "Stop time is required.";

        $message_type =
            "error";

    } else {

        /*
         * Convert HTML datetime-local
         */

        $stop_datetime =
            str_replace(
                "T",
                " ",
                $stop_time
            );


        if (
            strlen($stop_datetime) === 16
        ) {

            $stop_datetime .= ":00";
        }


        $stmt = $conn->prepare("
            UPDATE app_users
            SET stop_time = ?
            WHERE id = ?
        ");


        if ($stmt) {

            $stmt->bind_param(
                "si",
                $stop_datetime,
                $id
            );


            if ($stmt->execute()) {

                $message =
                    "Stop time saved successfully.";

                $message_type =
                    "success";

            } else {

                $message =
                    "Could not save stop time.";

                $message_type =
                    "error";
            }


            $stmt->close();

        } else {

            $message =
                "Stop time preparation failed.";

            $message_type =
                "error";
        }
    }
}


/* =========================================================
   CLEAR STOP TIME
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["clear_stop"])
) {

    $id =
        intval($_POST["id"] ?? 0);


    if ($id > 0) {

        $stmt = $conn->prepare("
            UPDATE app_users
            SET stop_time = NULL
            WHERE id = ?
        ");


        if ($stmt) {

            $stmt->bind_param(
                "i",
                $id
            );


            if ($stmt->execute()) {

                $message =
                    "Stop time cleared.";

                $message_type =
                    "success";

            } else {

                $message =
                    "Could not clear stop time.";

                $message_type =
                    "error";
            }


            $stmt->close();

        } else {

            $message =
                "Clear stop preparation failed.";

            $message_type =
                "error";
        }
    }
}


/* =========================================================
   READ ALL USERS
========================================================= */

$users = [];


$result = $conn->query("
    SELECT
        id,
        user_id,
        user_name,
        active,
        start_time,
        stop_time,
        last_login,
        created_at,
        updated_at
    FROM app_users
    ORDER BY id ASC
");


if ($result) {

    while (
        $row =
        $result->fetch_assoc()
    ) {

        $users[] = $row;
    }
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
CURD-EMPLOYEE2 - Admin
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

    color: #222;
}


.container {

    width: 98%;

    max-width: 1500px;

    margin: auto;

    background: white;

    padding: 25px;

    border-radius: 12px;

    box-shadow:
        0 3px 15px
        rgba(0,0,0,0.12);
}


/* =========================================================
   HEADER
========================================================= */

.header {

    position: relative;

    text-align: center;

    margin-bottom: 25px;
}

.header h1 {

    margin: 0 0 5px 0;

    color: #1d3557;
}

.subtitle {

    color: #666;

    font-size: 15px;
}


.logout {

    position: absolute;

    right: 0;

    top: 0;

    text-decoration: none;

    background: #6c757d;

    color: white;

    padding: 9px 15px;

    border-radius: 5px;

    font-size: 14px;
}

.logout:hover {

    opacity: 0.85;
}


/* =========================================================
   MESSAGE
========================================================= */

.message {

    padding: 12px;

    margin-bottom: 20px;

    border-radius: 6px;

    font-weight: bold;

    text-align: center;
}

.success {

    background: #d1e7dd;

    color: #0f5132;
}

.error {

    background: #f8d7da;

    color: #842029;
}


/* =========================================================
   INFORMATION
========================================================= */

.info-box {

    background: #e7f1ff;

    border: 1px solid #b8d8f5;

    padding: 15px;

    border-radius: 8px;

    margin-bottom: 20px;

    color: #084298;

    line-height: 1.7;
}


/* =========================================================
   TABLE
========================================================= */

.table-container {

    overflow-x: auto;
}

table {

    width: 100%;

    border-collapse: collapse;

    min-width: 1200px;
}

th,
td {

    border: 1px solid #ddd;

    padding: 10px;

    text-align: center;

    vertical-align: middle;
}

th {

    background: #1d3557;

    color: white;

    white-space: nowrap;
}

tr:nth-child(even) {

    background: #f8f9fa;
}


/* =========================================================
   USER STATUS
========================================================= */

.active {

    color: #198754;

    font-weight: bold;
}

.inactive {

    color: #dc3545;

    font-weight: bold;
}


/* =========================================================
   BUTTONS
========================================================= */

button {

    border: none;

    border-radius: 5px;

    padding: 8px 12px;

    cursor: pointer;

    font-size: 13px;

    color: white;
}

button:hover {

    opacity: 0.85;
}

.activate {

    background: #198754;
}

.deactivate {

    background: #dc3545;
}

.save {

    background: #0d6efd;
}

.clear {

    background: #6c757d;
}


/* =========================================================
   FORMS
========================================================= */

.action-form {

    margin: 3px 0;
}

.datetime-form {

    display: flex;

    flex-direction: column;

    gap: 5px;

    align-items: center;
}

.datetime-form input {

    padding: 7px;

    border: 1px solid #aaa;

    border-radius: 5px;

    font-size: 13px;

    width: 210px;
}

.button-row {

    display: flex;

    gap: 5px;

    justify-content: center;

    flex-wrap: wrap;
}


/* =========================================================
   NO USERS
========================================================= */

.no-users {

    padding: 30px;

    text-align: center;

    color: #666;

    font-size: 16px;
}


/* =========================================================
   MOBILE
========================================================= */

@media (max-width: 700px) {

    body {

        padding: 10px;
    }

    .container {

        padding: 15px;
    }

    .logout {

        position: static;

        display: inline-block;

        margin-top: 10px;
    }

    .header {

        text-align: center;
    }
}

</style>

</head>


<body>


<div class="container">


<!-- ======================================================
     HEADER
====================================================== -->

<div class="header">

<h1>
CURD-EMPLOYEE2
</h1>

<div class="subtitle">
Administrator - User Management
</div>


<a
    href="admin.php?logout=1"
    class="logout"
>
Logout
</a>

</div>


<!-- ======================================================
     INFORMATION
====================================================== -->

<div class="info-box">

<strong>
Administrator User Management
</strong>

<br>

From this page you can activate or deactivate users
and control their Start Time and Stop Time.

<br><br>

<strong>
Important:
</strong>

Changing a user's status or time settings here
does <strong>not</strong> change or delete any
employee records.

</div>


<?php

if ($message !== "") {

?>

<div
    class="message
    <?php

    echo
        $message_type === "success"
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


<!-- ======================================================
     USER TABLE
====================================================== -->

<div class="table-container">

<table>

<thead>

<tr>

<th>ID</th>

<th>User ID</th>

<th>User Name</th>

<th>Status</th>

<th>Start Time</th>

<th>Stop Time</th>

<th>Last Login</th>

<th>Created At</th>

<th>Updated At</th>

<th>Activate / Deactivate</th>

<th>Start Time Control</th>

<th>Stop Time Control</th>

</tr>

</thead>


<tbody>

<?php

if (count($users) > 0) {

    foreach ($users as $user) {

?>

<tr>


<!-- ID -->

<td>

<?php

echo intval(
    $user["id"]
);

?>

</td>


<!-- USER ID -->

<td>

<strong>

<?php

echo htmlspecialchars(
    $user["user_id"],
    ENT_QUOTES,
    "UTF-8"
);

?>

</strong>

</td>


<!-- USER NAME -->

<td>

<?php

echo htmlspecialchars(
    $user["user_name"],
    ENT_QUOTES,
    "UTF-8"
);

?>

</td>


<!-- STATUS -->

<td>

<?php

if (
    (int)$user["active"] === 1
) {

?>

<span class="active">
ACTIVE
</span>

<?php

} else {

?>

<span class="inactive">
INACTIVE
</span>

<?php

}

?>

</td>


<!-- START TIME -->

<td>

<?php

if (
    !empty($user["start_time"])
) {

    echo htmlspecialchars(
        $user["start_time"],
        ENT_QUOTES,
        "UTF-8"
    );

} else {

    echo "Not set";
}

?>

</td>


<!-- STOP TIME -->

<td>

<?php

if (
    !empty($user["stop_time"])
) {

    echo htmlspecialchars(
        $user["stop_time"],
        ENT_QUOTES,
        "UTF-8"
    );

} else {

    echo "Not set";
}

?>

</td>


<!-- LAST LOGIN -->

<td>

<?php

if (
    !empty($user["last_login"])
) {

    echo htmlspecialchars(
        $user["last_login"],
        ENT_QUOTES,
        "UTF-8"
    );

} else {

    echo "Never";
}

?>

</td>


<!-- CREATED -->

<td>

<?php

echo htmlspecialchars(
    $user["created_at"],
    ENT_QUOTES,
    "UTF-8"
);

?>

</td>


<!-- UPDATED -->

<td>

<?php

echo htmlspecialchars(
    $user["updated_at"],
    ENT_QUOTES,
    "UTF-8"
);

?>

</td>


<!-- ==================================================
     ACTIVATE / DEACTIVATE
================================================== -->

<td>


<?php

if (
    (int)$user["active"] === 1
) {

?>

<form
    method="POST"
    action="admin.php"
    class="action-form"
>

<input
    type="hidden"
    name="id"
    value="<?php
        echo intval(
            $user["id"]
        );
    ?>"
>

<button
    type="submit"
    name="deactivate_user"
    class="deactivate"
    onclick="
        return confirm(
            'Deactivate this user?'
        );
    "
>
Deactivate
</button>

</form>

<?php

} else {

?>

<form
    method="POST"
    action="admin.php"
    class="action-form"
>

<input
    type="hidden"
    name="id"
    value="<?php
        echo intval(
            $user["id"]
        );
    ?>"
>

<button
    type="submit"
    name="activate_user"
    class="activate"
>
Activate
</button>

</form>

<?php

}

?>

</td>


<!-- ==================================================
     START TIME CONTROL
================================================== -->

<td>

<form
    method="POST"
    action="admin.php"
    class="datetime-form"
>

<input
    type="hidden"
    name="id"
    value="<?php
        echo intval(
            $user["id"]
        );
    ?>"
>


<input
    type="datetime-local"
    name="start_time"
    value="<?php

        if (
            !empty(
                $user["start_time"]
            )
        ) {

            echo htmlspecialchars(
                date(
                    "Y-m-d\TH:i",
                    strtotime(
                        $user["start_time"]
                    )
                ),
                ENT_QUOTES,
                "UTF-8"
            );
        }

    ?>"
>


<div class="button-row">

<button
    type="submit"
    name="save_start"
    class="save"
>
Save
</button>


<button
    type="submit"
    name="clear_start"
    class="clear"
    onclick="
        return confirm(
            'Clear the Start Time?'
        );
    "
>
Clear
</button>

</div>

</form>

</td>


<!-- ==================================================
     STOP TIME CONTROL
================================================== -->

<td>

<form
    method="POST"
    action="admin.php"
    class="datetime-form"
>

<input
    type="hidden"
    name="id"
    value="<?php
        echo intval(
            $user["id"]
        );
    ?>"
>


<input
    type="datetime-local"
    name="stop_time"
    value="<?php

        if (
            !empty(
                $user["stop_time"]
            )
        ) {

            echo htmlspecialchars(
                date(
                    "Y-m-d\TH:i",
                    strtotime(
                        $user["stop_time"]
                    )
                ),
                ENT_QUOTES,
                "UTF-8"
            );
        }

    ?>"
>


<div class="button-row">

<button
    type="submit"
    name="save_stop"
    class="save"
>
Save
</button>


<button
    type="submit"
    name="clear_stop"
    class="clear"
    onclick="
        return confirm(
            'Clear the Stop Time?'
        );
    "
>
Clear
</button>

</div>

</form>

</td>


</tr>

<?php

    }

} else {

?>

<tr>

<td
    colspan="12"
    class="no-users"
>
No users found in app_users.
</td>

</tr>

<?php

}

?>

</tbody>

</table>

</div>


</div>


</body>

</html>

<?php

mysqli_close($conn);

?>
