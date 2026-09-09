<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['matricNo'])) {
    die("Student not logged in.");
}

$matricNo = $_SESSION['matricNo'];

/* DELETE LOGBOOK */
if (isset($_GET['delete'])) {

    $logID = (int)$_GET['delete'];

    $delete = $conn->prepare("
        DELETE FROM logbook
        WHERE logID = ? AND matricNo = ?
    ");

    $delete->bind_param("is", $logID, $matricNo);
    $delete->execute();

    header("Location: logbook.php");
    exit();
}

/* LOAD LOGBOOKS */
$logbooks = [];

$stmt = $conn->prepare("
    SELECT logID,
       fileName,
       submission_date,
       logbookStatus,
       reviewStatus,
       reviewFeedback,
       reviewedAt
FROM logbook
    WHERE matricNo = ?
    ORDER BY submission_date DESC, logID DESC
");

$stmt->bind_param("s", $matricNo);
$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $logbooks[] = $row;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Logbook</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body class="app-bg">

<?php include 'header.php'; ?>
<?php include 'sidebar.php'; ?>

<div class="main">

    <div class="logbook-top">
        <a href="upload_logbook.php" class="btn submit">
            + Upload Logbook
        </a>
    </div>

    <div class="card logbook-list">

        <?php if (empty($logbooks)): ?>

            <div class="empty-state">

                <div class="empty-icon">📘</div>

                <h3>No Logbook Uploaded</h3>

                <p>
                    You haven't uploaded any logbooks yet.
                </p>

            </div>

        <?php else: ?>

            <?php foreach ($logbooks as $logbook): ?>

                <div class="logbook-item">

                    <div class="logbook-info">

    <h4>
        <?php echo htmlspecialchars($logbook['fileName']); ?>
    </h4>

    <p>
        Uploaded on
        <?php
        echo !empty($logbook['submission_date'])
            ? date(
                "d M Y",
                strtotime($logbook['submission_date'])
              )
            : "N/A";
        ?>
    </p>

    <div class="report-review-box" style="margin-top:10px;">

        <p class="mini-sub">
            Status:
            <strong>
                <?php echo htmlspecialchars($logbook['reviewStatus'] ?? 'Pending'); ?>
            </strong>
        </p>

        <?php if (!empty($logbook['reviewFeedback'])): ?>
            <p class="mini-sub">
                Feedback:
                <?php echo nl2br(htmlspecialchars($logbook['reviewFeedback'])); ?>
            </p>
        <?php endif; ?>

        <?php if (!empty($logbook['reviewedAt'])): ?>
            <p class="mini-sub">
                Reviewed on
                <?php echo date("d M Y", strtotime($logbook['reviewedAt'])); ?>
            </p>
        <?php endif; ?>

    </div>

</div>

                    <div class="report-actions">

                        <a href="view_logbook.php?id=<?php echo $logbook['logID']; ?>"
                           class="btn view small"
                           target="_blank">
                            View
                        </a>

                        <div class="menu-container">

                            <button type="button" class="menu-btn">
                                ⋮
                            </button>

                            <div class="menu-dropdown">

                                <a href="logbook.php?delete=<?php echo $logbook['logID']; ?>"
                                   class="delete"
                                   onclick="return confirm('Delete this logbook?');">
                                    Delete
                                </a>

                            </div>

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>

</div>

<?php include 'footer.php'; ?>

<script>
document.querySelectorAll(".menu-btn").forEach(btn => {
    btn.addEventListener("click", (e) => {
        e.stopPropagation();

        document.querySelectorAll(".menu-dropdown").forEach(menu => {
            if (menu !== btn.nextElementSibling) {
                menu.style.display = "none";
            }
        });

        const dropdown = btn.nextElementSibling;

        dropdown.style.display =
            dropdown.style.display === "block"
                ? "none"
                : "block";
    });
});

document.addEventListener("click", () => {
    document.querySelectorAll(".menu-dropdown").forEach(menu => {
        menu.style.display = "none";
    });
});
</script>

</body>
</html>