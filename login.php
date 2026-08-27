<?php
/*
============================================================
 CURD-EMPLOYEE2
 USER LOGIN WITH START / STOP TIME CONTROL
 + APPLICATION BASED REDIRECTION
============================================================

Database:
    app_users

Columns used:
    user_id
    user_name
    password_hash
    active
    start_time
    stop_time
    application
    last_login

Timezone:
    Asia/Kolkata

USER APPLICATION MAPPING:

    ravi   -> index.php
    ravi2  -> payroll.php
    ravi3  -> controller.php

IMPORTANT:
    Time control is checked during login.
    Individual application pages should also check
    the logged-in session/time as required.

============================================================
*/

date_default_timezone_set("Asia/Kolkata");

session_start();

require_once __DIR__ . "/db.php";


/* =========================================================
   ALLOWED APPLICATIONS
========================================================= */

$allowed_applications = [

    "index.php",
    "payroll.php",
    "controller.php"

];


/* =========================================================
   IF ALREADY LOGGED IN
========================================================= */

if (
    isset($_SESSION["app_user_id"]) &&
    $_SESSION["app_user_id"] !== ""
) {

    /*
     * If the application was stored in the session,
     * use it for redirection.
     */

    $application =
        $_SESSION["app_application"]
        ?? "index.php";


    /*
     * Safety check.
     */

    if (
        !in_array(
            $application,
            $allowed_applications,
            true
        )
    ) {

        $application = "index.php";
    }


    header(
        "Location: " . $application
    );

    exit;
}


/* =========================================================
   MESSAGE
========================================================= */

$error = "";


