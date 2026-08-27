<?php
require_once __DIR__ . "/license_guard.php";

$basic = 0;
$da = 0;
$da_amount = 0;
$total = 0;

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $basic = (float)($_POST["basic"] ?? 0);
    $da = (float)($_POST["da"] ?? 0);

    $da_amount = $basic * $da / 100;
    $total = $basic + $da_amount;
}
?>

<!DOCTYPE html>
<html>
<head>

    <meta charset="UTF-8">

    <title>Payroll</title>

    <style>

        body {
            font-family: Arial, sans-serif;
            background: #f2f2f2;
            text-align: center;
            margin-top: 50px;
        }

        .box {
            width: 400px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 0 10px #aaa;
        }

        input {
            width: 90%;
            padding: 10px;
            margin: 8px;
        }

        button {
            padding: 10px 25px;
            margin-top: 10px;
            cursor: pointer;
        }

        .result {
            margin-top: 20px;
            font-size: 18px;
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

    <h2>Employee Payroll</h2>


    <form method="post">

        <input
            type="number"
            name="basic"
            placeholder="Basic Salary"
            step="0.01"
            required
        >

        <input
            type="number"
            name="da"
            placeholder="DA Percentage"
            step="0.01"
            required
        >

        <button type="submit">
            Calculate
        </button>

    </form>


    <?php if ($_SERVER["REQUEST_METHOD"] === "POST"): ?>

        <div class="result">

            <p>
                <strong>Basic Salary:</strong>
                ₹<?= number_format($basic, 2) ?>
            </p>

            <p>
                <strong>DA:</strong>
                <?= number_format($da, 2) ?>%
            </p>

            <p>
                <strong>DA Amount:</strong>
                ₹<?= number_format($da_amount, 2) ?>
            </p>

            <hr>

            <p>
                <strong>Total Salary:</strong>
                ₹<?= number_format($total, 2) ?>
            </p>

        </div>

    <?php endif; ?>


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
