<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // 로그인 페이지로 리디렉션
    exit;
}

include 'db.php';  // 데이터베이스 연결 설정 파일을 포함합니다.

$reportId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

 
$query = "SELECT r.content, r.report_date, r.team_id, r.id, t.team_name, r.category_id, a.category_name ";
$query .= " FROM WRDB.task_categories a,";
$query .= " WRDB.reports r JOIN WRDB.teams t ON r.team_id = t.id WHERE r.id = :id and r.category_id = a.id";

// 보고서 데이터 가져오기
$stmt = $conn->prepare($query);
$stmt->bindParam(':id', $reportId, PDO::PARAM_INT); 
$stmt->execute();
$report = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$report) {
    echo "해당 보고서를 찾을 수 없습니다.";
    exit;
}

$reportDate = new DateTime($report['report_date']); 
$team_id = $report['team_id'];
$currentReportContent = htmlspecialchars($report['content']);

$thisWeek = $reportDate->modify('-1 days')->format('Y-m-d');
$lastWeek = $reportDate->modify('-7 days')->format('Y-m-d');

$previousReportContent = '';

$lastWeekquery = "SELECT content FROM reports WHERE team_id = :team_id AND category_id = :category_id AND report_date BETWEEN :start AND :end ORDER BY report_date ASC LIMIT 1";
// 지난 주 보고서 내용
$lastWeekStmt = $conn->prepare($lastWeekquery);
$lastWeekStmt->bindParam(':team_id', $team_id, PDO::PARAM_INT);
$lastWeekStmt->bindParam(':category_id', $report['category_id'], PDO::PARAM_INT);
$lastWeekStmt->bindParam(':start', $lastWeek);
$lastWeekStmt->bindParam(':end', $thisWeek);
$lastWeekStmt->execute();

// echo ">>지난 주 보고서 내용 lastWeekquery ==>[".$lastWeekquery."] <br>"; 
// echo ">>지난 주 보고서 내용 lastWeekquery __ team_id ==>[".$team_id."] <br>"; 
// echo ">>지난 주 보고서 내용 lastWeekquery __ category_id ==>[".$report['category_id']."] <br>"; 
// echo ">>지난 주 보고서 내용 lastWeekquery __ start ==>[".$lastWeek."] <br>"; 
// echo ">>지난 주 보고서 내용 lastWeekquery __ end ==>[".$thisWeek."] <br>"; 


if ($lastWeekData = $lastWeekStmt->fetch(PDO::FETCH_ASSOC)) {
    $previousReportContent = htmlspecialchars($lastWeekData['content']);
} else {
    $previousReportContent = "-";
}

?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>주간고서 상세 보기</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        th, td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .highlight {
            background-color: yellow;
        }
        .content-column {
            width: 50%;
            vertical-align: top;
        }
    </style>
</head>
<body>
    <h1>보고서 상세</h1>
    <table>
        <tr>
            <th>팀 이름</th>
            <td><?= $report['team_name'] ?></td>
        </tr>
        <tr>
            <th>보고 날짜</th>
            <td><?= $report['report_date'] ?></td>
        </tr>
        <tr>
            <th>업무 카테고리</th>
            <td><?= $report['category_name'] ?></td>
        </tr>
        <tr> 
            <th>전주 주간보고 내용</th> 
            <th>이번주 주간보고 내용</th>
        </tr>
        <tr>
            <td class="content-column" id="lastWeekContent"><?= htmlspecialchars_decode($previousReportContent) ?></td>
            <td class="content-column" id="thisWeekContent"><?= htmlspecialchars_decode($currentReportContent) ?></td>
        </tr>
    </table>

    <button onclick="window.location = 'edit_report.php?id=<?= $reportId ?>'">이번주 주간보고 수정</button>
    <button onclick="if(confirm('정말 삭제하시겠습니까?')) window.location='delete_report.php?id=<?= $reportId ?>'">이번주 주간보고 삭제</button>
    <button onclick="window.location = 'list_report.php'">목록으로 돌아가기</button>
    
    <script>
        // 내용 비교 및 하이라이트
        function highlightSimilarText() {
            var lastContent = document.getElementById('lastWeekContent').innerHTML;
            var thisContent = document.getElementById('thisWeekContent').innerHTML;
            var for_lastContents = lastContent.split("\n");
            var for_thisContents = thisContent.split("\n");
            var highlightedContent = thisContent; 

            // alert(">>> thisContent ==["+highlightedContent+"]");
            // alert(">>> for_thisContents.length ==["+for_thisContents.length+"]");

            for(let i = 0; i< for_thisContents.length; i++){
                if(for_lastContents[i].trim() != for_thisContents[i].trim()){
                    highlightedContent = highlightedContent.replace(for_thisContents[i], '<span class="highlight">' + for_thisContents[i] + '</span> ');
                }
                // else{
                //     alert("X_for_lastContents[i].trim() == for_thisContents[i].trim() =>["+for_lastContents[i].trim()+" == "+ for_thisContents[i].trim()+"]");
                // }
            }

            document.getElementById('thisWeekContent').innerHTML = highlightedContent;

            // alert(">>> highlightedContent(3) ==["+document.getElementById('thisWeekContent').innerHTML+"]");
        }

        highlightSimilarText();
    </script>
</body>
</html>