/* =========================================================
   LOGIN
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["login"])
) {

    $user_id =
        trim(
            $_POST["user_id"] ?? ""
        );

    $password =
        $_POST["password"] ?? "";


    /* =====================================================
       BASIC INPUT CHECK
    ===================================================== */

    if (
        $user_id === "" ||
        $password === ""
    ) {

        $error =
            "Please enter User ID and Password.";

    } else {


        /* =================================================
           FIND USER
        ================================================= */

        $stmt =
            $conn->prepare("
                SELECT
                    id,
                    user_id,
                    user_name,
                    password_hash,
                    active,
                    start_time,
                    stop_time,
                    application,
                    last_login
                FROM app_users
                WHERE user_id = ?
                LIMIT 1
            ");


        if (!$stmt) {

            $error =
                "Login preparation failed.";

        } else {


            $stmt->bind_param(
                "s",
                $user_id
            );


            $stmt->execute();


            $result =
                $stmt->get_result();


            /* =============================================
               USER NOT FOUND
            ============================================= */

            if (
                !$result ||
                $result->num_rows === 0
            ) {

                $error =
                    "Invalid User ID or Password.";

            } else {

                $user =
                    $result->fetch_assoc();


                /* =========================================
                   CHECK PASSWORD
                ========================================= */

                if (
                    !password_verify(
                        $password,
                        $user["password_hash"]
                    )
                ) {

                    $error =
                        "Invalid User ID or Password.";

                }


                /* =========================================
                   CHECK ACTIVE
                ========================================= */

                elseif (
                    (int)$user["active"] !== 1
                ) {

                    $error =
                        "Your account is inactive. Please contact the administrator.";

                } else {


                    /* =====================================
                       CURRENT IST TIME
                    ===================================== */

                    $now =
                        new DateTime(
                            "now",
                            new DateTimeZone(
                                "Asia/Kolkata"
                            )
                        );


                    /* =====================================
                       START TIME
                    ===================================== */

                    if (
                        !empty(
                            $user["start_time"]
                        )
                    ) {

                        $start =
                            new DateTime(
                                $user["start_time"],
                                new DateTimeZone(
                                    "Asia/Kolkata"
                                )
                            );


                        if (
                            $now < $start
                        ) {

                            $error =
                                "Login is not available yet. Your account starts at "
                                .
                                $start->format(
                                    "d-m-Y H:i:s"
                                )
                                .
                                " IST.";
                        }
                    }


                    /* =====================================
                       STOP TIME
                    ===================================== */

                    if (
                        $error === "" &&
                        !empty(
                            $user["stop_time"]
                        )
                    ) {

                        $stop =
                            new DateTime(
                                $user["stop_time"],
                                new DateTimeZone(
                                    "Asia/Kolkata"
                                )
                            );


                        if (
                            $now > $stop
                        ) {

                            $error =
                                "Your login time has expired. Your account stopped at "
                                .
                                $stop->format(
                                    "d-m-Y H:i:s"
                                )
                                .
                                " IST.";
                        }
                    }


                    /* =====================================
                       APPLICATION CHECK
                    ===================================== */

                    if (
                        $error === ""
                    ) {

                        $application =
                            trim(
                                $user["application"]
                                ?? ""
                            );


                        /*
                         * Application must be one of
                         * the approved application files.
                         */

                        if (
                            !in_array(
                                $application,
                                $allowed_applications,
                                true
                            )
                        ) {

                            $error =
                                "Application is not authorized for this user.";

                        }
                    }


                    /* =====================================
                       LOGIN SUCCESS
                    ===================================== */

                    if (
                        $error === ""
                    ) {


                        /*
                         * Regenerate session ID after
                         * successful authentication.
                         */

                        session_regenerate_id(
                            true
                        );


                        /* =================================
                           STORE USER SESSION
                        ================================= */

                        $_SESSION[
                            "app_user_id"
                        ] =
                            $user["user_id"];


                        $_SESSION[
                            "app_user_name"
                        ] =
                            $user["user_name"];


                        $_SESSION[
                            "app_start_time"
                        ] =
                            $user["start_time"];


                        $_SESSION[
                            "app_stop_time"
                        ] =
                            $user["stop_time"];


                        /*
                         * Store assigned application.
                         */

                        $_SESSION[
                            "app_application"
                        ] =
                            $application;


                        /* =================================
                           UPDATE LAST LOGIN
                        ================================= */

                        $update =
                            $conn->prepare("
                                UPDATE app_users
                                SET last_login = ?
                                WHERE user_id = ?
                            ");


                        if ($update) {

                            $login_time =
                                $now->format(
                                    "Y-m-d H:i:s"
                                );


                            $update->bind_param(
                                "ss",
                                $login_time,
                                $user["user_id"]
                            );


                            $update->execute();


                            $update->close();
                        }


                        /* =================================
                           REDIRECT TO ASSIGNED APPLICATION
                        ================================= */

                        header(
                            "Location: " . $application
                        );

                        exit;
                    }
                }
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
CURD-EMPLOYEE2 - User Login
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

.login-box {

    width: 100%;

    max-width: 420px;

    margin: 80px auto;

    background: white;

    padding: 30px;

    border-radius: 12px;

    box-shadow:
        0 4px 15px
        rgba(0,0,0,0.15);

    text-align: center;
}

h1 {

    margin-top: 0;

    color: #1d3557;
}

.subtitle {

    color: #666;

    margin-bottom: 25px;
}

.error {

    background: #f8d7da;

    color: #842029;

    padding: 12px;

    border-radius: 6px;

    margin-bottom: 18px;

    font-weight: bold;

    line-height: 1.5;
}

.form-group {

    text-align: left;

    margin-bottom: 15px;
}

label {

    display: block;

    font-weight: bold;

    margin-bottom: 6px;
}

input {

    width: 100%;

    padding: 12px;

    border: 1px solid #aaa;

    border-radius: 6px;

    font-size: 16px;
}

button {

    width: 100%;

    padding: 12px;

    border: none;

    border-radius: 6px;

    background: #0d6efd;

    color: white;

    font-size: 16px;

    cursor: pointer;

    margin-top: 10px;
}

button:hover {

    opacity: 0.85;
}

.footer {

    margin-top: 20px;

    color: #777;

    font-size: 13px;
}

</style>

</head>

<body>

<div class="login-box">

<h1>
CURD-EMPLOYEE2
</h1>

<div class="subtitle">
Employee Payment System
</div>


<?php

if ($error !== "") {

?>

<div class="error">

<?php

echo htmlspecialchars(
    $error,
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
    action="login.php"
>


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
    autofocus
>

</div>


<div class="form-group">

<label for="password">
Password
</label>

<input
    type="password"
    id="password"
    name="password"
    required
>

</div>


<button
    type="submit"
    name="login"
>
LOGIN
</button>

</form>


<div class="footer">

Authorized users only

<br>

Time zone: Asia/Kolkata (IST)

</div>

</div>

</body>

</html>

<?php

mysqli_close($conn);

?>
