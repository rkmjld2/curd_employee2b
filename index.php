<?php
/*
============================================================
 CURD-EMPLOYEE2
 EMPLOYEE PAYMENT CRUD
============================================================

Database:
    employee

User table:
    app_users

IMPORTANT:
    Each employee record belongs to one user through:

        employee.user_id

    A logged-in user can see and modify ONLY
    his/her own employee records.

============================================================
*/

/* =========================================================
   COMMERCIAL LICENSE PROTECTION
========================================================= */

require_once __DIR__ . "/license_guard.php";

date_default_timezone_set("Asia/Kolkata");

error_reporting(E_ALL);
ini_set("display_errors", "1");

require_once __DIR__ . "/db.php";


/* =========================================================
   LOGIN PROTECTION
========================================================= */

if (
    !isset($_SESSION["app_user_id"]) ||
    $_SESSION["app_user_id"] === ""
) {

    header("Location: login.php");

    exit;
}


/* =========================================================
   CURRENT LOGGED-IN USER
========================================================= */

$current_user_id =
    $_SESSION["app_user_id"];

$current_user_name =
    $_SESSION["app_user_name"] ?? "";


/* =========================================================
   CHECK USER ACTIVE + START / STOP TIME
========================================================= */

$stmt = $conn->prepare("
    SELECT user_id, user_name, active, start_time, stop_time
    FROM app_users
    WHERE user_id = ?
    LIMIT 1
");

if (!$stmt) {

    $_SESSION = [];

    session_destroy();

    header("Location: login.php");

    exit;
}

$stmt->bind_param(
    "s",
    $current_user_id
);

if (!$stmt->execute()) {

    $stmt->close();

    $_SESSION = [];

    session_destroy();

    header("Location: login.php");

    exit;
}

$user_result =
    $stmt->get_result();

if (
    !$user_result ||
    $user_result->num_rows === 0
) {

    $stmt->close();

    $_SESSION = [];

    session_destroy();

    header("Location: login.php");

    exit;
}

$user =
    $user_result->fetch_assoc();

$stmt->close();


$now =
    new DateTime(
        "now",
        new DateTimeZone(
            "Asia/Kolkata"
        )
    );


/* =========================================================
   ACTIVE CHECK
========================================================= */

if (
    (int)$user["active"] !== 1
) {

    $_SESSION = [];

    session_destroy();

    header("Location: login.php");

    exit;
}


/* =========================================================
   START TIME CHECK
========================================================= */

if (
    !empty($user["start_time"])
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

        $_SESSION = [];

        session_destroy();

        header("Location: login.php");

        exit;
    }
}


/* =========================================================
   STOP TIME CHECK
========================================================= */

if (
    !empty($user["stop_time"])
) {

    $stop =
        new DateTime(
            $user["stop_time"],
            new DateTimeZone(
                "Asia/Kolkata"
            )
        );


    /*
     * IMPORTANT:
     *
     * Use >= so that access stops exactly
     * at the configured stop time.
     */

    if (
        $now >= $stop
    ) {

        $_SESSION = [];

        session_destroy();

        header("Location: login.php");

        exit;
    }
}


/* =========================================================
   LOGOUT
========================================================= */

if (
    isset($_GET["logout"])
) {

    $_SESSION = [];

    session_destroy();

    header("Location: login.php");

    exit;
}


/* =========================================================
   MESSAGE
========================================================= */

$message = "";

$message_type = "";


/* =========================================================
   DELETE EMPLOYEE
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["delete_id"])
) {

    $id =
        intval(
            $_POST["delete_id"]
        );


    if (
        $id > 0
    ) {

        /*
         * IMPORTANT:
         *
         * Delete only if the employee belongs
         * to the currently logged-in user.
         */

        $stmt =
            $conn->prepare("
                DELETE FROM employee
                WHERE id = ?
                AND user_id = ?
            ");

        if ($stmt) {

            $stmt->bind_param(
                "is",
                $id,
                $current_user_id
            );


            if (
                $stmt->execute()
            ) {

                if (
                    $stmt->affected_rows > 0
                ) {

                    $message =
                        "Employee record deleted successfully.";

                    $message_type =
                        "success";

                } else {

                    $message =
                        "Employee record not found.";

                    $message_type =
                        "error";
                }

            } else {

                $message =
                    "Delete failed.";

                $message_type =
                    "error";
            }

            $stmt->close();

        } else {

            $message =
                "Delete preparation failed.";

            $message_type =
                "error";
        }
    }
}


