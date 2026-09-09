<?php
session_start();
include 'db_connect.php';

$error = '';
$success = '';
$resetLink = '';

function findAccount(mysqli $conn, string $identifier): ?array
{
    $stmt = $conn->prepare("
        SELECT matricNo AS account_key, 'student' AS user_type
        FROM student
        WHERE matricNo = ? OR stuEmail = ?
        LIMIT 1
    ");
    $stmt->bind_param("ss", $identifier, $identifier);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $stmt->close();
        return $row;
    }
    $stmt->close();

    $stmt = $conn->prepare("
        SELECT SPmatric AS account_key, 'supervisor' AS user_type
        FROM supervisor
        WHERE SPmatric = ? OR SPgmail = ?
        LIMIT 1
    ");
    $stmt->bind_param("ss", $identifier, $identifier);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $stmt->close();
        return $row;
    }
    $stmt->close();

    $stmt = $conn->prepare("
        SELECT SAmatrix AS account_key, 'admin' AS user_type
        FROM superadmin
        WHERE SAmatrix = ? OR SAgmail = ?
        LIMIT 1
    ");
    $stmt->bind_param("ss", $identifier, $identifier);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $stmt->close();
        return $row;
    }
    $stmt->close();

    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');

    if ($identifier === '') {
        $error = "Please enter your email or ID.";
    } else {
        $account = findAccount($conn, $identifier);

        if (!$account) {
            $error = "No account found.";
        } else {
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', time() + 3600);

            $stmt = $conn->prepare("
                INSERT INTO password_resets (user_type, account_key, token, expires_at)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->bind_param("ssss", $account['user_type'], $account['account_key'], $token, $expiresAt);

            if ($stmt->execute()) {
                $resetLink = "reset_password.php?token=" . urlencode($token);
                $success = "Reset link generated successfully.";
            } else {
                $error = "Failed to generate reset link: " . $stmt->error;
            }

            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-page">

<?php include 'header.php'; ?>

<div class="page-content">
    <div class="container">
        <div class="title">Forgot Password</div>

        <?php if ($error): ?>
            <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <?php if ($success): ?>
            <p style="color:lime;"><?php echo htmlspecialchars($success); ?></p>
            <p style="margin-top:10px;">
                <a href="<?php echo htmlspecialchars($resetLink); ?>">Open reset link</a>
            </p>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="input-group">
                <input type="text" name="identifier" placeholder="Email or Matric ID" required>
            </div>

            <button class="auth-btn" type="submit">Generate Reset Link</button>
        </form>

        <div class="register-link">
            <a href="login.php">Back to Login</a>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

</body>
</html>