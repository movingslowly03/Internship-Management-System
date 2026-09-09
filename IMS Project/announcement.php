<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$adminId = $_SESSION['admin_id'] ?? '';
$error = '';
$success = '';

/* DELETE ANNOUNCEMENT */
if (isset($_GET['delete'])) {
    $announcementID = (int)$_GET['delete'];

    $stmt = $conn->prepare("DELETE FROM announcement WHERE announcementID = ?");
    $stmt->bind_param("i", $announcementID);
    $stmt->execute();
    $stmt->close();

    header("Location: announcement.php?deleted=1");
    exit();
}

if (isset($_GET['deleted'])) {
    $success = "Announcement deleted successfully.";
}

/* POST NEW ANNOUNCEMENT */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $target_role = trim($_POST['target_role'] ?? 'all');

    if ($title === '' || $message === '') {
        $error = "Please fill in the title and message.";
    } else {
        $stmt = $conn->prepare("
            INSERT INTO announcement (title, message, target_role, posted_by)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("ssss", $title, $message, $target_role, $adminId);

        if ($stmt->execute()) {
            $stmt->close();
            header("Location: announcement.php?posted=1");
            exit();
        } else {
            $error = "Failed to post announcement.";
            $stmt->close();
        }
    }
}

if (isset($_GET['posted'])) {
    $success = "Announcement posted successfully.";
}

/* LOAD ANNOUNCEMENTS */
$announcements = [];

$stmt = $conn->prepare("
    SELECT announcementID, title, message, target_role, posted_by, is_active, posted_at
    FROM announcement
    ORDER BY posted_at DESC, announcementID DESC
");
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $announcements[] = $row;
}

$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Announcements</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body class="app-bg">

<?php include 'header.php'; ?>
<?php include 'sidebar.php'; ?>

<div class="main">

    <div class="card">
        <h2>Announcements</h2>
        <p class="subtitle">Post announcements for students and supervisors.</p>

        <?php if (!empty($error)): ?>
            <p style="color:#ef4444;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <p style="color:#22c55e;"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" required>
            </div>

            <div class="form-group">
                <label>Message</label>
                <textarea name="message" rows="5" required></textarea>
            </div>

            <div class="form-group">
    <label>Target Audience</label>

    <div class="select-wrapper">
        <select name="target_role" required class="styled-select">
            <option value="all">All Users</option>
            <option value="student">Students Only</option>
            <option value="supervisor">Supervisors Only</option>
        </select>
    </div>
</div>

            

            <div class="form-actions">
                <button type="submit" class="btn submit">Post Announcement</button>
            </div>
        </form>
    </div>

    <div class="card" style="margin-top:20px;">
        <h3>Posted Announcements</h3>

        <div class="report-list">
            <?php if (empty($announcements)): ?>
                <p>No announcements yet.</p>
            <?php else: ?>
                <?php foreach ($announcements as $announcement): ?>
                    <div class="report-item">

                        <div class="report-info">
                            <h4><?php echo htmlspecialchars($announcement['title']); ?></h4>
                            <p><?php echo nl2br(htmlspecialchars($announcement['message'])); ?></p>
                            <p>
                                Posted on
                                <?php echo date("d M Y", strtotime($announcement['posted_at'])); ?>
                            </p>
                        </div>

                        <div class="report-actions">
                            <span class="mini-sub">
                                <?php echo !empty($announcement['is_active']) ? 'Active' : 'Hidden'; ?>
                            </span>

                            <a href="announcement.php?delete=<?php echo (int)$announcement['announcementID']; ?>"
                               class="btn delete small"
                               onclick="return confirm('Delete this announcement?');">
                                Delete
                            </a>
                        </div>

                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php include 'footer.php'; ?>

</body>
</html>