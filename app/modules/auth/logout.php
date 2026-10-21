
<?php

// Use the same session cookie settings as login.php
session_set_cookie_params([
    "httponly" => true,
    "secure" => !empty($_SERVER["HTTPS"])
        && $_SERVER["HTTPS"] !== "off",
    "samesite" => "Lax"
]);

session_start();

// Logout must be submitted through a POST form
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit("Method not allowed.");
}

// Verify the logout request
$token = $_POST["logout_token"] ?? "";

if (
    !is_string($token) ||
    !isset($_SESSION["logout_token"]) ||
    !hash_equals($_SESSION["logout_token"], $token)
) {
    http_response_code(403);
    exit("Invalid logout request.");
}

// Remove all session variables
$_SESSION = [];

// Delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();

    setcookie(session_name(), "", [
        "expires" => time() - 3600,
        "path" => $params["path"],
        "domain" => $params["domain"],
        "secure" => $params["secure"],
        "httponly" => $params["httponly"],
        "samesite" => $params["samesite"] ?? "Lax"
    ]);
}

// Destroy the server-side session
session_destroy();

// Prevent caching
header("Cache-Control: no-store");

// Redirect to login
medicore_redirect('login');
exit;

?>
