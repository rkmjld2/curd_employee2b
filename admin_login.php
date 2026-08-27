<?php
/*
============================================================
 CURD-EMPLOYEE2
 ADMINISTRATOR LOGIN
============================================================

Purpose:
    Administrator-only login for User Management.

Authentication:
    ADMIN_PASSWORD from environment variable.

IMPORTANT:
    This is separate from normal user login.

Normal user:
    login.php

Administrator:
    admin_login.php

Timezone:
    Asia/Kolkata

============================================================
*/

date_default_timezone_set("Asia/Kolkata");

session_start();


/* =========================================================
   ADMIN PASSWORD
========================================================= */

$admin_password =
    getenv("ADMIN_PASSWORD") ?: "";


/* =========================================================
   IF ADMIN ALREADY LOGGED IN
========================================================= */

if (
    isset($_SESSION["admin_logged_in"]) &&
    $_SESSION["admin_logged_in"] === true
) {

    header("Location: admin.php");

    exit;
}


/* =========================================================
   LOGIN ERROR
========================================================= */

$login_error = "";


/* =========================================================
   ADMIN LOGIN
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["admin_login"])
) {

    $password =
        $_POST["password"] ?? "";


    /* =====================================================
       CHECK ADMIN PASSWORD
    ===================================================== */

    if (
        $admin_password !== "" &&
        hash_equals(
            $admin_password,
            $password
        )
    ) {


        /*
         * Regenerate session ID after
         * successful authentication.
         */

        session_regenerate_id(true);


        /* =================================================
           ADMIN SESSION
        ================================================= */

        $_SESSION["admin_logged_in"] = true;


        /*
         * Optional identification of
         * administrator session.
         */

        $_SESSION["admin_name"] =
            "Administrator";


        /* =================================================
           GO TO ADMIN PANEL
        ================================================= */

        header(
            "Location: admin.php"
        );

        exit;

    }
    else {

        $login_error =
            "Invalid administrator password.";
    }
}

?>
<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>CURD-EMPLOYEE2 - Administrator Login</title>


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


.login-box {

    max-width: 420px;

    margin: 80px auto;

    background: white;

    padding: 30px;

    border-radius: 12px;

    box-shadow:
        0 3px 15px
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


input[type="password"] {

    width: 100%;

    padding: 12px;

    font-size: 16px;

    border: 1px solid #aaa;

    border-radius: 6px;

    margin-bottom: 15px;
}


button {

    width: 100%;

    padding: 12px;

    border: none;

    border-radius: 6px;

    background: #6f42c1;

    color: white;

    font-size: 16px;

    cursor: pointer;
}


button:hover {

    opacity: 0.85;
}


.error {

    color: #842029;

    background: #f8d7da;

    border-radius: 6px;

    padding: 10px;

    margin-bottom: 15px;

    font-weight: bold;
}


.small {

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
Administrator Login
</div>


<?php

if ($login_error !== "") {

?>

<div class="error">

<?php

echo htmlspecialchars(
    $login_error,
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
    action="admin_login.php"
>


<input
    type="password"
    name="password"
    placeholder="Enter Administrator Password"
    autocomplete="current-password"
    required
    autofocus
>


<button
    type="submit"
    name="admin_login"
>
ADMIN LOGIN
</button>


</form>


<div class="small">
User Management Administration
</div>


</div>


</body>

</html>