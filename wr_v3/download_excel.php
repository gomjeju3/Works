<?php
require_once 'PHPExcel/Classes/PHPExcel.php'; // Adjust the path as necessary
include 'db.php';

// Retrieve the parameters from the GET request
$search = $_GET['search'] ?? '';
$selectedDate = $_GET['date'] ?? '';
$selectedTeam = $_GET['team'] ?? '';

// Set up the conditions for the query
$conditions = " WHERE r.content LIKE ?";
$params = ['%' . $search . '%'];

if ($selectedDate) {
    $conditions .= " AND DATE(r.report_date) = ?";
    $params[] = $selectedDate;
}

if ($selectedTeam) {
    $conditions .= " AND r.team_id = ?";
    $params[] = $selectedTeam;
}

$query = "SELECT r.id, a.category_name, r.content, t.team_name, r.report_date, r.team_id FROM WRDB.task_categories a, WRDB.reports r JOIN teams t ON r.team_id = t.id $conditions AND r.category_id = a.id ORDER BY r.report_date DESC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

$objPHPExcel = new PHPExcel();
$objPHPExcel->setActiveSheetIndex(0);
$sheet = $objPHPExcel->getActiveSheet();

// Get previous week's report content
$previousContentQuery = "SELECT content,id FROM reports WHERE team_id = :team_id AND DATE(report_date) = DATE_SUB(:report_date, INTERVAL 7 DAY)";
$prevContentStmt = $conn->prepare($previousContentQuery);

// Set headers for the Excel document
$sheet->setCellValue('A1', '팀 이름');
$sheet->setCellValue('B1', '업무 카테고리');
$sheet->setCellValue('C1', '전주 주간보고 내용');
$sheet->setCellValue('D1', '이번주 주간보고 내용');

// Fill data
$rowCount = 2;
foreach ($reports as $report) {
    $sheet->setCellValue('A' . $rowCount, $report['team_name']);
    $sheet->setCellValue('B' . $rowCount, $report['category_name']);

    // Get previous week's report content
    $prevContentStmt->execute(['team_id' => $report['team_id'], 'report_date' => $report['report_date']]);
    $prevContent = "-";
    $prevId = 0;
    $preRow = $prevContentStmt->fetch(PDO::FETCH_ASSOC);
    if($prevContentStmt -> rowCount() > 0){
        $prevContent = $preRow["content"];
        $prevId = $preRow["id"];
    }

    $cleanContentLastWeek = htmlspecialchars(strip_tags(str_replace(["<br>", "<br/>", "<br />"], "\n", $prevContent)));
    $cleanContentThisWeek = htmlspecialchars(strip_tags(str_replace(["<br>", "<br/>", "<br />"], "\n", $report['content'])));

    $sheet->setCellValue('C' . $rowCount, $cleanContentLastWeek); // Assuming content from last week
    $sheet->setCellValue('D' . $rowCount, $cleanContentThisWeek); // Assuming content from this week
    $rowCount++;
}

$filename = "주간보고_".$report['report_date'].".xlsx";

// Set the headers to download the file
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename='.$filename);
header('Cache-Control: max-age=0');

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
$objWriter->save('php://output');

// Excel 다운로드 직후 사용자를 list_report.php로 리디렉션
$queryString = http_build_query([
    'search' => $search,
    'date' => $selectedDate,
    'team' => $selectedTeam,
]);

header("Location: list_report.php?" . $queryString);
exit;
?>