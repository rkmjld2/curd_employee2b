<?php

/*
===========================================================
        CURD_EMPLOYEE2
        COMMERCIAL LICENSE GUARD
===========================================================

Purpose:
    Protect the application using the remote
    commercial license server.

Features:
    - Remote license verification
    - Automatic retry on temporary connection/HTTP errors
    - Maximum 3 attempts
    - Supports JSON response from license server

IMPORTANT:
    This file does NOT connect to the license database.

    Employee database:
        db.php

    License database:
        Remote license server

===========================================================
*/


/* =========================================================
   LOAD CONFIGURATION
========================================================= */

require_once __DIR__ . "/config.php";


/* =========================================================
   CUSTOMER LICENSE USER ID
========================================================= */

if (
    !isset($LICENSE_USER_ID) ||
    trim($LICENSE_USER_ID) === ""
) {

    http_response_code(500);

    die("License USER_ID is not configured.");
}


$user_id =
    trim($LICENSE_USER_ID);


/* =========================================================
   LICENSE SERVER URL
========================================================= */

if (
    !isset($LICENSE_SERVER_URL) ||
    trim($LICENSE_SERVER_URL) === ""
) {

    http_response_code(500);

    die("License server URL is not configured.");
}


$license_url =
    trim($LICENSE_SERVER_URL);


/* =========================================================
   LICENSE CHECK FUNCTION
========================================================= */

function guard_check_license(
    $user_id,
    $license_url,
    $license_timeout = 20
) {

    /*
     * Maximum number of attempts.
     *
     * 1 = first request
     * 2 = first retry
     * 3 = second retry
     */

    $max_attempts = 3;


    /*
     * Keep the last error so that if all
     * attempts fail we can display useful
     * diagnostic information.
     */

    $last_error =
        "Unknown license server error.";


    /*
     * -----------------------------------------------------
     * RETRY LOOP
     * -----------------------------------------------------
     */

    for (
        $attempt = 1;
        $attempt <= $max_attempts;
        $attempt++
    ) {


        /* =================================================
           POST DATA
        ================================================= */

        $post_data =
            http_build_query(
                [
                    "user_id" => $user_id
                ]
            );


        /* =================================================
           CURL INITIALIZATION
        ================================================= */

        $ch =
            curl_init();


        if ($ch === false) {

            $last_error =
                "Unable to initialize license connection.";

        } else {


            /* =================================================
               CURL SETTINGS
            ================================================= */

            curl_setopt_array(
                $ch,
                [

                    CURLOPT_URL =>
                        $license_url,

                    CURLOPT_POST =>
                        true,

                    CURLOPT_POSTFIELDS =>
                        $post_data,

                    CURLOPT_RETURNTRANSFER =>
                        true,

                    /*
                     * Connection timeout.
                     */

                    CURLOPT_CONNECTTIMEOUT =>
                        10,

                    /*
                     * Maximum request time.
                     */

                    CURLOPT_TIMEOUT =>
                        (int)$license_timeout,

                    CURLOPT_FOLLOWLOCATION =>
                        true,

                    CURLOPT_MAXREDIRS =>
                        5,

                    /*
                     * Request headers.
                     */

                    CURLOPT_HTTPHEADER =>
                        [
                            "Content-Type: application/x-www-form-urlencoded",
                            "Accept: application/json",
                            "User-Agent: CURD-EMPLOYEE2-License-Client/1.0"
                        ],

                    /*
                     * HTTPS certificate verification.
                     */

                    CURLOPT_SSL_VERIFYPEER =>
                        true,

                    CURLOPT_SSL_VERIFYHOST =>
                        2
                ]
            );


            /* =================================================
               EXECUTE REQUEST
            ================================================= */

            $response =
                curl_exec($ch);


            /* =================================================
               CURL ERROR
            ================================================= */

            if ($response === false) {

                $error =
                    curl_error($ch);

                $errno =
                    curl_errno($ch);


                $last_error =
                    "cURL error " .
                    $errno .
                    ": " .
                    $error;


                curl_close($ch);

            } else {


                /* =================================================
                   HTTP INFORMATION
                ================================================= */

                $http_code =
                    curl_getinfo(
                        $ch,
                        CURLINFO_HTTP_CODE
                    );


                $content_type =
                    curl_getinfo(
                        $ch,
                        CURLINFO_CONTENT_TYPE
                    );


                curl_close($ch);


                /* =================================================
                   SUCCESSFUL HTTP RESPONSE
                ================================================= */

                if (
                    $http_code >= 200 &&
                    $http_code < 300
                ) {


                    /* =============================================
                       CLEAN RESPONSE
                    ============================================= */

                    $response =
                        trim($response);


                    /*
                     * Remove UTF-8 BOM.
                     */

                    $response =
                        preg_replace(
                            '/^\xEF\xBB\xBF/',
                            '',
                            $response
                        );


                    /*
                     * Remove accidental Markdown fences.
                     */

                    $response =
                        preg_replace(
                            '/^```(?:json|php)?\s*/i',
                            '',
                            $response
                        );


                    $response =
                        preg_replace(
                            '/\s*```\s*$/',
                            '',
                            $response
                        );


                    $response =
                        trim($response);


                    /* =============================================
                       JSON DECODE
                    ============================================= */

                    $data =
                        json_decode(
                            $response,
                            true
                        );


                    if (
                        !is_array($data)
                    ) {

                        return [

                            "success" => false,

                            "status" => "OFF",

                            "message" =>
                                "Invalid response from license server. " .
                                "JSON error: " .
                                json_last_error_msg()
                        ];
                    }


                    /*
                     * License server responded correctly.
                     *
                     * IMPORTANT:
                     *
                     * If the license server says OFF,
                     * we return OFF immediately.
                     *
                     * We retry only temporary communication
                     * failures, not a genuine license denial.
                     */

                    return $data;
                }


                /* =================================================
                   HTTP ERROR
                ================================================= */

                $last_error =
                    "License checker returned HTTP " .
                    $http_code .
                    ". Content-Type: " .
                    (
                        $content_type ??
                        "unknown"
                    );
            }
        }


        /* =================================================
           RETRY DELAY
        =================================================

        Do not wait after the final attempt.

        Attempt 1 → wait 1 second
        Attempt 2 → wait 2 seconds
        Attempt 3 → stop

        ================================================= */

        if (
            $attempt < $max_attempts
        ) {

            sleep($attempt);
        }
    }


    /* =========================================================
       ALL ATTEMPTS FAILED
    ========================================================= */

    return [

        "success" => false,

        "status" => "OFF",

        "message" =>
            $last_error .
            " License server was contacted " .
            $max_attempts .
            " times."
    ];
}


