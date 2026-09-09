<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_type'])) {
    header("Location: login.php");
    exit();
}

$userType = $_SESSION['user_type'];

$profile = [
    'name' => '',
    'email' => '',
    'id' => '',
    'phone' => '',
    'ic' => ''
];

$error = '';
$success = '';


/* ===================================
   SAVE PROFILE
=================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $ic = trim($_POST['ic'] ?? '');

    if ($userType === 'student') {

        $matricNo = $_SESSION['matricNo'] ?? '';

        $stmt = $conn->prepare("
            UPDATE student
            SET stuName = ?, stuEmail = ?, stuPhNO = ?, stuIC = ?
            WHERE matricNo = ?
        ");

        $stmt->bind_param(
            "sssss",
            $full_name,
            $email,
            $phone,
            $ic,
            $matricNo
        );

        if ($stmt->execute()) {
            header("Location: profile.php?updated=1");
            exit();
        }

        $stmt->close();
    }
}

if (isset($_GET['updated'])) {
    $success = "Profile updated successfully.";
}


/* ===================================
   LOAD PROFILE
=================================== */

if ($userType === 'student') {

    $matricNo = $_SESSION['matricNo'] ?? '';

    $stmt = $conn->prepare("
        SELECT matricNo, stuName, stuPhNO, stuEmail, stuIC
        FROM student
        WHERE matricNo = ?
        LIMIT 1
    ");

    $stmt->bind_param("s", $matricNo);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $profile['name'] = $row['stuName'];
        $profile['email'] = $row['stuEmail'];
        $profile['id'] = $row['matricNo'];
        $profile['phone'] = $row['stuPhNO'];
        $profile['ic'] = $row['stuIC'];
    }

    $stmt->close();

} elseif ($userType === 'supervisor') {

    $spMatric = $_SESSION['SPmatric'] ?? '';

    $stmt = $conn->prepare("
        SELECT SPmatric, SPname, SPphNO, SPgmail
        FROM supervisor
        WHERE SPmatric = ?
        LIMIT 1
    ");

    $stmt->bind_param("s", $spMatric);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $profile['name'] = $row['SPname'];
        $profile['email'] = $row['SPgmail'];
        $profile['id'] = $row['SPmatric'];
        $profile['phone'] = $row['SPphNO'];
    }

    $stmt->close();

} elseif ($userType === 'admin') {

    $adminId = $_SESSION['admin_id'] ?? '';

    $stmt = $conn->prepare("
        SELECT SAmatrix, SAname, SAno, SAgmail
        FROM superadmin
        WHERE SAmatrix = ?
        LIMIT 1
    ");

    $stmt->bind_param("s", $adminId);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $profile['name'] = $row['SAname'];
        $profile['email'] = $row['SAgmail'];
        $profile['id'] = $row['SAmatrix'];
        $profile['phone'] = $row['SAno'];
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Profile</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body class="app-bg">

<?php include 'header.php'; ?>
<?php include 'sidebar.php'; ?>

<div class="main">

    <div class="card profile-card">

        <div class="profile-top">
            <img src="images/pretty.png" class="profile-pic-large" alt="Profile Picture">

            <div class="profile-info">
                <h2><?php echo htmlspecialchars($profile['name']); ?></h2>
<p><?php echo htmlspecialchars($profile['email']); ?></p>
            </div>
        </div>

        <?php if (!empty($success)): ?>
            <p style="color:#22c55e;"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <p style="color:#ef4444;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <?php
        $displayName = $profile['name'];
        $displayEmail = $profile['email'];
        $displayId = $profile['id'];

        if ($_SESSION['user_type'] === 'student') {
            $displayId = $profile['id'];
        } elseif ($_SESSION['user_type'] === 'supervisor') {
            $displayId = $_SESSION['SPmatric'] ?? '';
        } elseif ($_SESSION['user_type'] === 'admin') {
            $displayId = $_SESSION['admin_id'] ?? '';
        }
        ?>

        <form id="profileForm" method="POST">
            <div class="profile-details">

                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($displayName); ?>" readonly class="editable-input">
                </div>

                <div class="form-group">
                    <label>
                        <?php
                        if ($_SESSION['user_type'] === 'student') {
                            echo 'Matriks Number';
                        } elseif ($_SESSION['user_type'] === 'supervisor') {
                            echo 'Supervisor ID';
                        } else {
                            echo 'Admin ID';
                        }
                        ?>
                    </label>
                    <input type="text" value="<?php echo htmlspecialchars($displayId); ?>" readonly>
                </div>

                <div class="form-group">
                    <label>IC Number</label>
                    <input type="text" name="ic" value="<?php echo htmlspecialchars($profile['ic']); ?>" readonly class="editable-input">
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($profile['email']); ?>" readonly class="editable-input">
                </div>

                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone" value="<?php echo htmlspecialchars($profile['phone']); ?>" readonly class="editable-input">
                </div>

                

            </div>

            <div class="profile-actions">
                <button type="button" id="editBtn" class="btn update">Edit</button>
                <button type="submit" id="saveBtn" class="btn submit" style="display:none;">Save</button>
                <button type="button" id="cancelBtn" class="btn" style="display:none;">Cancel</button>
            </div>
        </form>

    </div>

</div>

<?php include 'footer.php'; ?>

<script>
const editBtn = document.getElementById("editBtn");
const saveBtn = document.getElementById("saveBtn");
const cancelBtn = document.getElementById("cancelBtn");
const inputs = document.querySelectorAll(".editable-input");

let originalValues = {};

editBtn.addEventListener("click", () => {
    originalValues = {};
    inputs.forEach(input => {
        originalValues[input.name] = input.value;
        input.readOnly = false;
    });

    editBtn.style.display = "none";
    saveBtn.style.display = "inline-block";
    cancelBtn.style.display = "inline-block";
});

cancelBtn.addEventListener("click", () => {
    inputs.forEach(input => {
        input.value = originalValues[input.name];
        input.readOnly = true;
    });

    editBtn.style.display = "inline-block";
    saveBtn.style.display = "none";
    cancelBtn.style.display = "none";
});
</script>

</body>
</html>