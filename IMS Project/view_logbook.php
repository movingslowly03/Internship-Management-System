<?php
session_start();
include 'db_connect.php';

if (!isset($_GET['id'])) {
    die("Invalid logbook.");
}

$logID = (int)$_GET['id'];

$stmt = $conn->prepare("
    SELECT fileName, logbookFile
    FROM logbook
    WHERE logID = ?
");

$stmt->bind_param("i", $logID);
$stmt->execute();

$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {

    header("Content-Type: application/pdf");
    header(
        "Content-Disposition: inline; filename=\"" .
        $row['fileName'] .
        "\""
    );

    echo $row['logbookFile'];
}

$stmt->close();
?>