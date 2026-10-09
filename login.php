
<?php

// Configure secure session cookie settings
session_set_cookie_params([
    "httponly" => true,
    "secure" => !empty($_SERVER["HTTPS"])
        && $_SERVER["HTTPS"] !== "off",
    "samesite" => "Lax"
]);

session_start();

// Load database connection
require_once __DIR__ . "/config/database.local.php";

$pageTitle = "Admin Login";
$error = "";

// Already logged in? Go to dashboard
if (isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}

// Generate a CSRF token for the login form
if (empty($_SESSION["login_token"])) {
    $_SESSION["login_token"] = bin2hex(random_bytes(32));
}

// Handle login form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";
    $token = $_POST["login_token"] ?? "";

    if (
        !is_string($token) ||
        !hash_equals($_SESSION["login_token"], $token)
    ) {
        $error = "Invalid request. Please refresh and try again.";

    } elseif ($username === "" || $password === "") {
        $error = "Please enter your username and password.";

    } else {

        try {
            // Find the administrator account
            $sql = "SELECT user_id, username, password_hash
                    FROM users
                    WHERE username = :username
                    LIMIT 1";

            $statement = $pdo->prepare($sql);

            $statement->execute([
                "username" => $username
            ]);

            $user = $statement->fetch();

            // Verify entered password
            if (
                $user &&
                password_verify(
                    $password,
                    $user["password_hash"]
                )
            ) {
                // Prevent session fixation
                session_regenerate_id(true);

                // Store logged-in administrator details
                $_SESSION["user_id"] = (int) $user["user_id"];
                $_SESSION["username"] = $user["username"];

                unset($_SESSION["login_token"]);

                // Redirect to dashboard
                header("Location: index.php");
                exit;

            } else {
                $error = "Invalid username or password.";
            }

        } catch (PDOException $e) {
            error_log($e->getMessage());
            $error = "Login is temporarily unavailable.";
        }
    }
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title><?php echo $pageTitle; ?> | MediCore</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
          rel="stylesheet">

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
          rel="stylesheet">

    <!-- Login Page CSS -->
    <link rel="stylesheet" href="assets/css/login.css">
</head>

<body>

<div class="login-container">

    <!-- Left: Hospital Image -->
    <section class="login-image-section">

        <div class="image-content">

            <div class="login-brand">
                <i class="bi bi-heart-pulse-fill"></i>
                <span>MediCore</span>
            </div>

            <div class="welcome-message">
                <span class="welcome-label">
                    HOSPITAL MANAGEMENT SYSTEM
                </span>

                <h1>
                    Better Healthcare.<br>
                    Smarter Management.
                </h1>

                <p>
                    Your centralized workspace for managing
                    hospital operations efficiently and securely.
                </p>
            </div>

            <div class="image-footer">
                MediCore | Hospital Administration
            </div>

        </div>

    </section>

    <!-- Right: Login Form -->
    <section class="login-form-section">

        <div class="login-form-wrapper">

            <div class="mobile-brand">
                <i class="bi bi-heart-pulse-fill"></i>
                <span>MediCore</span>
            </div>

            <div class="login-icon">
                <i class="bi bi-shield-lock-fill"></i>
            </div>

            <h2>Welcome Back!</h2>

            <p class="login-subtitle">
                Sign in to your administrator account
                to continue.
            </p>
            <?php if ($error !== ""): ?>

    <div class="alert alert-danger" role="alert">
        <i class="bi bi-exclamation-circle me-2"></i>

        <?php echo htmlspecialchars(
            $error,
            ENT_QUOTES,
            "UTF-8"
        ); ?>
    </div>

<?php endif; ?>

            <!-- Form: authentication added next -->
            <form method="POST" action="login.php">

    <input
        type="hidden"
        name="login_token"
        value="<?php echo htmlspecialchars(
            $_SESSION['login_token'],
            ENT_QUOTES,
            'UTF-8'
        ); ?>"
    >

                <div class="mb-4">
                    <label for="username"
                           class="form-label">
                        Username
                    </label>

                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-person"></i>
                        </span>

                        <input
                            type="text"
                            id="username"
                            name="username"
                            class="form-control"
                            placeholder="Enter your username"
                            autocomplete="username"
                            required
                        >
                    </div>
                </div>

                <div class="mb-4">
                    <label for="password"
                           class="form-label">
                        Password
                    </label>

                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-lock"></i>
                        </span>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required
                        >
                    </div>
                </div>

             
<button
    type="submit"
    class="btn btn-primary login-btn"
>
    <i class="bi bi-box-arrow-in-right me-2"></i>
    Sign In
</button>


                <p class="form-note">
                    Login functionality will be activated
                    in the next development stage.
                </p>

            </form>

            <div class="login-footer">
                &copy; <?php echo date("Y"); ?> MediCore
                <br>
                Hospital Management System
            </div>

        </div>

    </section>

</div>

</body>
</html>
