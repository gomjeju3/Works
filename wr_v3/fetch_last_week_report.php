<?php
include 'db.php';

$teamId = isset($_GET['team_id']) ? (int)$_GET['team_id'] : 0;
//$lastWeekDate = (new DateTime())->modify('-7 days')->format('Y-m-d');

//echo ">> teamId == $teamId <br>";
//echo ">> lastWeekDate == $lastWeekDate <br>";

$stmt = $conn->prepare("SELECT content FROM reports WHERE team_id = :team_id ORDER BY report_date DESC LIMIT 1");
$stmt->bindParam(':team_id', $teamId, PDO::PARAM_INT);
$stmt->execute();

$report = $stmt->fetch(PDO::FETCH_ASSOC);
echo $report ? $report['content'] : "지난 주 보고서가 없습니다.";
?>