/* =========================================================
   ADD / UPDATE
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["save"])
) {

    $id =
        isset($_POST["id"])
            ? intval($_POST["id"])
            : 0;


    $employee_name =
        mysqli_real_escape_string(
            $conn,
            trim(
                $_POST["employee_name"] ?? ""
            )
        );


    $basic_pay =
        floatval(
            $_POST["basic_pay"] ?? 0
        );


    $da_percent =
        floatval(
            $_POST["da_percent"] ?? 0
        );


    $hra_percent =
        floatval(
            $_POST["hra_percent"] ?? 0
        );


    $pf_deduction =
        floatval(
            $_POST["pf_deduction"] ?? 0
        );


    $other_allowance =
        floatval(
            $_POST["other_allowance"] ?? 0
        );


    /* =====================================================
       CALCULATIONS
    ===================================================== */

    $da_amount =
        (
            $basic_pay *
            $da_percent
        ) / 100;


    $hra_amount =
        (
            $basic_pay *
            $hra_percent
        ) / 100;


    /*
     * Total =
     *
     * Basic
     * + DA
     * + HRA
     * - PF
     * + Other Allowance
     */

    $total_payment =
        $basic_pay
        + $da_amount
        + $hra_amount
        - $pf_deduction
        + $other_allowance;


    /* =====================================================
       UPDATE EXISTING EMPLOYEE
    ===================================================== */

    if (
        $id > 0
    ) {

        /*
         * IMPORTANT:
         *
         * Update ONLY the current user's record.
         */

        $stmt =
            $conn->prepare("
                UPDATE employee
                SET
                    Employee_name = ?,
                    BASIC_PAY = ?,
                    DA_PERCENT = ?,
                    DA_AMOUNT = ?,
                    HRA_PERCENT = ?,
                    HRA_AMOUNT = ?,
                    PF_DEDUCTION = ?,
                    ANY_OTHER_ALLOWANCE = ?,
                    TOTAL_PAYMENT = ?
                WHERE
                    id = ?
                    AND user_id = ?
            ");


        if ($stmt) {

            $stmt->bind_param(
                "sddddddddis",
                $employee_name,
                $basic_pay,
                $da_percent,
                $da_amount,
                $hra_percent,
                $hra_amount,
                $pf_deduction,
                $other_allowance,
                $total_payment,
                $id,
                $current_user_id
            );


            if (
                $stmt->execute()
            ) {

                if (
                    $stmt->affected_rows >= 0
                ) {

                    $message =
                        "Employee record updated successfully.";

                    $message_type =
                        "success";
                }

            } else {

                $message =
                    "Update failed: " .
                    $stmt->error;

                $message_type =
                    "error";
            }


            $stmt->close();

        } else {

            $message =
                "Update preparation failed.";

            $message_type =
                "error";
        }


    } else {


        /* =================================================
           ADD NEW EMPLOYEE
        ================================================= */

        /*
         * IMPORTANT:
         *
         * user_id is automatically taken from
         * the logged-in session.
         */

        $stmt =
            $conn->prepare("
                INSERT INTO employee
                (
                    user_id,
                    Employee_name,
                    BASIC_PAY,
                    DA_PERCENT,
                    DA_AMOUNT,
                    HRA_PERCENT,
                    HRA_AMOUNT,
                    PF_DEDUCTION,
                    ANY_OTHER_ALLOWANCE,
                    TOTAL_PAYMENT
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?
                )
            ");


        if ($stmt) {

            $stmt->bind_param(
                "ssdddddddd",
                $current_user_id,
                $employee_name,
                $basic_pay,
                $da_percent,
                $da_amount,
                $hra_percent,
                $hra_amount,
                $pf_deduction,
                $other_allowance,
                $total_payment
            );


            if (
                $stmt->execute()
            ) {

                $message =
                    "Employee record added successfully.";

                $message_type =
                    "success";

            } else {

                $message =
                    "Insert failed: " .
                    $stmt->error;

                $message_type =
                    "error";
            }


            $stmt->close();

        } else {

            $message =
                "Insert preparation failed.";

            $message_type =
                "error";
        }
    }
}


/* =========================================================
   EDIT EMPLOYEE
========================================================= */

$edit = NULL;


if (
    isset($_GET["edit"])
) {

    $id =
        intval(
            $_GET["edit"]
        );


    if (
        $id > 0
    ) {

        /*
         * IMPORTANT:
         *
         * User can edit only his/her own record.
         */

        $stmt =
            $conn->prepare("
                SELECT *
                FROM employee
                WHERE id = ?
                AND user_id = ?
                LIMIT 1
            ");


        if ($stmt) {

            $stmt->bind_param(
                "is",
                $id,
                $current_user_id
            );


            $stmt->execute();


            $edit_result =
                $stmt->get_result();


            if (
                $edit_result &&
                $edit_result->num_rows > 0
            ) {

                $edit =
                    $edit_result->fetch_assoc();
            }


            $stmt->close();
        }
    }
}


/* =========================================================
   SQL SELECT SEARCH
========================================================= */

$search_sql = "";

$search_result = NULL;

$search_error = "";

$search_count = 0;

$search_fields = array();

$has_id_column = false;


if (
    isset($_GET["search"])
) {

    $search_sql =
        trim(
            $_GET["search"]
        );


    if (
        $search_sql !== ""
    ) {

        /*
         * Allow one optional semicolon at the end.
         */

        $search_sql =
            rtrim(
                $search_sql
            );

        $search_sql =
            rtrim(
                $search_sql,
                ";"
            );

        $search_sql =
            trim(
                $search_sql
            );


        /* =================================================
           MUST BEGIN WITH SELECT
        ================================================= */

        if (
            !preg_match(
                '/^SELECT\s/i',
                $search_sql
            )
        ) {

            $search_error =
                "Only SELECT statements are allowed.";


        /* =================================================
           REJECT DANGEROUS COMMANDS
        ================================================= */

        } elseif (
            preg_match(
                '/\b(
                    INSERT|
                    UPDATE|
                    DELETE|
                    DROP|
                    ALTER|
                    TRUNCATE|
                    CREATE|
                    RENAME|
                    REPLACE|
                    GRANT|
                    REVOKE|
                    CALL|
                    LOAD|
                    SET|
                    USE
                )\b/ix',
                $search_sql
            )
        ) {

            $search_error =
                "Only SELECT statements are allowed.";


        /* =================================================
           NO MULTIPLE STATEMENTS
        ================================================= */

        } elseif (
            strpos(
                $search_sql,
                ";"
            ) !== false
        ) {

            $search_error =
                "Please enter only one SELECT statement.";


        } else {


            /*
             * IMPORTANT SECURITY:
             *
             * We do NOT allow the user's SELECT to directly
             * access records belonging to another user.
             *
             * The query is rewritten so employee records
             * are restricted to current_user_id.
             */


            /*
             * Check whether the query contains employee.
             */

            if (
                preg_match(
                    '/\bFROM\s+employee\b/i',
                    $search_sql
                )
            ) {


                /*
                 * Add current user's restriction.
                 */

                if (
                    preg_match(
                        '/\bWHERE\b/i',
                        $search_sql
                    )
                ) {

                    $search_sql =
                        preg_replace(
                            '/\bWHERE\b/i',
                            "WHERE user_id = '" .
                            mysqli_real_escape_string(
                                $conn,
                                $current_user_id
                            ) .
                            "' AND ",
                            $search_sql,
                            1
                        );

                } else {

                    $search_sql .=
                        " WHERE user_id = '" .
                        mysqli_real_escape_string(
                            $conn,
                            $current_user_id
                        ) .
                        "'";
                }


            } else {

                /*
                 * For safety, SQL Search is restricted
                 * to the employee table.
                 */

                $search_error =
                    "SQL Search can only query the employee table.";
            }


            if (
                $search_error === ""
            ) {

                $search_result =
                    mysqli_query(
                        $conn,
                        $search_sql
                    );


                if (
                    !$search_result
                ) {

                    $search_error =
                        "SQL Error: " .
                        mysqli_error(
                            $conn
                        );

                } else {

                    $search_count =
                        mysqli_num_rows(
                            $search_result
                        );


                    $search_fields =
                        mysqli_fetch_fields(
                            $search_result
                        );


                    foreach (
                        $search_fields
                        as $field
                    ) {

                        if (
                            strtolower(
                                $field->name
                            ) === "id"
                        ) {

                            $has_id_column =
                                true;

                            break;
                        }
                    }
                }
            }
        }
    }
}


/* =========================================================
   NORMAL EMPLOYEE LIST
========================================================= */

$stmt =
    $conn->prepare("
        SELECT *
        FROM employee
        WHERE user_id = ?
        ORDER BY id DESC
    ");


$result = NULL;


if ($stmt) {

    $stmt->bind_param(
        "s",
        $current_user_id
    );

    $stmt->execute();

    $result =
        $stmt->get_result();

    $stmt->close();
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
Employee Payment CRUD
</title>

<style>

* {
    box-sizing: border-box;
}

body {

    margin: 0;

    font-family:
        Arial,
        sans-serif;

    background: #f2f4f7;
}

.container {

    width: 95%;

    max-width: 1450px;

    margin: 30px auto;
}

h1 {

    text-align: center;

    color: #1d3557;

    margin-bottom: 10px;
}

h2 {

    color: #1d3557;
}


/* =========================================================
   USER BAR
========================================================= */

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

.logout-button {

    background: #6c757d;

    color: white;

    padding: 8px 14px;

    border-radius: 5px;

    text-decoration: none;

    font-size: 14px;
}

.logout-button:hover {

    opacity: 0.85;
}


/* =========================================================
   CARDS
========================================================= */

.card {

    background: white;

    padding: 25px;

    margin-bottom: 25px;

    border-radius: 10px;

    box-shadow:
        0 3px 10px
        rgba(0,0,0,0.12);
}


/* =========================================================
   MESSAGE
========================================================= */

.message {

    padding: 12px;

    margin-bottom: 20px;

    border-radius: 5px;

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


/* =========================================================
   SEARCH
========================================================= */

.search-box textarea {

    width: 100%;

    min-height: 100px;

    padding: 12px;

    border: 1px solid #999;

    border-radius: 6px;

    font-family:
        Consolas,
        monospace;

    font-size: 15px;

    resize: vertical;
}

.search-buttons {

    margin-top: 10px;

    display: flex;

    gap: 10px;

    flex-wrap: wrap;
}


button,
.btn {

    padding: 9px 16px;

    border: none;

    border-radius: 5px;

    cursor: pointer;

    text-decoration: none;

    display: inline-block;

    font-size: 14px;
}

.search-button {

    background: #6f42c1;

    color: white;
}

.clear-button {

    background: #6c757d;

    color: white;
}

.print-button {

    background: #198754;

    color: white;
}

.search-info {

    background: #e7f1ff;

    padding: 12px;

    margin-top: 15px;

    margin-bottom: 15px;

    border-radius: 5px;

    color: #084298;
}

.search-error {

    background: #f8d7da;

    color: #842029;

    padding: 12px;

    margin-top: 15px;

    margin-bottom: 15px;

    border-radius: 5px;

    font-weight: bold;
}

.search-help {

    background: #fff3cd;

    padding: 15px;

    margin-top: 15px;

    border-radius: 5px;

    line-height: 1.7;
}

.search-help code {

    background: #eee;

    padding: 3px 6px;

    border-radius: 4px;

    font-family:
        Consolas,
        monospace;
}


/* =========================================================
   FORM
========================================================= */

.form-grid {

    display: grid;

    grid-template-columns:
        repeat(
            auto-fit,
            minmax(200px, 1fr)
        );

    gap: 15px;
}

.form-group {

    display: flex;

    flex-direction: column;
}

label {

    font-weight: bold;

    margin-bottom: 6px;
}

input {

    padding: 10px;

    border: 1px solid #aaa;

    border-radius: 5px;

    font-size: 15px;
}

.save {

    background: #198754;

    color: white;

    margin-top: 20px;
}

.cancel {

    background: #6c757d;

    color: white;

    margin-top: 20px;
}

.edit {

    background: #0d6efd;

    color: white;
}

.delete {

    background: #dc3545;

    color: white;
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
}

th,
td {

    border: 1px solid #ddd;

    padding: 10px;

    text-align: center;
}

th {

    background: #1d3557;

    color: white;
}

tr:nth-child(even) {

    background: #f8f9fa;
}

.total {

    font-weight: bold;

    color: green;
}

.action-cell {

    white-space: nowrap;
}

.delete-form {

    display: inline;
}

.note {

    background: #fff3cd;

    padding: 12px;

    margin-bottom: 20px;

    border-radius: 5px;

    line-height: 1.7;
}


/* =========================================================
   PRINT
========================================================= */

.print-button-area {

    margin: 15px 0;
}


@media print {

    body * {

        visibility:
            hidden !important;
    }

    #search-print-area,
    #search-print-area * {

        visibility:
            visible !important;
    }

    #search-print-area {

        position: absolute;

        left: 0;

        top: 0;

        width: 100%;
    }

    #search-print-area table {

        width: 100%;

        border-collapse:
            collapse;
    }

    #search-print-area th,
    #search-print-area td {

        border:
            1px solid #000;

        padding: 6px;

        text-align:
            center;
    }

    #search-print-area th {

        background: #ddd !important;

        color: #000 !important;
    }

    .print-button-area {

        display:
            none !important;
    }
}

