<?php
session_start();
include 'db_connect.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';

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

            $stmt->close();
            header("Location: dashboard.php");
            exit();
        }
    }

    $stmt->close();
    $error = "Invalid admin login details.";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Login</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-page">

<?php include 'header.php'; ?>

<div class="page-content">
    <div class="container">
        <h2 class="title">Admin Login</h2>

        <?php if ($error): ?>
            <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="input-group">
                <input type="text" name="identifier" placeholder="Admin ID / Email" required>
            </div>

            <div class="input-group">
                <input type="password" name="password" placeholder="Password" required>
            </div>

            <button type="submit" class="auth-btn">Login</button>
        </form>
    </div>
</div>

<?php include 'footer.php'; ?>

</body>
</html>