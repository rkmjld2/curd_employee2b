<?php
require_once __DIR__ . "/license_guard.php";
?>

<!DOCTYPE html>
<html>

<head>

    <meta charset="UTF-8">

    <title>Controller</title>

    <style>

        body {
            font-family: Arial, sans-serif;
            background: #f2f2f2;
            text-align: center;
            margin-top: 80px;
        }

        .box {
            width: 450px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px #aaa;
        }

        h2 {
            color: #333;
        }

        /* =================================================
           LOGOUT BUTTON
        ================================================= */

        .logout-button {
            display: inline-block;
            padding: 10px 25px;
            margin-top: 20px;
            background: #dc3545;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
        }

        .logout-button:hover {
            background: #b02a37;
        }

    </style>

</head>

<body>

<div class="box">

    <h2>
        Controller Application
    </h2>

    <p>
        Controller application is running.
    </p>

    <p>
        License verification successful.
    </p>


    <!-- =================================================
         LOGOUT
    ================================================= -->

    <a
        href="logout.php"
        class="logout-button"
    >
        Logout
    </a>

</div>

</body>

</html>
