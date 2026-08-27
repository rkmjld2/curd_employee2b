<?php
/*
============================================================
CURD-EMPLOYEE2
USER LOGOUT
============================================================
*/

session_start();

/* =========================================================
   DESTROY LOGIN SESSION
========================================================= */

$_SESSION = array();

if (ini_get("session.use_cookies")) {

    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

session_destroy();

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Logged Out</title>

<style>

body {

    margin: 0;

    font-family: Arial, sans-serif;

    background: #f2f4f7;

}

.box {

    width: 90%;

    max-width: 450px;

    margin: 100px auto;

    background: white;

    padding: 35px;

    border-radius: 12px;

    box-shadow:
        0 4px 15px
        rgba(0,0,0,0.15);

    text-align: center;

}

h1 {

    color: #198754;

}

p {

    font-size: 17px;

    color: #555;

}

.login-button {

    display: inline-block;

    margin-top: 20px;

    padding: 12px 25px;

    background: #0d6efd;

    color: white;

    text-decoration: none;

    border-radius: 6px;

    font-weight: bold;

}

.login-button:hover {

    opacity: 0.85;

}

</style>

</head>

<body>

<div class="box">

<h1>
Logged Out
</h1>

<p>
You have been successfully logged out.
</p>

<a
    href="login.php"
    class="login-button"
>
Login Again
</a>

</div>

</body>

</html>