/* =========================================================
   PERFORM LICENSE CHECK
========================================================= */

$license =
    guard_check_license(
        $user_id,
        $license_url,
        $LICENSE_TIMEOUT ?? 20
    );


/* =========================================================
   AUTHORIZATION
========================================================= */

$authorized =
    isset($license["status"]) &&
    strtoupper(
        trim(
            $license["status"]
        )
    ) === "ON";


/* =========================================================
   APPLICATION DISABLED
========================================================= */

if (!$authorized) {

    http_response_code(403);

    ?>

    <!DOCTYPE html>

    <html>

    <head>

        <meta charset="UTF-8">

        <title>
            Application Disabled
        </title>

        <style>

        body {

            font-family:
                Arial,
                sans-serif;

            background:
                #f2f2f2;

            text-align:
                center;

            padding:
                50px;
        }


        .box {

            background:
                white;

            max-width:
                700px;

            margin:
                auto;

            padding:
                35px;

            border-radius:
                12px;

            box-shadow:
                0 0 10px #aaa;

            border:
                2px solid red;
        }


        h1 {

            color:
                red;
        }


        .message {

            font-size:
                18px;

            margin:
                20px;

            line-height:
                1.5;
        }


        a {

            display:
                inline-block;

            margin-top:
                20px;

            padding:
                10px 20px;

            background:
                #eee;

            border:
                1px solid #ccc;

            border-radius:
                6px;

            text-decoration:
                none;

            color:
                black;
        }

        </style>

    </head>


    <body>

        <div class="box">

            <h1>
                APPLICATION DISABLED
            </h1>


            <p class="message">

                <?= htmlspecialchars(
                    $license["message"]
                    ??
                    "Application is not authorized.",
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>

            </p>


            <p>

                User ID:

                <?= htmlspecialchars(
                    $user_id,
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>

            </p>


            <a href="index.php">
                Back to Main Application
            </a>

        </div>

    </body>

    </html>

    <?php

    exit;
}

?>
