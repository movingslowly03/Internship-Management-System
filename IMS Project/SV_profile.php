<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'supervisor') {
    header("Location: login.php");
    exit();
}

$spMatric = $_SESSION['SPmatric'] ?? '';
if ($spMatric === '') {
    die("Supervisor not logged in.");
}

$userName = $_SESSION['user_name'] ?? 'Supervisor';
$userEmail = $_SESSION['email'] ?? '';
$profilePic = $_SESSION['user_image'] ?? 'images/pretty.png';

$success = '';
$error = '';

/* LOAD SUPERVISOR DATA */
$supervisor = [
    'SPmatric' => $spMatric,
    'SPname' => $userName,
    'SPgmail' => $userEmail,
    'SPphNO' => '',
    'SPType' => '',
    'SPprogress' => 0
];

$stmt = $conn->prepare("
    SELECT SPname, SPgmail, SPphNO, SPType, SPprogress
    FROM supervisor
    WHERE SPmatric = ?
    LIMIT 1
");
$stmt->bind_param("s", $spMatric);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $supervisor['SPname'] = $row['SPname'] ?? $supervisor['SPname'];
    $supervisor['SPgmail'] = $row['SPgmail'] ?? $supervisor['SPgmail'];
    $supervisor['SPphNO'] = $row['SPphNO'] ?? '';
    $supervisor['SPType'] = $row['SPType'] ?? '';
    $supervisor['SPprogress'] = $row['SPprogress'] ?? 0;

    $userName = $supervisor['SPname'];
    $userEmail = $supervisor['SPgmail'];
}
$stmt->close();

$_SESSION['user_name'] = $userName;
$_SESSION['email'] = $userEmail;

/* SAVE CHANGES */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newName = trim($_POST['name'] ?? '');
    $newEmail = trim($_POST['email'] ?? '');
    $newPhone = trim($_POST['phone'] ?? '');
    $newType = trim($_POST['spType'] ?? '');

    if ($newName === '' || $newEmail === '') {
        $error = "Name and email are required.";
    } else {
        $stmt = $conn->prepare("
    UPDATE supervisor
    SET SPname = ?,
        SPgmail = ?,
        SPphNO = ?,
        SPType = ?
    WHERE SPmatric = ?
");

$stmt->bind_param("sssss", $newName, $newEmail, $newPhone, $newType, $spMatric);

        if ($stmt->execute()) {
            $_SESSION['user_name'] = $newName;
            $_SESSION['email'] = $newEmail;

            $userName = $newName;
            $userEmail = $newEmail;

            $supervisor['SPname'] = $newName;
            $supervisor['SPgmail'] = $newEmail;
            $supervisor['SPphNO'] = $newPhone;
            $supervisor['SPType'] = $newType;

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
    <title>Supervisor Profile</title>
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
                <p><?php echo htmlspecialchars($spMatric); ?></p>
                <p><?php echo htmlspecialchars($userEmail); ?></p>
            </div>
        </div>

        <?php if (!empty($success)): ?>
            <p style="color:#22c55e;"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <p style="color:#ef4444;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <form method="POST" id="supervisorForm">
            <div class="profile-details-grid">

                <div class="profile-column">

                    <div class="form-group">
                        <label>Supervisor ID</label>
                        <input type="text"
                               value="<?php echo htmlspecialchars($spMatric); ?>"
                               readonly-permanent
                               
                               class="editable-input">
                    </div>

                    <div class="form-group">
                        <label>Name</label>
                        <input type="text"
                               name="name"
                               value="<?php echo htmlspecialchars($supervisor['SPname']); ?>"
                               readonly
                               class="editable-input">
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input type="email"
                               name="email"
                               value="<?php echo htmlspecialchars($supervisor['SPgmail']); ?>"
                               readonly
                               class="editable-input">
                    </div>

                </div>

                <div class="profile-column">

                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="text"
                               name="phone"
                               value="<?php echo htmlspecialchars($supervisor['SPphNO']); ?>"
                               readonly
                               class="editable-input">
                    </div>

                    <div class="form-group">
                        <label>Supervisor Type</label>
                        <input type="text"
       name="spType"
       value="<?php echo htmlspecialchars($supervisor['SPType']); ?>"
       readonly
       class="editable-input">
                    </div>

                    

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
document.addEventListener("DOMContentLoaded", () => {
    const editBtn = document.getElementById("editBtn");
    const saveBtn = document.getElementById("saveBtn");
    const cancelBtn = document.getElementById("cancelBtn");
    const inputs = document.querySelectorAll(".editable-input");

    let originalValues = {};

    editBtn.addEventListener("click", () => {
        originalValues = {};

        inputs.forEach(input => {
            if (!input.hasAttribute("readonly-permanent")) {
                originalValues[input.name] = input.value;
                input.removeAttribute("readonly");
            }
        });

        editBtn.style.display = "none";
        saveBtn.style.display = "inline-block";
        cancelBtn.style.display = "inline-block";
    });

    cancelBtn.addEventListener("click", () => {
        inputs.forEach(input => {
            if (!input.hasAttribute("readonly-permanent")) {
                input.value = originalValues[input.name] ?? input.value;
                input.setAttribute("readonly", "readonly");
            }
        });

        editBtn.style.display = "inline-block";
        saveBtn.style.display = "none";
        cancelBtn.style.display = "none";
    });
});
</script>

</body>
</html>