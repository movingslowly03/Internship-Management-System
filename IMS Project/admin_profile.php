<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$adminId = $_SESSION['admin_id'] ?? '';
if ($adminId === '') {
    die("Admin not logged in.");
}

$userName = $_SESSION['user_name'] ?? 'Admin';
$userEmail = $_SESSION['email'] ?? '';
$profilePic = $_SESSION['user_image'] ?? 'images/pretty.png';

$success = '';
$error = '';

/* LOAD ADMIN DATA */
$stmt = $conn->prepare("
    SELECT SAname, SAgmail
    FROM superadmin
    WHERE SAmatrix = ?
    LIMIT 1
");
$stmt->bind_param("s", $adminId);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $userName = $row['SAname'] ?? $userName;
    $userEmail = $row['SAgmail'] ?? $userEmail;
}
$stmt->close();

/* SAVE CHANGES */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newName = trim($_POST['name'] ?? '');
    $newEmail = trim($_POST['email'] ?? '');
    $newPassword = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($newName === '' || $newEmail === '') {
        $error = "Name and email are required.";
    } elseif ($newPassword !== '' && $newPassword !== $confirmPassword) {
        $error = "Passwords do not match.";
    } else {
        if ($newPassword !== '') {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("
                UPDATE superadmin
                SET SAname = ?, SAgmail = ?, SApassword = ?
                WHERE SAmatrix = ?
            ");
            $stmt->bind_param("ssss", $newName, $newEmail, $hashedPassword, $adminId);
        } else {
            $stmt = $conn->prepare("
                UPDATE superadmin
                SET SAname = ?, SAgmail = ?
                WHERE SAmatrix = ?
            ");
            $stmt->bind_param("sss", $newName, $newEmail, $adminId);
        }

        if ($stmt->execute()) {
            $_SESSION['user_name'] = $newName;
            $_SESSION['email'] = $newEmail;

            $userName = $newName;
            $userEmail = $newEmail;

            $success = "Profile updated successfully.";
        } else {
            $error = "Failed to update profile.";
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Profile</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body class="app-bg">

<?php include 'header.php'; ?>
<?php include 'sidebar.php'; ?>

<div class="main">

    <div class="card profile-card internship-card">

        <div class="profile-top">
            <img src="<?php echo htmlspecialchars($profilePic); ?>" alt="Profile Picture" class="profile-pic-large">
            <div class="profile-info">
                <h2><?php echo htmlspecialchars($userName); ?></h2>
                <p><?php echo htmlspecialchars($adminId); ?></p>
                <p><?php echo htmlspecialchars($userEmail); ?></p>
            </div>
        </div>

        <?php if (!empty($success)): ?>
            <p style="color:#22c55e;"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <p style="color:#ef4444;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <form method="POST">
            <div class="profile-details-grid">
                <div class="profile-column">

                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($userName); ?>" class="editable-input">
                    </div>

                    <div class="form-group">
                        <label>Admin ID</label>
                        <input type="text" value="<?php echo htmlspecialchars($adminId); ?>" readonly class="editable-input">
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($userEmail); ?>" class="editable-input">
                    </div>

                </div>

                <div class="profile-column">

                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="password" placeholder="Leave blank to keep current password" class="editable-input">
                    </div>

                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" placeholder="Leave blank to keep current password" class="editable-input">
                    </div>

                </div>
            </div>

            <div class="profile-actions">
                <a href="dashboard.php" class="btn">Back</a>
                <button type="submit" class="btn submit">Save Changes</button>
            </div>
        </form>

    </div>

</div>

<?php include 'footer.php'; ?>

</body>
</html>