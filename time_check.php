<?php
/*
============================================================
 CURD-EMPLOYEE2
 AUTOMATIC TIME CONTROL CHECK
============================================================
*/

date_default_timezone_set("Asia/Kolkata");

session_start();

require_once __DIR__ . "/db.php";


/* =========================================================
   USER MUST BE LOGGED IN
========================================================= */

if (
    !isset($_SESSION["app_user_id"]) ||
    $_SESSION["app_user_id"] === ""
) {

    http_response_code(401);

    echo "LOGOUT";

    exit;
}


$current_user_id =
    $_SESSION["app_user_id"];


/* =========================================================
   GET CURRENT USER
========================================================= */

$stmt = $conn->prepare("
    SELECT
        active,
        start_time,
        stop_time
    FROM app_users
    WHERE user_id = ?
    LIMIT 1
");


if (!$stmt) {

    http_response_code(500);

    echo "LOGOUT";

    exit;
}


$stmt->bind_param(
    "s",
    $current_user_id
);


if (!$stmt->execute()) {

    $stmt->close();

    http_response_code(500);

    echo "LOGOUT";

    exit;
}


$result =
    $stmt->get_result();


if (
    !$result ||
    $result->num_rows === 0
) {

    $stmt->close();

    http_response_code(401);

    echo "LOGOUT";

    exit;
}


$user =
    $result->fetch_assoc();


$stmt->close();


/* =========================================================
   CURRENT INDIA TIME
========================================================= */

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

    echo "LOGOUT";

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

        echo "LOGOUT";

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


    if (
        $now >= $stop
    ) {

        /*
         * Destroy the session immediately.
         */

        $_SESSION = [];

        if (
            ini_get("session.use_cookies")
        ) {

            $params =
                session_get_cookie_params();

            setcookie(
                session_name(),
                "",
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        session_destroy();

        echo "LOGOUT";

        exit;
    }
}


/* =========================================================
   USER IS STILL ALLOWED
========================================================= */

echo "OK";

exit;