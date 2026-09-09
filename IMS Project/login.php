<?php
session_start();
include 'db_connect.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($identifier === '' || $password === '') {
        $error = "Please fill in all fields.";
    } else {
        // 1) Check student
        $stmt = $conn->prepare("
            SELECT matricNo, stuName, stuEmail, stuPassword
            FROM student
            WHERE matricNo = ? OR stuEmail = ?
            LIMIT 1
        ");
        $stmt->bind_param("ss", $identifier, $identifier);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            if (password_verify($password, $row['stuPassword'])) {
                session_regenerate_id(true);
                $_SESSION['user_type'] = 'student';
                $_SESSION['user_name'] = $row['stuName'];
                $_SESSION['matricNo'] = $row['matricNo'];
                $_SESSION['email'] = $row['stuEmail'];
                header("Location: dashboard.php");
                exit();
            }
        }
        $stmt->close();

        // 2) Check supervisor
        $stmt = $conn->prepare("
            SELECT SPmatric, SPname, SPgmail, SPpassword
            FROM supervisor
            WHERE SPmatric = ? OR SPgmail = ?
            LIMIT 1
        ");
        $stmt->bind_param("ss", $identifier, $identifier);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            if (password_verify($password, $row['SPpassword'])) {
                session_regenerate_id(true);
                $_SESSION['user_type'] = 'supervisor';
                $_SESSION['user_name'] = $row['SPname'];
                $_SESSION['SPmatric'] = $row['SPmatric'];
                $_SESSION['email'] = $row['SPgmail'];
                header("Location: dashboard.php");
                exit();
            }
        }
        $stmt->close();

        // 3) Check superadmin
        $stmt = $conn->prepare("
            SELECT SAmatrix, SAname, SAgmail, SApassword
            FROM superadmin
            WHERE SAmatrix = ? OR SAgmail = ?
            LIMIT 1
        ");
        $stmt->bind_param("ss", $identifier, $identifier);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            if (password_verify($password, $row['SApassword'])) {
                session_regenerate_id(true);
                $_SESSION['user_type'] = 'admin';
                $_SESSION['user_name'] = $row['SAname'];
                $_SESSION['admin_id'] = $row['SAmatrix'];
                $_SESSION['email'] = $row['SAgmail'];
                header("Location: dashboard.php");
                exit();
            }
        }
        $stmt->close();

        $error = "Invalid login details.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-page">

<?php include 'header.php'; ?>

<div class="page-content">
    <div class="container">
        <div class="title">Login</div>

        <?php if ($error): ?>
            <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="input-group">
                <input type="text" name="identifier" placeholder="Email / Matric ID" required>
            </div>

            <div class="input-group">
                <input type="password" name="password" placeholder="Password" required>
            </div>

            <button class="auth-btn" type="submit">Login</button>
        </form>

        <div class="extra-links">
            <a href="forgot_password.php">Forgot Password?</a>
        </div>

        <div class="register-link">
            Don’t have an account? <a href="register.php">Register</a>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

</body>
</html>