</style>

</head>


<body>


<div class="container">


<h1>
Employee Payment CRUD
</h1>


<!-- ======================================================
     LOGGED-IN USER
====================================================== -->

<div class="user-bar">

<div class="user-info">

Logged in user:

<?php

echo htmlspecialchars(
    $current_user_name,
    ENT_QUOTES,
    "UTF-8"
);

?>

&nbsp;&nbsp;

(User ID:

<?php

echo htmlspecialchars(
    $current_user_id,
    ENT_QUOTES,
    "UTF-8"
);

?>

)

</div>


<a
    href="logout.php"
    class="logout-button"
>
Logout
</a>

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
     SQL SELECT SEARCH
===================================================== -->

<div class="card">

<h2>
SQL SELECT Search
</h2>

<div class="search-box">

<form
    method="GET"
    action="index.php"
>

<textarea
    name="search"
    placeholder="Enter MySQL SELECT command here..."
><?php

echo htmlspecialchars(
    $search_sql,
    ENT_QUOTES
);

?></textarea>


<div class="search-buttons">

<button
    type="submit"
    class="btn search-button"
>
Search
</button>


<a
    href="index.php"
    class="btn clear-button"
>
Show All
</a>

</div>

</form>

</div>


<div class="search-help">

<strong>
Enter a valid SELECT statement against the employee table.
</strong>

