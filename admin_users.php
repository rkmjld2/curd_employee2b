<?php
/*
============================================================
 CURD-EMPLOYEE2
 ADMINISTRATOR - USER MANAGEMENT
============================================================

Purpose:
    Manage app_users table.

Database:
    TiDB Cloud

Table:
    app_users

Columns:
    id
    user_id
    user_name
    password_hash
    application
    active
    start_time
    stop_time
    last_login
    created_at
    updated_at

Administrator credentials:
    ADMIN_USER
    ADMIN_PASSWORD

These must be stored in Render Environment Variables.

Timezone:
    Asia/Kolkata

============================================================
*/

date_default_timezone_set("Asia/Kolkata");

session_start();

require_once __DIR__ . "/db.php";


/* =========================================================
   ERROR REPORTING
   ========================================================= */

error_reporting(E_ALL);
ini_set("display_errors", "1");


/* =========================================================
   ADMIN CREDENTIALS FROM ENVIRONMENT
   ========================================================= */

$admin_user =
    getenv("ADMIN_USER") ?: "";

$admin_password =
    getenv("ADMIN_PASSWORD") ?: "";


/* =========================================================
   CSRF TOKEN
   ========================================================= */

if (!isset($_SESSION["admin_csrf"])) {

    $_SESSION["admin_csrf"] =
        bin2hex(random_bytes(32));
}

$csrf_token =
    $_SESSION["admin_csrf"];


/* =========================================================
   ADMIN LOGIN
   ========================================================= */

$login_error = "";

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["admin_login"])
) {

    $entered_user =
        trim($_POST["admin_user"] ?? "");

    $entered_password =
        $_POST["admin_password"] ?? "";

    if (
        $admin_user === "" ||
        $admin_password === ""
    ) {

        $login_error =
            "Administrator credentials are not configured.";

    } elseif (
        hash_equals(
            $admin_user,
            $entered_user
        ) &&
        hash_equals(
            $admin_password,
            $entered_password
        )
    ) {

        session_regenerate_id(true);

        $_SESSION["is_admin"] = true;

        $_SESSION["admin_csrf"] =
            bin2hex(random_bytes(32));

        header(
            "Location: admin_users.php"
        );

        exit;

    } else {

        $login_error =
            "Invalid administrator username or password.";
    }
}


/* =========================================================
   ADMIN LOGOUT
   ========================================================= */

if (
    isset($_GET["admin_logout"])
) {

    unset($_SESSION["is_admin"]);

    unset($_SESSION["admin_csrf"]);

    header(
        "Location: admin_users.php"
    );

    exit;
}


/* =========================================================
   CHECK ADMIN LOGIN
   ========================================================= */

$is_admin =
    isset($_SESSION["is_admin"]) &&
    $_SESSION["is_admin"] === true;


/* =========================================================
   MESSAGE
   ========================================================= */

$message = "";

$error = "";


/* =========================================================
   PROCESS ADMIN ACTIONS
========================================================= */

