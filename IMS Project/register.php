<?php
session_start();
include 'db_connect.php';

$error = '';
$success = '';

$name = '';
$email = '';
$matricNo = '';
$role = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $matricNo = trim($_POST['matricNo'] ?? '');
    $role = $_POST['role'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($name === '' || $email === '' || $matricNo === '' || $role === '' || $password === '' || $confirm_password === '') {
        $error = "Please fill in all fields.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (!in_array($role, ['student', 'supervisor'], true)) {
        $error = "Invalid role selected.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        if ($role === 'student') {
            $check = $conn->prepare("
                SELECT 1
                FROM student
                WHERE matricNo = ? OR stuEmail = ?
                LIMIT 1
            ");
            $check->bind_param("ss", $matricNo, $email);
            $check->execute();
            $result = $check->get_result();

            if ($result->num_rows > 0) {
                $error = "Matric number or email already exists.";
            }
            $check->close();

            if ($error === '') {
                $stmt = $conn->prepare("
                    INSERT INTO student (matricNo, stuName, stuEmail, stuPassword)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->bind_param("ssss", $matricNo, $name, $email, $hashed_password);

                if ($stmt->execute()) {
                    $success = "Registration successful!";
                    $name = '';
                    $email = '';
                    $matricNo = '';
                    $role = '';
                } else {
                    $error = "Registration failed.";
                }

                $stmt->close();
            }
        }

        if ($role === 'supervisor' && $error === '') {
            $spType = 'Supervisor';

            $check = $conn->prepare("
                SELECT 1
                FROM supervisor
                WHERE SPmatric = ? OR SPgmail = ?
                LIMIT 1
            ");
            $check->bind_param("ss", $matricNo, $email);
            $check->execute();
            $result = $check->get_result();

            if ($result->num_rows > 0) {
                $error = "Matric number or email already exists.";
            }
            $check->close();

            if ($error === '') {
                $stmt = $conn->prepare("
                    INSERT INTO supervisor (SPmatric, SPname, SPgmail, SPpassword, SPType)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->bind_param("sssss", $matricNo, $name, $email, $hashed_password, $spType);

                if ($stmt->execute()) {
                    $success = "Registration successful!";
                    $name = '';
                    $email = '';
                    $matricNo = '';
                    $role = '';
                } else {
                    $error = "Registration failed.";
                }

                $stmt->close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-page">

<?php include 'header.php'; ?>

<div class="page-content">
    <div class="container">
        <h2 class="title">Create Account</h2>

        <?php if ($error): ?>
            <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <?php if ($success): ?>
            <p style="color:lime;"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="input-group">
                <input type="text" name="name" placeholder="Full Name" required
                       value="<?php echo htmlspecialchars($name); ?>">
            </div>

            <div class="input-group">
                <input type="email" name="email" placeholder="Email" required
                       value="<?php echo htmlspecialchars($email); ?>">
            </div>

            <div class="input-group">
                <input type="text" name="matricNo" placeholder="Matriks ID" required
                       value="<?php echo htmlspecialchars($matricNo); ?>">
            </div>

            <div class="input-group">
                <select name="role" required style="width:100%; padding:12px; border-radius:8px; background: var(--bg-input); color: var(--text-main);">
                    <option value="">Select Role</option>
                    <option value="student" <?php echo ($role === 'student') ? 'selected' : ''; ?>>Student</option>
                    <option value="supervisor" <?php echo ($role === 'supervisor') ? 'selected' : ''; ?>>Supervisor</option>
                </select>
            </div>

            <div class="input-group">
                <input type="password" name="password" placeholder="Password" required>
            </div>

            <div class="input-group">
                <input type="password" name="confirm_password" placeholder="Confirm Password" required>
            </div>

            <button type="submit" class="auth-btn">Register</button>

            <div class="register-link">
                Already have an account? <a href="login.php">Login</a>
            </div>
        </form>
    </div>
</div>

<?php include 'footer.php'; ?>

</body>
</html>