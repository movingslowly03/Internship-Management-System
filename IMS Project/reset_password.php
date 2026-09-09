<?php
session_start();
include 'db_connect.php';

$error = '';
$success = '';

$token = trim($_GET['token'] ?? ($_POST['token'] ?? ''));

if ($token === '') {
    die("Invalid reset token.");
}

$stmt = $conn->prepare("
    SELECT resetID, user_type, account_key
    FROM password_resets
    WHERE token = ?
      AND used_at IS NULL
    LIMIT 1
");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();
$resetRow = $result->fetch_assoc();
$stmt->close();

if (!$resetRow && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Reset link is invalid or expired.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($newPassword === '' || $confirmPassword === '') {
        $error = "Please fill in all fields.";
    } elseif ($newPassword !== $confirmPassword) {
        $error = "Passwords do not match.";
    } elseif (!$resetRow) {
        $error = "Reset link is invalid or expired.";
    } else {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        if ($resetRow['user_type'] === 'student') {
            $stmt = $conn->prepare("UPDATE student SET stuPassword = ? WHERE matricNo = ?");
            $stmt->bind_param("ss", $hashedPassword, $resetRow['account_key']);

        } elseif ($resetRow['user_type'] === 'supervisor') {
            $stmt = $conn->prepare("UPDATE supervisor SET SPpassword = ? WHERE SPmatric = ?");
            $stmt->bind_param("ss", $hashedPassword, $resetRow['account_key']);

        } elseif ($resetRow['user_type'] === 'admin') {
            $stmt = $conn->prepare("UPDATE superadmin SET SApassword = ? WHERE SAmatrix = ?");
            $stmt->bind_param("ss", $hashedPassword, $resetRow['account_key']);

        } else {
            $error = "Invalid account type.";
        }

        if ($error === '') {
            if ($stmt->execute()) {
                $stmt->close();

                $stmt = $conn->prepare("UPDATE password_resets SET used_at = NOW() WHERE resetID = ?");
                $stmt->bind_param("i", $resetRow['resetID']);
                $stmt->execute();
                $stmt->close();

                $success = "Password updated successfully. You may now log in.";
            } else {
                $error = "Failed to update password: " . $stmt->error;
                $stmt->close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-page">

<?php include 'header.php'; ?>

<div class="page-content">
    <div class="container">
        <div class="title">Reset Password</div>

        <?php if ($error): ?>
            <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <?php if ($success): ?>
            <p style="color:lime;"><?php echo htmlspecialchars($success); ?></p>
            <div class="register-link">
                <a href="login.php">Back to Login</a>
            </div>
        <?php else: ?>
            <form method="POST" action="">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                <div class="input-group">
                    <input type="password" name="password" placeholder="New Password" required>
                </div>

                <div class="input-group">
                    <input type="password" name="confirm_password" placeholder="Confirm New Password" required>
                </div>

                <button class="auth-btn" type="submit">Update Password</button>
            </form>
        <?php endif; ?>

        
    </div>
</div>

<?php include 'footer.php'; ?>

</body>
</html>