if ($is_admin) {

    /* =====================================================
       ADD / UPDATE USER
    ===================================================== */

    if (
        $_SERVER["REQUEST_METHOD"] === "POST" &&
        isset($_POST["save_user"])
    ) {

        /* -------------------------------------------------
           CSRF CHECK
        ------------------------------------------------- */

        if (
            !isset($_POST["csrf_token"]) ||
            !hash_equals(
                $_SESSION["admin_csrf"],
                $_POST["csrf_token"]
            )
        ) {

            $error =
                "Invalid security token.";

        } else {

            $id =
                intval(
                    $_POST["id"] ?? 0
                );

            $user_id =
                trim(
                    $_POST["user_id"] ?? ""
                );

            $user_name =
                trim(
                    $_POST["user_name"] ?? ""
                );

            $application =
                trim(
                    $_POST["application"] ?? ""
                );

            $password =
                $_POST["password"] ?? "";

            $active =
                isset($_POST["active"])
                    ? 1
                    : 0;

            $start_time =
                trim(
                    $_POST["start_time"] ?? ""
                );

            $stop_time =
                trim(
                    $_POST["stop_time"] ?? ""
                );


            /* ---------------------------------------------
               BASIC VALIDATION
            --------------------------------------------- */

            if ($user_id === "") {

                $error =
                    "User ID is required.";

            } elseif (
                strlen($user_id) > 50
            ) {

                $error =
                    "User ID cannot exceed 50 characters.";

            } elseif ($user_name === "") {

                $error =
                    "User Name is required.";

            } elseif (
                strlen($user_name) > 100
            ) {

                $error =
                    "User Name cannot exceed 100 characters.";

            } elseif (
                strlen($application) > 255
            ) {

                $error =
                    "Application cannot exceed 255 characters.";

            } elseif (
                $id === 0 &&
                $password === ""
            ) {

                $error =
                    "Password is required for a new user.";

            } elseif (
                $password !== "" &&
                strlen($password) < 6
            ) {

                $error =
                    "Password must contain at least 6 characters.";

            }


            /* ---------------------------------------------
               CONVERT DATETIME-LOCAL TO MYSQL DATETIME
            --------------------------------------------- */

            $start_db = null;

            $stop_db = null;


            if (
                $error === "" &&
                $start_time !== ""
            ) {

                $start_obj =
                    DateTime::createFromFormat(
                        "Y-m-d\TH:i",
                        $start_time,
                        new DateTimeZone(
                            "Asia/Kolkata"
                        )
                    );

                if (!$start_obj) {

                    $error =
                        "Invalid Start Time.";

                } else {

                    $start_db =
                        $start_obj->format(
                            "Y-m-d H:i:s"
                        );
                }
            }


            if (
                $error === "" &&
                $stop_time !== ""
            ) {

                $stop_obj =
                    DateTime::createFromFormat(
                        "Y-m-d\TH:i",
                        $stop_time,
                        new DateTimeZone(
                            "Asia/Kolkata"
                        )
                    );

                if (!$stop_obj) {

                    $error =
                        "Invalid Stop Time.";

                } else {

                    $stop_db =
                        $stop_obj->format(
                            "Y-m-d H:i:s"
                        );
                }
            }


            /* ---------------------------------------------
               CHECK START / STOP ORDER
            --------------------------------------------- */

            if (
                $error === "" &&
                $start_db !== null &&
                $stop_db !== null
            ) {

                if (
                    strtotime($stop_db) <=
                    strtotime($start_db)
                ) {

                    $error =
                        "Stop Time must be later than Start Time.";
                }
            }


            /* ---------------------------------------------
               SAVE USER
            --------------------------------------------- */

            if ($error === "") {

                if ($id > 0) {

                    /* =====================================
                       UPDATE EXISTING USER
                    ===================================== */

                    if ($password !== "") {

                        $password_hash =
                            password_hash(
                                $password,
                                PASSWORD_DEFAULT
                            );

                        $stmt =
                            $conn->prepare("
                                UPDATE app_users
                                SET
                                    user_id = ?,
                                    user_name = ?,
                                    password_hash = ?,
                                    application = ?,
                                    active = ?,
                                    start_time = ?,
                                    stop_time = ?
                                WHERE id = ?
                            ");

                        if (!$stmt) {

                            $error =
                                "Database preparation failed.";

                        } else {

                            $stmt->bind_param(
                                "ssssissi",
                                $user_id,
                                $user_name,
                                $password_hash,
                                $application,
                                $active,
                                $start_db,
                                $stop_db,
                                $id
                            );
                        }

                    } else {

                        /*
                         * Password left blank:
                         * keep existing password.
                         */

                        $stmt =
                            $conn->prepare("
                                UPDATE app_users
                                SET
                                    user_id = ?,
                                    user_name = ?,
                                    application = ?,
                                    active = ?,
                                    start_time = ?,
                                    stop_time = ?
                                WHERE id = ?
                            ");

                        if (!$stmt) {

                            $error =
                                "Database preparation failed.";

                        } else {

                            $stmt->bind_param(
                                "sssissi",
                                $user_id,
                                $user_name,
                                $application,
                                $active,
                                $start_db,
                                $stop_db,
                                $id
                            );
                        }
                    }


                    if (
                        $error === "" &&
                        !$stmt->execute()
                    ) {

                        if (
                            $stmt->errno === 1062
                        ) {

                            $error =
                                "That User ID already exists.";

                        } else {

                            $error =
                                "Unable to update user: " .
                                $stmt->error;
                        }
                    }


                    if (
                        isset($stmt) &&
                        $stmt
                    ) {

                        $stmt->close();
                    }


                    if ($error === "") {

                        $message =
                            "User updated successfully.";
                    }


                } else {

                    /* =====================================
                       ADD NEW USER
                    ===================================== */

                    $password_hash =
                        password_hash(
                            $password,
                            PASSWORD_DEFAULT
                        );

                    $stmt =
                        $conn->prepare("
                            INSERT INTO app_users
                            (
                                user_id,
                                user_name,
                                password_hash,
                                application,
                                active,
                                start_time,
                                stop_time
                            )
                            VALUES
                            (
                                ?,
                                ?,
                                ?,
                                ?,
                                ?,
                                ?,
                                ?
                            )
                        ");

                    if (!$stmt) {

                        $error =
                            "Database preparation failed.";

                    } else {

                        $stmt->bind_param(
                            "ssssiss",
                            $user_id,
                            $user_name,
                            $password_hash,
                            $application,
                            $active,
                            $start_db,
                            $stop_db
                        );


                        if (!$stmt->execute()) {

                            if (
                                $stmt->errno === 1062
                            ) {

                                $error =
                                    "That User ID already exists.";

                            } else {

                                $error =
                                    "Unable to create user: " .
                                    $stmt->error;
                            }

                        } else {

                            $message =
                                "New user created successfully.";
                        }

                        $stmt->close();
                    }
                }
            }
        }
    }


    /* =====================================================
       DELETE USER
    ===================================================== */

    if (
        $_SERVER["REQUEST_METHOD"] === "POST" &&
        isset($_POST["delete_user"])
    ) {

        if (
            !isset($_POST["csrf_token"]) ||
            !hash_equals(
                $_SESSION["admin_csrf"],
                $_POST["csrf_token"]
            )
        ) {

            $error =
                "Invalid security token.";

        } else {

            $id =
                intval(
                    $_POST["delete_id"] ?? 0
                );


            if ($id <= 0) {

                $error =
                    "Invalid user ID.";

            } else {

                $stmt =
                    $conn->prepare("
                        DELETE FROM app_users
                        WHERE id = ?
                    ");

                if (!$stmt) {

                    $error =
                        "Database preparation failed.";

                } else {

                    $stmt->bind_param(
                        "i",
                        $id
                    );

                    if (
                        !$stmt->execute()
                    ) {

                        $error =
                            "Unable to delete user: " .
                            $stmt->error;

                    } else {

                        $message =
                            "User deleted successfully.";
                    }

                    $stmt->close();
                }
            }
        }
    }
}


/* =========================================================
   LOAD USER FOR EDIT
========================================================= */

$edit_user = null;

if (
    $is_admin &&
    isset($_GET["edit"])
) {

    $edit_id =
        intval(
            $_GET["edit"]
        );

    if ($edit_id > 0) {

        $stmt =
            $conn->prepare("
                SELECT
                    id,
                    user_id,
                    user_name,
                    application,
                    active,
                    start_time,
                    stop_time,
                    last_login,
                    created_at,
                    updated_at
                FROM app_users
                WHERE id = ?
                LIMIT 1
            ");

        if ($stmt) {

            $stmt->bind_param(
                "i",
                $edit_id
            );

            $stmt->execute();

            $result =
                $stmt->get_result();

            if (
                $result &&
                $result->num_rows > 0
            ) {

                $edit_user =
                    $result->fetch_assoc();
            }

            $stmt->close();
        }
    }
}


/* =========================================================
   LOAD ALL USERS
========================================================= */

$users = null;

if ($is_admin) {

    $users =
        $conn->query("
            SELECT
                id,
                user_id,
                user_name,
                application,
                active,
                start_time,
                stop_time,
                last_login,
                created_at,
                updated_at
            FROM app_users
            ORDER BY id ASC
        ");
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
CURD-EMPLOYEE2 - Administrator User Control
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

    max-width: 1250px;

    margin: 0 auto;
}

.card {

    background: white;

    padding: 25px;

    margin-bottom: 25px;

    border-radius: 12px;

    box-shadow:
        0 4px 15px
        rgba(0,0,0,0.12);
}

.login-box {

    width: 100%;

    max-width: 430px;

    margin: 80px auto;

    text-align: center;
}

h1 {

    color: #1d3557;

    margin-top: 0;
}

h2 {

    color: #1d3557;
}

.subtitle {

    color: #666;

    margin-bottom: 25px;
}

.form-group {

    margin-bottom: 15px;

    text-align: left;
}

label {

    display: block;

    font-weight: bold;

    margin-bottom: 6px;
}

input[type="text"],
input[type="password"],
input[type="datetime-local"] {

    width: 100%;

    padding: 11px;

    border:
        1px solid #aaa;

    border-radius: 6px;

    font-size: 15px;
}

.checkbox-row {

    display: flex;

    align-items: center;

    gap: 8px;

    margin: 15px 0;
}

.checkbox-row label {

    margin: 0;
}

button,
.btn {

    display: inline-block;

    padding: 10px 16px;

    border: none;

    border-radius: 6px;

    text-decoration: none;

    font-size: 14px;

    cursor: pointer;

    margin: 3px;
}

.primary {

    background: #0d6efd;

    color: white;
}

.success {

    background: #198754;

    color: white;
}

.warning {

    background: #ffc107;

    color: #222;
}

.danger {

    background: #dc3545;

    color: white;
}

.secondary {

    background: #6c757d;

    color: white;
}

.logout {

    float: right;

    background: #dc3545;

    color: white;
}

.message {

    background: #d1e7dd;

    color: #0f5132;

    padding: 12px;

    border-radius: 6px;

    margin-bottom: 20px;

    font-weight: bold;
}

.error {

    background: #f8d7da;

    color: #842029;

    padding: 12px;

    border-radius: 6px;

    margin-bottom: 20px;

    font-weight: bold;
}

.table-container {

    overflow-x: auto;
}

table {

    width: 100%;

    border-collapse: collapse;

    min-width: 1050px;
}

th,
td {

    border:
        1px solid #ddd;

    padding: 9px;

    text-align: left;

    vertical-align: middle;
}

th {

    background: #1d3557;

    color: white;
}

tr:nth-child(even) {

    background: #f8f9fa;
}

.active {

    color: #198754;

    font-weight: bold;
}

.inactive {

    color: #dc3545;

    font-weight: bold;
}

.small {

    color: #666;

    font-size: 13px;

    margin-top: 5px;
}

.topbar {

    overflow: hidden;

    margin-bottom: 20px;
}

.note {

    background: #fff3cd;

    color: #664d03;

    padding: 12px;

    border-radius: 6px;

    margin-bottom: 20px;
}

</style>

</head>

<body>


<?php if (!$is_admin) { ?>

<div class="card login-box">

<h1>
CURD-EMPLOYEE2
</h1>

<h2>
Administrator Login
</h2>

<div class="subtitle">
User Management
</div>


<?php if ($login_error !== "") { ?>

<div class="error">

<?php
echo htmlspecialchars(
    $login_error,
    ENT_QUOTES,
    "UTF-8"
);
?>

</div>

<?php } ?>


<form
    method="POST"
    action="admin_users.php"
>

<div class="form-group">

<label>
Administrator User
</label>

<input
    type="text"
    name="admin_user"
    required
    autofocus
>

</div>


<div class="form-group">

<label>
Administrator Password
</label>

<input
    type="password"
    name="admin_password"
    required
>

</div>


<button
    type="submit"
    name="admin_login"
    class="primary"
>
ADMIN LOGIN
</button>

</form>

<div class="small">
Administrator access only.
</div>

</div>

<?php } else { ?>


<div class="container">


<div class="topbar">

<a
    href="index.php"
    class="btn secondary"
>
Back to Employee Payment
</a>

<a
    href="admin_users.php?admin_logout=1"
    class="btn logout"
>
Administrator Logout
</a>

</div>


<div class="card">

<h1>
CURD-EMPLOYEE2
</h1>

<h2>
Administrator - User Control
</h2>

<div class="note">

You can create and manage users, passwords, application,
active status, start time and stop time.

</div>


<?php if ($message !== "") { ?>

<div class="message">

<?php
echo htmlspecialchars(
    $message,
    ENT_QUOTES,
    "UTF-8"
);
?>

</div>

<?php } ?>


<?php if ($error !== "") { ?>

<div class="error">

<?php
echo htmlspecialchars(
    $error,
    ENT_QUOTES,
    "UTF-8"
);
?>

</div>

<?php } ?>


<form
    method="POST"
    action="admin_users.php"
>

<input
    type="hidden"
    name="csrf_token"
    value="<?php
        echo htmlspecialchars(
            $csrf_token,
            ENT_QUOTES,
            "UTF-8"
        );
    ?>"
>

<?php if ($edit_user) { ?>

<input
    type="hidden"
    name="id"
    value="<?php
        echo intval(
            $edit_user["id"]
        );
    ?>"
>

<?php } ?>


<div class="form-group">

<label>
User ID
</label>

<input
    type="text"
    name="user_id"
    maxlength="50"
    required
    value="<?php
        echo htmlspecialchars(
            $edit_user["user_id"] ?? "",
            ENT_QUOTES,
            "UTF-8"
        );
    ?>"
>

</div>


<div class="form-group">

<label>
User Name
</label>

<input
    type="text"
    name="user_name"
    maxlength="100"
    required
    value="<?php
        echo htmlspecialchars(
            $edit_user["user_name"] ?? "",
            ENT_QUOTES,
            "UTF-8"
        );
    ?>"
>

</div>


<div class="form-group">

<label>
Application
</label>

<input
    type="text"
    name="application"
    maxlength="255"
    placeholder="/curd_employee2/index.php"
    value="<?php
        echo htmlspecialchars(
            $edit_user["application"] ?? "",
            ENT_QUOTES,
            "UTF-8"
        );
    ?>"
>

<div class="small">
Enter the application path or URL to open after login.
Example: /curd_employee2/index.php
</div>

</div>


<div class="form-group">

<label>
Password

<?php if ($edit_user) { ?>

<span class="small">
Leave blank to keep the existing password.
</span>

<?php } ?>

</label>

<input
    type="password"
    name="password"
    minlength="6"
    <?php
    if (!$edit_user) {
        echo "required";
    }
    ?>
>

</div>


<div class="checkbox-row">

<input
    type="checkbox"
    id="active"
    name="active"
    value="1"
    <?php

    if (
        !$edit_user ||
        (int)$edit_user["active"] === 1
    ) {

        echo "checked";
    }

    ?>
>

<label for="active">
Active
</label>

</div>


<div class="form-group">

<label>
Start Time
</label>

<input
    type="datetime-local"
    name="start_time"
    value="<?php

    if (
        $edit_user &&
        !empty(
            $edit_user["start_time"]
        )
    ) {

        echo date(
            "Y-m-d\TH:i",
            strtotime(
                $edit_user["start_time"]
            )
        );
    }

    ?>"
>

<div class="small">
Leave blank for no start-time restriction.
</div>

</div>


<div class="form-group">

<label>
Stop Time
</label>

<input
    type="datetime-local"
    name="stop_time"
    value="<?php

    if (
        $edit_user &&
        !empty(
            $edit_user["stop_time"]
        )
    ) {

        echo date(
            "Y-m-d\TH:i",
            strtotime(
                $edit_user["stop_time"]
            )
        );
    }

    ?>"
>

<div class="small">
Leave blank for no stop-time restriction.
</div>

</div>


<button
    type="submit"
    name="save_user"
    class="success"
>

<?php
echo $edit_user
    ? "Update User"
    : "Create User";
?>

</button>


<?php if ($edit_user) { ?>

<a
    href="admin_users.php"
    class="btn secondary"
>
Cancel Edit
</a>

<?php } ?>


</form>

</div>


<div class="card">

<h2>
All Application Users
</h2>


<div class="table-container">

<table>

<thead>

<tr>

<th>ID</th>

<th>User ID</th>

<th>User Name</th>

<th>Application</th>

<th>Status</th>

<th>Start Time</th>

<th>Stop Time</th>

<th>Last Login</th>

<th>Created</th>

<th>Updated</th>

<th>Action</th>

</tr>

</thead>

<tbody>


<?php

if (
    $users &&
    $users->num_rows > 0
) {

    while (
        $row =
        $users->fetch_assoc()
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
    $row["user_id"],
    ENT_QUOTES,
    "UTF-8"
);
?>

</td>


<td>

<?php
echo htmlspecialchars(
    $row["user_name"],
    ENT_QUOTES,
    "UTF-8"
);
?>

</td>


<td>

<?php
echo htmlspecialchars(
    $row["application"] ?? "",
    ENT_QUOTES,
    "UTF-8"
);
?>

</td>


<td>

<?php

if (
    (int)$row["active"] === 1
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


<td>

<?php

echo $row["start_time"]
    ? htmlspecialchars(
        $row["start_time"],
        ENT_QUOTES,
        "UTF-8"
    )
    : "-";

?>

</td>


<td>

<?php

echo $row["stop_time"]
    ? htmlspecialchars(
        $row["stop_time"],
        ENT_QUOTES,
        "UTF-8"
    )
    : "-";

?>

</td>


<td>

<?php

echo $row["last_login"]
    ? htmlspecialchars(
        $row["last_login"],
        ENT_QUOTES,
        "UTF-8"
    )
    : "Never";

?>

</td>


<td>

<?php

echo htmlspecialchars(
    $row["created_at"],
    ENT_QUOTES,
    "UTF-8"
);

?>

</td>


<td>

<?php

echo htmlspecialchars(
    $row["updated_at"],
    ENT_QUOTES,
    "UTF-8"
);

?>

</td>


<td>

<a
    href="admin_users.php?edit=<?php
        echo intval(
            $row["id"]
        );
    ?>"
    class="btn warning"
>
Edit
</a>


<form
    method="POST"
    action="admin_users.php"
    style="display:inline;"
    onsubmit="
        return confirm(
            'Are you sure you want to delete this user?'
        );
    "
>

<input
    type="hidden"
    name="csrf_token"
    value="<?php
        echo htmlspecialchars(
            $csrf_token,
            ENT_QUOTES,
            "UTF-8"
        );
    ?>"
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
    name="delete_user"
    class="danger"
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
No users found.
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

<?php } ?>


</body>

</html>

<?php

mysqli_close($conn);

?>