<br>

Your search is automatically restricted to your own
employee records.

<br><br>

<strong>Examples:</strong>

<br><br>

<code>
SELECT * FROM employee;
</code>

<br><br>

<code>
SELECT * FROM employee WHERE id = 1;
</code>

<br><br>

<code>
SELECT * FROM employee WHERE Employee_name LIKE '%Ravi%';
</code>

<br><br>

<code>
SELECT * FROM employee WHERE id BETWEEN 1 AND 10;
</code>

<br><br>

<code>
SELECT COUNT(*) AS TotalEmployees FROM employee;
</code>

<br><br>

<code>
SELECT SUM(TOTAL_PAYMENT) AS TotalPayment FROM employee;
</code>

<br><br>

<code>
SELECT AVG(BASIC_PAY) AS AverageBasicPay FROM employee;
</code>

<br><br>

<code>
SELECT MAX(TOTAL_PAYMENT) AS HighestPayment FROM employee;
</code>

<br><br>

<strong>
Only SELECT statements against employee are permitted.
</strong>

</div>


<?php

if (
    $search_error !== ""
) {

?>

<div class="search-error">

<?php

echo htmlspecialchars(
    $search_error
);

?>

</div>

<?php

}


if (
    $search_sql !== "" &&
    $search_error === "" &&
    $search_result
) {

?>

<div class="search-info">

<strong>
Search completed.
</strong>

&nbsp;&nbsp;

Rows returned:

<strong>

<?php

echo $search_count;

?>

</strong>

</div>


<div class="print-button-area">

<button
    type="button"
    class="btn print-button"
    onclick="printSearchResult()"
>
Print Search Result
</button>

</div>


<div
    id="search-print-area"
    class="table-container"
>

<table>

<thead>

<tr>

<?php

foreach (
    $search_fields
    as $field
) {

?>

<th>

<?php

echo htmlspecialchars(
    $field->name
);

?>

</th>

<?php

}


if (
    $has_id_column
) {

?>

<th>
Action
</th>

<?php

}

?>

</tr>

</thead>


<tbody>

<?php

if (
    $search_count > 0
) {

    while (
        $row =
        mysqli_fetch_assoc(
            $search_result
        )
    ) {

?>

<tr>

<?php

foreach (
    $search_fields
    as $field
) {

    $column =
        $field->name;

    $value =
        isset(
            $row[$column]
        )
        ? $row[$column]
        : "";

?>

<td>

<?php

if (
    is_numeric($value) &&
    preg_match(
        '/(PAY|AMOUNT|BASIC|DEDUCTION|ALLOWANCE|PERCENT)/i',
        $column
    )
) {

    echo number_format(
        (float)$value,
        2
    );

} else {

    echo htmlspecialchars(
        (string)$value
    );
}

?>

</td>

<?php

}

?>


<?php

if (
    $has_id_column
) {

?>

<td class="action-cell">

<a
    href="index.php?edit=<?php
        echo intval(
            $row["id"]
        );
    ?>"
    class="btn edit"
>
Edit
</a>


<form
    method="POST"
    action="index.php"
    class="delete-form"
>

<input
    type="hidden"
    name="delete_id"
    value="<?php
        echo intval(
            $row["id"]
        );
    ?>"
>


<button
    type="submit"
    class="btn delete"
    onclick="
        return confirm(
            'Are you sure you want to delete this employee record?'
        );
    "
>
Delete
</button>

</form>

</td>

<?php

}

?>

</tr>

<?php

    }

} else {

?>

<tr>

<td
    colspan="<?php

        echo count(
            $search_fields
        ) +
        (
            $has_id_column
            ? 1
            : 0
        );

    ?>"
>
No records found.
</td>

</tr>

<?php

}

?>

</tbody>

</table>

</div>

<?php

}

