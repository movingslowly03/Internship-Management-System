<?php
session_start();
include 'db_connect.php';

if (!isset($_GET['id'])) {
    exit();
}

$id = (int)$_GET['id'];

$stmt = $conn->prepare("
    SELECT fileName, reportFile
    FROM report
    WHERE reportID = ?
");

$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {

    header("Content-Type: application/pdf");
    header("Content-Disposition: inline; filename=\"" . $row['fileName'] . "\"");

    echo $row['reportFile'];
}

$stmt->close();
?>