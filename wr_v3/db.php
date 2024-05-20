<?php

$host = 'localhost';
$dbname = 'WRDB';
$username = 'root';
$password = 'wook4564';
$conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function getReportsByTeamAndWeek($year, $week) {
    global $conn;
    $reports = [];

    // 이번 주 날짜 계산
    $thisWeek = new DateTime();
    $thisWeek->setISODate($year, $week);
    $thisWeekStart = $thisWeek->format('Y-m-d');
    $thisWeekEnd = $thisWeek->modify('+6 days')->format('Y-m-d');

    // 저번 주 날짜 계산
    $lastWeek = new DateTime();
    $lastWeek->setISODate($year, $week-1);
    $lastWeekStart = $lastWeek->format('Y-m-d');
    $lastWeekEnd = $lastWeek->modify('+6 days')->format('Y-m-d');

    // 팀별로 데이터 검색
    $stmt = $conn->prepare("
        SELECT r.content, t.team_name, r.report_date 
        FROM reports r 
        JOIN teams t ON r.team_id = t.id 
        WHERE (r.report_date BETWEEN :lastWeekStart AND :thisWeekEnd) AND t.team_name IN ('A팀', 'B팀', 'C팀')
        ORDER BY t.team_name ASC, r.report_date ASC
    ");
    $stmt->bindParam(':lastWeekStart', $lastWeekStart);
    $stmt->bindParam(':thisWeekEnd', $thisWeekEnd);
    $stmt->execute();

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $weekIndicator = ($row['report_date'] >= $thisWeekStart) ? 'this_week' : 'last_week';
        $reports[$row['team_name']][$weekIndicator][] = $row['content'];
    }

    return $reports;
}
?>