?>

</div>


<!-- =====================================================
     ADD / UPDATE EMPLOYEE
===================================================== -->

<div class="card">

<h2>

<?php

echo $edit
    ? "Edit Employee"
    : "Add Employee";

?>

</h2>


<div class="note">

<strong>
Calculation:
</strong>

<br><br>

DA Amount =
Basic Pay × DA % / 100

<br>

HRA Amount =
Basic Pay × HRA % / 100

<br><br>

<strong>
Total Payment =
Basic Pay + DA Amount + HRA Amount
- PF Deduction + Other Allowance
</strong>

</div>


<form
    method="POST"
    action="index.php"
>

<input
    type="hidden"
    name="id"
    value="<?php

        echo $edit
            ? intval(
                $edit["id"]
            )
            : "";

    ?>"
>


<div class="form-grid">


<div class="form-group">

<label>
Employee Name
</label>

<input
    type="text"
    name="employee_name"
    maxlength="100"
    required
    value="<?php

        echo $edit
            ? htmlspecialchars(
                $edit["Employee_name"],
                ENT_QUOTES
            )
            : "";

    ?>"
>

</div>


<div class="form-group">

<label>
Basic Pay
</label>

<input
    type="number"
    step="0.01"
    min="0"
    name="basic_pay"
    required
    value="<?php

        echo $edit
            ? $edit["BASIC_PAY"]
            : "";

    ?>"
