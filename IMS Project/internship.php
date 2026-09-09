<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_type'])) {
    header("Location: login.php");
    exit();
}

$userType = $_SESSION['user_type'];
$success = '';
$error = '';

// Only students should edit internship details in this version
if ($userType !== 'student') {
    header("Location: dashboard.php");
    exit();
}

$matricNo = $_SESSION['matricNo'] ?? '';

$internship = [
    'company_name' => '',
    'company_address' => '',
    'company_email' => '',
    'company_phone' => '',
    'supervisor_name' => '',
    'supervisor_position' => '',
    'supervisor_email' => '',
    'start_date' => '',
    'end_date' => ''
];

// Save updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_name = trim($_POST['company_name'] ?? '');
    $company_address = trim($_POST['company_address'] ?? '');
    $company_email = trim($_POST['company_email'] ?? '');
    $company_phone = trim($_POST['company_phone'] ?? '');
    $supervisor_name = trim($_POST['supervisor_name'] ?? '');
    $supervisor_position = trim($_POST['supervisor_position'] ?? '');
    $supervisor_email = trim($_POST['supervisor_email'] ?? '');
    $start_date = trim($_POST['start_date'] ?? '');
    $end_date = trim($_POST['end_date'] ?? '');

    if ($company_name === '' || $company_email === '' || $start_date === '' || $end_date === '') {
        $error = "Please fill in all required fields.";
    } else {
        $checkStmt = $conn->prepare("SELECT internID FROM internship WHERE matricNo = ? LIMIT 1");
        $checkStmt->bind_param("s", $matricNo);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult && $checkResult->num_rows > 0) {
            $stmt = $conn->prepare("
                UPDATE internship
SET companyName = ?,
    companyAddress = ?,
    companyEmail = ?,
    companyPhone = ?,
    supervisorName = ?,
    supervisorPosition = ?,
    supervisorEmail = ?,
    startDate = ?,
    endDate = ?,
    internStatus = 'Submitted'
WHERE matricNo = ?
            ");
            $stmt->bind_param(
                "ssssssssss",
                $company_name,
                $company_address,
                $company_email,
                $company_phone,
                $supervisor_name,
                $supervisor_position,
                $supervisor_email,
                $start_date,
                $end_date,
                $matricNo
            );
        } else {
            $stmt = $conn->prepare("
                INSERT INTO internship (
                    matricNo,
                    companyName,
                    companyAddress,
                    companyEmail,
                    companyPhone,
                    supervisorName,
                    supervisorPosition,
                    supervisorEmail,
                    startDate,
                    endDate,
                    internStatus
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Submitted')
            ");
            $stmt->bind_param(
                "ssssssssss",
                $matricNo,
                $company_name,
                $company_address,
                $company_email,
                $company_phone,
                $supervisor_name,
                $supervisor_position,
                $supervisor_email,
                $start_date,
                $end_date
            );
        }

        if ($stmt->execute()) {
            header("Location: internship.php?updated=1");
            exit();
        } else {
            $error = "Failed to update internship information.";
        }

        $stmt->close();
        $checkStmt->close();
    }
}

if (isset($_GET['updated'])) {
    $success = "Internship information updated successfully.";
}

// Load current internship data
$stmt = $conn->prepare("
    SELECT companyName, companyAddress, companyEmail, companyPhone,
           supervisorName, supervisorPosition, supervisorEmail,
           startDate, endDate
    FROM internship
    WHERE matricNo = ?
    LIMIT 1
");
$stmt->bind_param("s", $matricNo);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $internship['company_name'] = $row['companyName'] ?? '';
    $internship['company_address'] = $row['companyAddress'] ?? '';
    $internship['company_email'] = $row['companyEmail'] ?? '';
    $internship['company_phone'] = $row['companyPhone'] ?? '';
    $internship['supervisor_name'] = $row['supervisorName'] ?? '';
    $internship['supervisor_position'] = $row['supervisorPosition'] ?? '';
    $internship['supervisor_email'] = $row['supervisorEmail'] ?? '';
    $internship['start_date'] = $row['startDate'] ?? '';
    $internship['end_date'] = $row['endDate'] ?? '';
}

$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Internship</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body class="app-bg">

<?php include 'header.php'; ?>
<?php include 'sidebar.php'; ?>

<div class="main">

    <div class="card profile-card internship-card">

        <h2>Internship Details</h2>

        <?php if (!empty($success)): ?>
            <p style="color:#22c55e;"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <p style="color:#ef4444;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <form id="internshipForm" method="POST">
            <div class="profile-details-grid">

    <!-- LEFT COLUMN (5 fields) -->
    <div class="profile-column">

        <div class="form-group">
            <label>Company Name</label>
            <input type="text" name="company_name"
                value="<?php echo htmlspecialchars($internship['company_name']); ?>"
                readonly class="editable-input">
        </div>

        <div class="form-group">
            <label>Company Address</label>
            <input type="text" name="company_address"
                value="<?php echo htmlspecialchars($internship['company_address']); ?>"
                readonly class="editable-input">
        </div>

        <div class="form-group">
            <label>Company Email</label>
            <input type="email" name="company_email"
                value="<?php echo htmlspecialchars($internship['company_email']); ?>"
                readonly class="editable-input">
        </div>

        <div class="form-group">
            <label>Supervisor Name</label>
            <input type="text" name="supervisor_name"
                value="<?php echo htmlspecialchars($internship['supervisor_name']); ?>"
                readonly class="editable-input">
        </div>
        
        <div class="form-group">
            <label>Supervisor Position</label>
            <input type="text" name="supervisor_position"
                value="<?php echo htmlspecialchars($internship['supervisor_position']); ?>"
                readonly class="editable-input">
        </div>

        

    </div>

    <!-- RIGHT COLUMN (4 fields) -->
    <div class="profile-column">


        <div class="form-group">
            <label>Supervisor Phone No.</label>
            <input type="text" name="company_phone"
                value="<?php echo htmlspecialchars($internship['company_phone']); ?>"
                readonly class="editable-input">
        </div>

        <div class="form-group">
            <label>Supervisor Email</label>
            <input type="email" name="supervisor_email"
                value="<?php echo htmlspecialchars($internship['supervisor_email']); ?>"
                readonly class="editable-input">
        </div>

        <div class="form-group">
            <label>Start Date</label>
            <input type="date" name="start_date"
                value="<?php echo htmlspecialchars($internship['start_date']); ?>"
                readonly class="editable-input">
        </div>

        <div class="form-group">
            <label>End Date</label>
            <input type="date" name="end_date"
                value="<?php echo htmlspecialchars($internship['end_date']); ?>"
                readonly class="editable-input">
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