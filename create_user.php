<?php
/*
============================================================
CURD-EMPLOYEE2
CREATE USER
============================================================

Database:
    employeer

Table:
    app_users

Purpose:
    Create application users securely.

IMPORTANT:
    Application is entered manually by the administrator.

Examples:
    index.php
    payroll.php
    controller.php
    /crud3/index.php
    /abc/index.php

Password:
    Stored using PHP password_hash()

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
   VARIABLES
========================================================= */

$message = "";
$message_type = "";

$user_id = "";
$user_name = "";
$password = "";
$confirm_password = "";
$application = "";
$start_time = "";
$stop_time = "";
$active = 1;


/* =========================================================
   CREATE USER
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $user_id =
        trim($_POST["user_id"] ?? "");

    $user_name =
        trim($_POST["user_name"] ?? "");

    $password =
        $_POST["password"] ?? "";

    $confirm_password =
        $_POST["confirm_password"] ?? "";

    $application =
        trim($_POST["application"] ?? "");

    $active =
        isset($_POST["active"])
            ? 1
            : 0;

    $start_time =
        trim($_POST["start_time"] ?? "");

    $stop_time =
        trim($_POST["stop_time"] ?? "");


    /* =====================================================
       BASIC VALIDATION
    ===================================================== */

    if ($user_id === "") {

        $message = "User ID is required.";
        $message_type = "error";

    }
    elseif (!preg_match(
        '/^[A-Za-z0-9_-]+$/',
        $user_id
    )) {

        $message =
            "User ID may contain only letters, numbers, underscore and hyphen.";

        $message_type = "error";

    }
    elseif ($user_name === "") {

        $message = "User Name is required.";
        $message_type = "error";

    }
    elseif ($password === "") {

        $message = "Password is required.";
        $message_type = "error";

    }
    elseif (strlen($password) < 6) {

        $message =
            "Password must contain at least 6 characters.";

        $message_type = "error";

    }
    elseif ($password !== $confirm_password) {

        $message =
            "Passwords do not match.";

        $message_type = "error";

    }
    elseif ($application === "") {

        $message =
            "Application is required.";

        $message_type = "error";
    }


    /* =====================================================
       APPLICATION VALIDATION
    ===================================================== */

    if ($message === "") {

        /*
         * Reject dangerous characters.
         */

        if (
            preg_match(
                '/[\r\n\t]/',
                $application
            )
        ) {

            $message =
                "Application contains invalid characters.";

            $message_type = "error";
        }

        /*
         * Reject Windows backslash paths.
         */

        elseif (
            strpos(
                $application,
                "\\"
            ) !== false
        ) {

            $message =
                "Please use / instead of \\ in Application.";

            $message_type = "error";
        }

        /*
         * Reject directory traversal.
         */

        elseif (
            strpos(
                $application,
                ".."
            ) !== false
        ) {

            $message =
                "Invalid Application path.";

            $message_type = "error";
        }

        /*
         * Reject external URLs.
         */

        elseif (
            preg_match(
                '/^[a-zA-Z][a-zA-Z0-9+\-.]*:/',
                $application
            )
        ) {

            $message =
                "External URLs are not allowed.";

            $message_type = "error";
        }

        /*
         * Reject protocol-relative URLs.
         */

        elseif (
            substr(
                $application,
                0,
                2
            ) === "//"
        ) {

            $message =
                "Invalid Application path.";

            $message_type = "error";
        }

        /*
         * If application is a local file,
         * allow only PHP filename.
         */

        elseif (
            substr(
                $application,
                0,
                1
            ) !== "/"
            &&
            !preg_match(
                '/^[A-Za-z0-9_-]+\.php$/i',
                $application
            )
        ) {

            $message =
                "Invalid application. Example: controller.php";

            $message_type = "error";
        }
    }


    /* =====================================================
       CHECK USER ID
    ===================================================== */

    if ($message === "") {

        $stmt = $conn->prepare("
            SELECT id
            FROM app_users
            WHERE user_id = ?
            LIMIT 1
        ");

        if (!$stmt) {

            $message =
                "Could not prepare user check: "
                . $conn->error;

            $message_type = "error";

        }
        else {

            $stmt->bind_param(
                "s",
                $user_id
            );

            $stmt->execute();

            $result =
                $stmt->get_result();

            if (
                $result &&
                $result->num_rows > 0
            ) {

                $message =
                    "This User ID already exists.";

                $message_type = "error";
            }

            $stmt->close();
        }
    }


    /* =====================================================
       CHECK START / STOP TIME
    ===================================================== */

    $start_datetime = null;
    $stop_datetime = null;


    if ($message === "") {

        if ($start_time !== "") {

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
        }


        if ($stop_time !== "") {

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
        }


        /*
         * Start must be before Stop.
         */

        if (
            $start_datetime !== null &&
            $stop_datetime !== null
        ) {

            $start_timestamp =
                strtotime(
                    $start_datetime
                );

            $stop_timestamp =
                strtotime(
                    $stop_datetime
                );


            if (
                $start_timestamp !== false &&
                $stop_timestamp !== false &&
                $start_timestamp >= $stop_timestamp
            ) {

                $message =
                    "Stop Time must be later than Start Time.";

                $message_type = "error";
            }
        }
    }


    /* =====================================================
       INSERT USER
    ===================================================== */

    if ($message === "") {

        /*
         * Secure password hash.
         */

        $password_hash =
            password_hash(
                $password,
                PASSWORD_DEFAULT
            );


        $stmt = $conn->prepare("
            INSERT INTO app_users
            (
                user_id,
                user_name,
                password_hash,
                active,
                start_time,
                stop_time,
                application
            )
            VALUES
            (?, ?, ?, ?, ?, ?, ?)
        ");


        if (!$stmt) {

            $message =
                "Could not prepare user creation: "
                . $conn->error;

            $message_type = "error";

        }
        else {

            $stmt->bind_param(
                "sssisss",
                $user_id,
                $user_name,
                $password_hash,
                $active,
                $start_datetime,
                $stop_datetime,
                $application
            );


            if ($stmt->execute()) {

                $message =
                    "User created successfully.";

                $message_type =
                    "success";


                /*
                 * Clear form after successful creation.
                 */

                $user_id = "";
                $user_name = "";
                $password = "";
                $confirm_password = "";
                $application = "";
                $start_time = "";
                $stop_time = "";
                $active = 1;

            }
            else {

                $message =
                    "User creation failed: "
                    . $stmt->error;

                $message_type =
                    "error";
            }


            $stmt->close();
        }
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
Create User - CURD-EMPLOYEE2
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

    background: #f2f2f2;
}


.container {

    max-width: 520px;

    margin: 40px auto;

    background: white;

    padding: 30px;

    border-radius: 12px;

    box-shadow:
        0 3px 15px
        rgba(0,0,0,0.15);
}


h1 {

    text-align: center;

    margin-top: 0;

    color: #1d3557;
}


.subtitle {

    text-align: center;

    color: #666;

    margin-bottom: 25px;
}


.form-group {

    margin-bottom: 18px;
}


label {

    display: block;

    font-weight: bold;

    margin-bottom: 7px;
}


input {

    width: 100%;

    padding: 11px;

    border: 1px solid #aaa;

    border-radius: 6px;

    font-size: 15px;
}


input:focus {

    outline: none;

    border-color: #007bff;

    box-shadow:
        0 0 4px
        rgba(0,123,255,0.25);
}


.application-note {

    margin-top: 6px;

    color: #666;

    font-size: 13px;

    line-height: 1.5;
}


.checkbox-group {

    display: flex;

    align-items: center;

    gap: 8px;

    margin-bottom: 20px;
}


.checkbox-group input {

    width: auto;
}


.create-button {

    width: 100%;

    padding: 12px;

    border: none;

    border-radius: 6px;

    background: #198754;

    color: white;

    font-size: 16px;

    cursor: pointer;
}


.create-button:hover {

    opacity: 0.85;
}


.admin-button {

    display: block;

    width: 100%;

    padding: 11px;

    margin-top: 10px;

    border-radius: 6px;

    background: #6c757d;

    color: white;

    text-align: center;

    text-decoration: none;

}


.admin-button:hover {

    opacity: 0.85;
}


.message {

    padding: 12px;

    border-radius: 6px;

    margin-bottom: 20px;

    text-align: center;

    font-weight: bold;
}


.success {

    background: #d1e7dd;

    color: #0f5132;
}


.error {

    background: #f8d7da;

    color: #842029;
}


.note {

    background: #fff3cd;

    color: #664d03;

    padding: 12px;

    border-radius: 6px;

    margin-bottom: 20px;

    line-height: 1.6;

    font-size: 14px;
}

</style>

</head>


<body>


<div class="container">


<h1>
Create User
</h1>


<div class="subtitle">
CURD-EMPLOYEE2 Administrator
</div>


<div class="note">

<strong>Application:</strong><br>

Enter the PHP application that this user
should open after successful login.

<br><br>

Examples:

<br>
<code>index.php</code>

<br>
<code>payroll.php</code>

<br>
<code>controller.php</code>

<br>
<code>/crud3/index.php</code>

<br>
<code>/abc/index.php</code>

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


<form
    method="POST"
    action="create_user.php"
>


<!-- USER ID -->

<div class="form-group">

<label for="user_id">
User ID
</label>

<input
    type="text"
    id="user_id"
    name="user_id"
    maxlength="50"
    required
    placeholder="Example: ravi4"
    value="<?php

        echo htmlspecialchars(
            $user_id,
            ENT_QUOTES,
            "UTF-8"
        );

    ?>"
>

</div>


<!-- USER NAME -->

<div class="form-group">

<label for="user_name">
User Name
</label>

<input
    type="text"
    id="user_name"
    name="user_name"
    maxlength="100"
    required
    placeholder="Example: Ravi4"
    value="<?php

        echo htmlspecialchars(
            $user_name,
            ENT_QUOTES,
            "UTF-8"
        );

    ?>"
>

</div>


<!-- PASSWORD -->

<div class="form-group">

<label for="password">
Password
</label>

<input
    type="password"
    id="password"
    name="password"
    required
    autocomplete="new-password"
>

</div>


<!-- CONFIRM PASSWORD -->

<div class="form-group">

<label for="confirm_password">
Confirm Password
</label>

<input
    type="password"
    id="confirm_password"
    name="confirm_password"
    required
    autocomplete="new-password"
>

</div>


<!-- APPLICATION -->

<div class="form-group">

<label for="application">
Application
</label>

<input
    type="text"
    id="application"
    name="application"
    maxlength="255"
    required
    placeholder="Example: controller.php"
    value="<?php

        echo htmlspecialchars(
            $application,
            ENT_QUOTES,
            "UTF-8"
        );

    ?>"
>

<div class="application-note">

Enter the value exactly as it should be
stored in the <strong>application</strong>
column.

</div>

</div>


<!-- ACTIVE -->

<div class="checkbox-group">

<input
    type="checkbox"
    id="active"
    name="active"
    value="1"

    <?php

    echo
        $active == 1
        ? "checked"
        : "";

    ?>
>

<label
    for="active"
    style="margin:0;"
>
Active User
</label>

</div>


<!-- START TIME -->

<div class="form-group">

<label for="start_time">
Start Time (Optional)
</label>

<input
    type="datetime-local"
    id="start_time"
    name="start_time"
    value="<?php

        echo htmlspecialchars(
            $start_time,
            ENT_QUOTES,
            "UTF-8"
        );

    ?>"
>

</div>


<!-- STOP TIME -->

<div class="form-group">

<label for="stop_time">
Stop Time (Optional)
</label>

<input
    type="datetime-local"
    id="stop_time"
    name="stop_time"
    value="<?php

        echo htmlspecialchars(
            $stop_time,
            ENT_QUOTES,
            "UTF-8"
        );

    ?>"
>

</div>


<!-- CREATE -->

<button
    type="submit"
    class="create-button"
>
CREATE USER
</button>


</form>


<a
    href="admin.php"
    class="admin-button"
>
Back to User Management
</a>


</div>


</body>

</html>


<?php

mysqli_close($conn);

?>