>

</div>


<div class="form-group">

<label>
DA %
</label>

<input
    type="number"
    step="0.01"
    min="0"
    name="da_percent"
    required
    value="<?php

        echo $edit
            ? $edit["DA_PERCENT"]
            : "";

    ?>"
>

</div>


<div class="form-group">

<label>
HRA %
</label>

<input
    type="number"
    step="0.01"
    min="0"
    name="hra_percent"
    required
    value="<?php

        echo $edit
            ? $edit["HRA_PERCENT"]
            : "";

    ?>"
>

</div>


<div class="form-group">

<label>
PF Deduction
</label>

<input
    type="number"
    step="0.01"
    min="0"
    name="pf_deduction"
    required
    value="<?php

        echo $edit
            ? $edit["PF_DEDUCTION"]
            : "0";

    ?>"
>

</div>


<div class="form-group">

<label>
Any Other Allowance
</label>

<input
    type="number"
    step="0.01"
    min="0"
    name="other_allowance"
    required
    value="<?php

        echo $edit
            ? $edit["ANY_OTHER_ALLOWANCE"]
            : "0";

    ?>"
>

</div>

</div>


<?php

if (
    $edit
) {

?>

<button
    type="submit"
    name="save"
    class="btn save"
>
Update Employee
</button>


<a
    href="index.php"
    class="btn cancel"
>
Cancel
</a>

<?php

} else {

?>

<button
    type="submit"
    name="save"
    class="btn save"
>
Add Employee
</button>

<?php

}

