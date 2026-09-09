<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_type']) ||
    !in_array($_SESSION['user_type'], ['admin', 'supervisor'])) {

    header("Location: login.php");
    exit();
}

$evalID = (int)($_GET['id'] ?? 0);

if ($evalID <= 0) {
    die("Invalid evaluation.");
}

$stmt = $conn->prepare("
    SELECT
        e.*,
        s.stuName,
        s.stuEmail,
        sp.SPname
    FROM bli08_evaluation e
    INNER JOIN student s
        ON s.matricNo = e.matricNo
    INNER JOIN supervisor sp
        ON sp.SPmatric = e.SPmatric
    WHERE e.evalID = ?
    LIMIT 1
");

$stmt->bind_param("i", $evalID);
$stmt->execute();

$result = $stmt->get_result();

$evaluation = $result->fetch_assoc();

/* DELETE RECORD */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['delete_record']) &&
    $_SESSION['user_type'] === 'admin'
) {

    $stmt = $conn->prepare("
        DELETE FROM bli08_evaluation
        WHERE evalID = ?
        LIMIT 1
    ");

    $stmt->bind_param(
        "i",
        $evalID
    );

    if ($stmt->execute()) {

        $stmt->close();

        header(
            "Location: admin_bli08_evaluations.php?deleted=1"
        );

        exit();
    }

    $stmt->close();

    die("Failed to delete record.");
}

$stmt->close();

if (!$evaluation) {
    die("Evaluation not found.");
}

$partA = [

    'a1' => 'Project Development & Functionality',
    'a2' => 'Application of Software / Tools / Technologies',
    'a3' => 'Technical Problem Solving',
    'a4' => 'Presentation & Demonstration Skills',
    'a5' => 'Question & Answer Response Ability'

];

$partB = [

    'b1'  => 'Report Structure & Format',
    'b2'  => 'Writing Quality',
    'b3'  => 'Project Introduction & Goals',
    'b4'  => 'Organization Overview',
    'b5'  => 'Project Planning & Professionalism',
    'b6'  => 'Digital Tool Usage',
    'b7'  => 'Project Design & Analysis',
    'b8'  => 'Implementation & Technical Work',
    'b9'  => 'Testing & Debugging',
    'b10' => 'Results, Discussion & Reflection'

];

function ratingLabel($value)
{
    switch ((int)$value) {

        case 1:
            return "Poor";

        case 2:
            return "Fair";

        case 3:
            return "Good";

        case 4:
            return "Very Good";

        case 5:
            return "Excellent";

        default:
            return "-";
    }
}
?>

<!DOCTYPE html>
<html>
<head>

    <title>View BLI-08 Evaluation</title>

    <link rel="stylesheet" href="css/style.css">

    <style>

        .evaluation-section {
            margin-top: 20px;
        }

        .evaluation-row {

            padding: 12px 0;

            border-bottom: 1px solid var(--border);

            display: flex;

            justify-content: space-between;

            gap: 20px;
        }

        .evaluation-row:last-child {
            border-bottom: none;
        }

        .score-badge {

            padding: 4px 10px;

            border-radius: 999px;

            background: rgba(37,99,235,.1);

            color: var(--accent);

            font-weight: 600;
        }

    </style>

</head>

<body class="app-bg">

<?php include 'header.php'; ?>
<?php include 'sidebar.php'; ?>

<div class="main">

    <div class="card">

        <h2>BLI-08 Evaluation</h2>

        <p>
            Submitted by
            <?php echo htmlspecialchars($evaluation['SPname']); ?>
        </p>

    </div>

    <div class="card evaluation-section">

        <h3>Student Information</h3>

        <p>
            <strong>Name:</strong>
            <?php echo htmlspecialchars($evaluation['stuName']); ?>
        </p>

        <p>
            <strong>Matric No:</strong>
            <?php echo htmlspecialchars($evaluation['matricNo']); ?>
        </p>

        <p>
            <strong>Email:</strong>
            <?php echo htmlspecialchars($evaluation['stuEmail']); ?>
        </p>

        <p>
            <strong>Submitted:</strong>
            <?php echo date(
                "d M Y H:i",
                strtotime($evaluation['submittedAt'])
            ); ?>
        </p>

    </div>

    <div class="card evaluation-section">

        <h3>Part A : Individual Project Evaluation</h3>

        <?php foreach ($partA as $field => $label): ?>

            <div class="evaluation-row">

                <span>
                    <?php echo htmlspecialchars($label); ?>
                </span>

                <span class="score-badge">

                    <?php
                    echo ratingLabel(
                        $evaluation[$field]
                    );
                    ?>

                    (<?php echo $evaluation[$field]; ?>/5)

                </span>

            </div>

        <?php endforeach; ?>

    </div>

    <div class="card evaluation-section">

        <h3>Part B : Internship Report Evaluation</h3>

        <?php foreach ($partB as $field => $label): ?>

            <div class="evaluation-row">

                <span>
                    <?php echo htmlspecialchars($label); ?>
                </span>

                <span class="score-badge">

                    <?php
                    echo ratingLabel(
                        $evaluation[$field]
                    );
                    ?>

                    (<?php echo $evaluation[$field]; ?>/5)

                </span>

            </div>

        <?php endforeach; ?>

    </div>

    <div class="card evaluation-section">

        <h3>Final Score</h3>

        <h2>

            <?php
            echo number_format(
                $evaluation['totalScore'],
                2
            );
            ?>

            / 40

        </h2>

    </div>

    <div class="card evaluation-section">

        <h3>Comments</h3>

        <p>

            <?php

            echo nl2br(
                htmlspecialchars(
                    $evaluation['comments']
                )
            );

            ?>

        </p>

    </div>

    <div class="profile-actions">

    <button
        type="button"
        class="btn"
        onclick="history.back();">

        Back

    </button>

    <?php if ($_SESSION['user_type'] === 'admin'): ?>

        <form
            method="POST"
            style="display:inline;"
            onsubmit="return confirm(
                'Delete this evaluation record?'
            );">

            <button
                type="submit"
                name="delete_record"
                class="btn delete">

                Delete Record

            </button>

        </form>

    <?php endif; ?>

</div>

</div>

<?php include 'footer.php'; ?>

</body>
</html>