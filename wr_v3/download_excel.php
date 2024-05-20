<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

include 'db.php';

$search = $_GET['search'] ?? '';
$selectedDate = $_GET['date'] ?? '';
$selectedTeam = $_GET['team'] ?? '';

$conditions = " WHERE r.content LIKE :search";
$params = ['search' => '%' . $search . '%'];

if ($selectedDate) {
    $conditions .= " AND DATE(r.report_date) = :report_date";
    $params['report_date'] = $selectedDate;
}

if ($selectedTeam) {
    $conditions .= " AND r.team_id = :team_id";
    $params['team_id'] = $selectedTeam;
}

$query = "SELECT r.id, r.content, t.team_name, r.report_date FROM reports r JOIN teams t ON r.team_id = t.id $conditions ORDER BY r.report_date DESC";
$stmt = $conn->prepare($query);
$stmt->execute($params);
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setCellValue('A1', '팀 이름');
$sheet->setCellValue('B1', '보고 날짜');
$sheet->setCellValue('C1', '보고 내용');

$row = 2;
foreach ($reports as $report) {
    $sheet->setCellValue('A' . $row, $report['team_name']);
    $sheet->setCellValue('B' . $row, $report['report_date']);
    $sheet->setCellValue('C' . $row, $report['content']);
    $row++;
}

$writer = new Xlsx($spreadsheet);

// Redirect output to a client’s web browser (Xlsx)
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Report.xlsx"');
header('Cache-Control: max-age=0');

$writer->save('php://output');
exit;
?>