?>

</form>

</div>


<!-- =====================================================
     ALL EMPLOYEE RECORDS
===================================================== -->

<div class="card">

<h2>
My Employee Records
</h2>

<div class="table-container">

<table>

<thead>

<tr>

<th>ID</th>

<th>Employee Name</th>

<th>Basic Pay</th>

<th>DA %</th>

<th>DA Amount</th>

<th>HRA %</th>

<th>HRA Amount</th>

<th>PF Deduction</th>

<th>Other Allowance</th>

<th>Total Payment</th>

<th>Action</th>

</tr>

</thead>


<tbody>

<?php

if (
    $result &&
    $result->num_rows > 0
) {

    while (
        $row =
        $result->fetch_assoc()
    ) {

?>

<tr>

<td>

<?php

echo intval(
    $row["id"]
);

?>

</td>


<td>

<?php

echo htmlspecialchars(
    $row["Employee_name"]
);

?>

</td>


<td>

<?php

echo number_format(
    $row["BASIC_PAY"],
    2
);

?>

</td>


<td>

<?php

echo number_format(
    $row["DA_PERCENT"],
    2
);

?>

%

</td>


<td>

<?php

echo number_format(
    $row["DA_AMOUNT"],
    2
);

?>

</td>


<td>

<?php

echo number_format(
    $row["HRA_PERCENT"],
    2
);

?>

%

</td>


<td>

<?php

echo number_format(
    $row["HRA_AMOUNT"],
    2
);

?>

</td>


<td>

<?php

echo number_format(
    $row["PF_DEDUCTION"],
    2
);

?>

</td>


<td>

<?php

echo number_format(
    $row["ANY_OTHER_ALLOWANCE"],
    2
);

?>

</td>


<td class="total">

<?php

echo number_format(
    $row["TOTAL_PAYMENT"],
    2
);

?>

</td>


<td class="action-cell">

<a
    href="index.php?edit=<?php
        echo intval(
            $row["id"]
        );
    ?>"
    class="btn edit"
>
Edit
</a>


<form
    method="POST"
    action="index.php"
    class="delete-form"
>

<input
    type="hidden"
    name="delete_id"
    value="<?php

        echo intval(
            $row["id"]
        );

    ?>"
>


<button
    type="submit"
    class="btn delete"
    onclick="
        return confirm(
            'Are you sure you want to delete this employee record?'
        );
    "
>
Delete
</button>

</form>

</td>

</tr>

<?php

    }

} else {

?>

<tr>

<td colspan="11">

No employee records found for this user.

</td>

</tr>

<?php

}

?>

</tbody>

</table>

</div>

</div>


</div>


<script>

/*
=========================================================
 PRINT SEARCH RESULT
=========================================================
*/

function printSearchResult()
{
    window.print();
}


/*
=========================================================
 AUTOMATIC STOP-TIME LOGOUT
=========================================================

This checks time_check.php every 5 seconds.

If the permitted stop time has been reached,
time_check.php destroys the login session and
returns "LOGOUT".

The browser then returns to login.php.

=========================================================
*/

(function () {

    function checkUserTime() {

        fetch(
            "time_check.php",
            {
                method: "GET",
                cache: "no-store",
                credentials: "same-origin"
            }
        )

        .then(
            function (response) {

                /*
                 * Session is no longer valid.
                 */

                if (
                    response.status === 401 ||
                    response.status === 500
                ) {

                    window.location.href =
                        "login.php";

                    return null;
                }


                return response.text();

            }
        )

        .then(
            function (result) {

                if (
                    result &&
                    result.trim() === "LOGOUT"
                ) {

                    window.location.href =
                        "login.php";
                }

            }
        )

        .catch(
            function (error) {

                /*
                 * Do NOT logout because of a
                 * temporary network problem.
                 *
                 * The next 5-second check will
                 * try again.
                 */

                console.log(
                    "Time check error:",
                    error
                );

            }
        );

    }


    /*
     * Check immediately when the page loads.
     */

    checkUserTime();


    /*
     * Continue checking every 5 seconds.
     */

    setInterval(
        checkUserTime,
        5000
    );

})();

</script>


</body>

</html>

<?php

mysqli_close(
    $conn
);

?>
