<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$evaluations = [];

$stmt = $conn->prepare("
    SELECT
        e.evalID,
        e.matricNo,
        s.stuName,
        sp.SPname,
        e.totalScore,
        e.submittedAt
    FROM bli08_evaluation e
    INNER JOIN student s
        ON s.matricNo = e.matricNo
    INNER JOIN supervisor sp
        ON sp.SPmatric = e.SPmatric
    ORDER BY e.submittedAt DESC
");

$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $evaluations[] = $row;
}

$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>BLI-08 Evaluations</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body class="app-bg">

<?php include 'header.php'; ?>
<?php include 'sidebar.php'; ?>

<div class="main">

    <div class="card">

        <h2>BLI-08 Academic Supervisor Evaluations</h2>

        <p>
            View all submitted supervisor evaluations.
        </p>

    </div>

    <div class="card" style="margin-top:20px;">

        <?php if (empty($evaluations)): ?>

            <p>No evaluations submitted yet.</p>

        <?php else: ?>

            <div class="report-list">

                <?php foreach ($evaluations as $eval): ?>

                    <div class="report-item">

                        <div class="report-info">

                            <h4>
                                <?php echo htmlspecialchars($eval['stuName']); ?>
                            </h4>

                            <p>
                                Matric No:
                                <?php echo htmlspecialchars($eval['matricNo']); ?>
                            </p>

                            <p>
                                Supervisor:
                                <?php echo htmlspecialchars($eval['SPname']); ?>
                            </p>

                            <p>
                                Score:
                                <?php echo number_format($eval['totalScore'], 2); ?>/40
                            </p>

                            <p>
                                Submitted:
                                <?php echo date(
                                    "d M Y",
                                    strtotime($eval['submittedAt'])
                                ); ?>
                            </p>

                        </div>

                        <div class="report-actions">

                            <a
                                href="view_bli08.php?id=<?php echo $eval['evalID']; ?>"
                                class="btn view small">

                                View

                            </a>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </div>

</div>

<?php include 'footer.php'; ?>

</body>
</html>