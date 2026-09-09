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

$error = '';
$success = '';

$selectedMatric = trim($_GET['matricNo'] ?? ($_POST['matricNo'] ?? ''));
$student = null;

/* LOAD SUPERVISOR DETAILS */

$stmt = $conn->prepare("
    SELECT SPname, SPgmail
    FROM supervisor
    WHERE SPmatric = ?
    LIMIT 1
");

$stmt->bind_param("s", $spMatric);
$stmt->execute();

$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $userName = $row['SPname'];
    $userEmail = $row['SPgmail'];
}

$stmt->close();

$_SESSION['user_name'] = $userName;
$_SESSION['email'] = $userEmail;

/* LOAD ASSIGNED STUDENT */

if ($selectedMatric !== '') {

    $stmt = $conn->prepare("
        SELECT
            s.matricNo,
            s.stuName,
            s.stuEmail
        FROM assigned a
        INNER JOIN student s
            ON s.matricNo = a.matricNo
        WHERE a.SPmatric = ?
        AND s.matricNo = ?
        LIMIT 1
    ");

    $stmt->bind_param(
        "ss",
        $spMatric,
        $selectedMatric
    );

    $stmt->execute();

    $result = $stmt->get_result();

    $student = $result->fetch_assoc() ?: null;

    $stmt->close();
}

if (!$student) {
    die("Invalid or unassigned student selected.");
}

/* CHECK IF ALREADY SUBMITTED */

$alreadySubmitted = false;

$stmt = $conn->prepare("
    SELECT evalID
    FROM bli08_evaluation
    WHERE matricNo = ?
    LIMIT 1
");

$stmt->bind_param(
    "s",
    $selectedMatric
);

$stmt->execute();

$result = $stmt->get_result();

$alreadySubmitted =
    $result->num_rows > 0;

$stmt->close();

/* CRITERIA */

$partA = [

    'a1' => 'Project Development & Functionality',
    'a2' => 'Application of Software / Tools / Technologies',
    'a3' => 'Technical Problem Solving',
    'a4' => 'Presentation & Demonstration Skills',
    'a5' => 'Question & Answer Response Ability'

];

$weightsA = [

    'a1' => 5,
    'a2' => 4,
    'a3' => 4,
    'a4' => 4,
    'a5' => 3

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

$weightsB = [

    'b1'  => 1,
    'b2'  => 1,
    'b3'  => 2,
    'b4'  => 1,
    'b5'  => 2,
    'b6'  => 3,
    'b7'  => 2,
    'b8'  => 3,
    'b9'  => 3,
    'b10' => 2

];

$weights = [

    'a1'=>5,
    'a2'=>4,
    'a3'=>4,
    'a4'=>4,
    'a5'=>3,

    'b1'=>1,
    'b2'=>1,
    'b3'=>2,
    'b4'=>1,
    'b5'=>2,

    'b6'=>3,
    'b7'=>2,
    'b8'=>3,
    'b9'=>3,
    'b10'=>2

];

/* SAVE FORM */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($alreadySubmitted) {

        $error =
            "This student has already been evaluated.";

    } else {

        $scores = [];

        foreach ($weights as $field => $weight) {

            $scores[$field] =
                (int)($_POST[$field] ?? 0);

            if (
                $scores[$field] < 1 ||
                $scores[$field] > 5
            ) {

                $error =
                    "Please answer all questions.";

                break;
            }
        }

        $comments =
            trim($_POST['comments'] ?? '');

        if ($error === '') {

            $totalScore = 0;

            foreach (
                $weights as $field => $weight
            ) {

                $totalScore +=
                    ($scores[$field] / 5)
                    * $weight;
            }

            $totalScore =
                round($totalScore, 2);

            $stmt = $conn->prepare("

                INSERT INTO bli08_evaluation (

                    SPmatric,
                    matricNo,

                    a1,
                    a2,
                    a3,
                    a4,
                    a5,

                    b1,
                    b2,
                    b3,
                    b4,
                    b5,
                    b6,
                    b7,
                    b8,
                    b9,
                    b10,

                    totalScore,
                    comments

                )

                VALUES (

                    ?, ?,

                    ?, ?, ?, ?, ?,

                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,

                    ?, ?

                )

            ");

            $stmt->bind_param(
    "ssiiiiiiiiiiiiiiids",

                $spMatric,
                $selectedMatric,

                $scores['a1'],
                $scores['a2'],
                $scores['a3'],
                $scores['a4'],
                $scores['a5'],

                $scores['b1'],
                $scores['b2'],
                $scores['b3'],
                $scores['b4'],
                $scores['b5'],
                $scores['b6'],
                $scores['b7'],
                $scores['b8'],
                $scores['b9'],
                $scores['b10'],

                $totalScore,
                $comments
            );

            if ($stmt->execute()) {

                $stmt->close();

                header(
                    "Location: bli08_evaluation.php?matricNo=" .
                    urlencode($selectedMatric) .
                    "&saved=1"
                );

                exit();
            }

            $error =
                "Failed to save evaluation.";

            $stmt->close();
        }
    }
}

if (isset($_GET['saved'])) {

    $success =
        "Evaluation submitted successfully.";
}

function checkedValue(
    $current,
    $value
): string {

    return (
        (string)$current ===
        (string)$value
    )
        ? 'checked'
        : '';
}

?>

<!DOCTYPE html>
<html>

<head>

    <title>BLI-08 Academic Supervisor Evaluation</title>

    <link rel="stylesheet" href="css/style.css">

    <style>

        .form-section {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 24px;
            margin-top: 20px;
        }

        .form-section h3 {
            margin-top: 0;
            margin-bottom: 18px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }

        .question-block {
            padding-bottom: 18px;
            margin-bottom: 18px;
            border-bottom: 1px solid var(--border);
        }

        .question-block:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .question-block > label {
            display: block;
            margin-bottom: 12px;
            font-weight: 600;
            font-size: 16px;
        }

        .scale-row {
            display: flex;
            flex-wrap: wrap;
            gap: 18px;
        }

        .scale-option {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .weight-badge {

            display: inline-block;

            margin-left: 8px;

            padding: 3px 8px;

            border-radius: 999px;

            background: rgba(37,99,235,.10);

            color: var(--accent);

            font-size: 13px;

            font-weight: 600;

        }

        textarea.monitor-textarea {

            width: 100%;

            min-height: 140px;

            padding: 12px;

            border-radius: 8px;

            border: 1px solid var(--border);

            background: var(--bg-input);

            color: var(--text-main);

            resize: vertical;
        }

        .score-note {

            padding: 14px;

            border-radius: 10px;

            background: rgba(37,99,235,.08);

            border: 1px solid rgba(37,99,235,.18);

            margin-bottom: 20px;
        }

        @media (max-width: 800px) {

            .form-grid {
                grid-template-columns: 1fr;
            }

            .scale-row {
                flex-direction: column;
                gap: 8px;
            }

        }

    </style>

</head>

<body class="app-bg">

<?php include 'header.php'; ?>
<?php include 'sidebar.php'; ?>

<div class="main">

    <div class="card">

        <div class="profile-header">

            <img
                src="<?php echo htmlspecialchars($profilePic); ?>"
                alt="Profile Picture"
                class="profile-pic">

            <div class="welcome-text">

                <h2>BLI-08 Evaluation Form</h2>

                <p>
                    Academic Supervisor Evaluation
                </p>

            </div>

        </div>

        <?php if (!empty($success)): ?>

            <p style="color:#22c55e;">
                <?php echo htmlspecialchars($success); ?>
            </p>

        <?php endif; ?>

        <?php if (!empty($error)): ?>

            <p style="color:#ef4444;">
                <?php echo htmlspecialchars($error); ?>
            </p>

        <?php endif; ?>

        <?php if ($alreadySubmitted): ?>

            <p style="color:#f59e0b;">
                This student has already been evaluated.
            </p>

        <?php endif; ?>

    </div>

    <div class="form-section">

        <form method="POST">

            <input
                type="hidden"
                name="matricNo"
                value="<?php echo htmlspecialchars($student['matricNo']); ?>">

            <h3>Student Information</h3>

            <div class="form-grid">

                <div class="form-group">

                    <label>Academic Supervisor</label>

                    <input
                        type="text"
                        value="<?php echo htmlspecialchars($userName); ?>"
                        readonly>

                </div>

                <div class="form-group">

                    <label>Student Name</label>

                    <input
                        type="text"
                        value="<?php echo htmlspecialchars($student['stuName']); ?>"
                        readonly>

                </div>

                <div class="form-group">

                    <label>Matric Number</label>

                    <input
                        type="text"
                        value="<?php echo htmlspecialchars($student['matricNo']); ?>"
                        readonly>

                </div>

                <div class="form-group">

                    <label>Email</label>

                    <input
                        type="text"
                        value="<?php echo htmlspecialchars($student['stuEmail']); ?>"
                        readonly>

                </div>

            </div>

            <div class="form-section">

                <div class="score-note">

                    Rate each criterion using:

                    <strong>
                        Poor, Fair, Good, Very Good, Excellent
                    </strong>

                </div>

                <h3>
                    Part A : Individual Project Evaluation (20%)
                </h3>

                <?php foreach ($partA as $key => $label): ?>

                    <div class="question-block">

                        <label>

                            <?php echo htmlspecialchars($label); ?>

                            <span class="weight-badge">
                                <?php echo $weightsA[$key]; ?>%
                            </span>

                        </label>

                        <div class="scale-row">

                            <label class="scale-option">
                                <input
                                    type="radio"
                                    name="<?php echo $key; ?>"
                                    value="1"
                                    required
                                    <?php echo checkedValue($_POST[$key] ?? '', 1); ?>>
                                Poor
                            </label>

                            <label class="scale-option">
                                <input
                                    type="radio"
                                    name="<?php echo $key; ?>"
                                    value="2"
                                    <?php echo checkedValue($_POST[$key] ?? '', 2); ?>>
                                Fair
                            </label>

                            <label class="scale-option">
                                <input
                                    type="radio"
                                    name="<?php echo $key; ?>"
                                    value="3"
                                    <?php echo checkedValue($_POST[$key] ?? '', 3); ?>>
                                Good
                            </label>

                            <label class="scale-option">
                                <input
                                    type="radio"
                                    name="<?php echo $key; ?>"
                                    value="4"
                                    <?php echo checkedValue($_POST[$key] ?? '', 4); ?>>
                                Very Good
                            </label>

                            <label class="scale-option">
                                <input
                                    type="radio"
                                    name="<?php echo $key; ?>"
                                    value="5"
                                    <?php echo checkedValue($_POST[$key] ?? '', 5); ?>>
                                Excellent
                            </label>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

                        <div class="form-section">

                <h3>
                    Part B : Internship Report Evaluation (20%)
                </h3>

                <?php foreach ($partB as $key => $label): ?>

                    <div class="question-block">

                        <label>

                            <?php echo htmlspecialchars($label); ?>

                            <span class="weight-badge">
                                <?php echo $weightsB[$key]; ?>%
                            </span>

                        </label>

                        <div class="scale-row">

                            <label class="scale-option">
                                <input
                                    type="radio"
                                    name="<?php echo $key; ?>"
                                    value="1"
                                    required
                                    <?php echo checkedValue($_POST[$key] ?? '', 1); ?>>
                                Poor
                            </label>

                            <label class="scale-option">
                                <input
                                    type="radio"
                                    name="<?php echo $key; ?>"
                                    value="2"
                                    <?php echo checkedValue($_POST[$key] ?? '', 2); ?>>
                                Fair
                            </label>

                            <label class="scale-option">
                                <input
                                    type="radio"
                                    name="<?php echo $key; ?>"
                                    value="3"
                                    <?php echo checkedValue($_POST[$key] ?? '', 3); ?>>
                                Good
                            </label>

                            <label class="scale-option">
                                <input
                                    type="radio"
                                    name="<?php echo $key; ?>"
                                    value="4"
                                    <?php echo checkedValue($_POST[$key] ?? '', 4); ?>>
                                Very Good
                            </label>

                            <label class="scale-option">
                                <input
                                    type="radio"
                                    name="<?php echo $key; ?>"
                                    value="5"
                                    <?php echo checkedValue($_POST[$key] ?? '', 5); ?>>
                                Excellent
                            </label>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

            <div class="form-section">

                <h3>
                    Recommendation / Comments
                </h3>

                <div class="question-block">

                    <label>
                        Academic Supervisor Comments
                    </label>

                    <textarea
                        name="comments"
                        class="monitor-textarea"><?php echo htmlspecialchars($_POST['comments'] ?? ''); ?></textarea>

                </div>

            </div>

            <?php if (!$alreadySubmitted): ?>

                <div
                    class="profile-actions"
                    style="margin-top:24px;">

                    <button
                        type="button"
                        class="btn"
                        onclick="history.back();">

                        Back

                    </button>

                    <button
                        type="submit"
                        class="btn submit">

                        Submit Evaluation

                    </button>

                </div>

            <?php else: ?>

                <div
                    class="profile-actions"
                    style="margin-top:24px;">

                    <button
                        type="button"
                        class="btn"
                        onclick="history.back();">

                        Back

                    </button>

                </div>

            <?php endif; ?>

        </form>

    </div>

</div>

<?php include 'footer.php'; ?>

</body>
</html>