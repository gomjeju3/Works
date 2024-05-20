<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // 로그인 페이지로 리디렉션
    exit;
}

include 'db.php';  // 데이터베이스 연결 설정 파일을 포함합니다.

// 요청에서 ID 가져오기
$reportId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $content = $_POST['content'];  // 수정된 내용을 받아옵니다.
    $stmt = $conn->prepare("UPDATE reports SET content = :content WHERE id = :id");
    $stmt->bindParam(':content', $content);
    $stmt->bindParam(':id', $reportId, PDO::PARAM_INT);
    $stmt->execute();
    header("Location: view_report.php?id=$reportId");  // 수정 후 보고서 상세 페이지로 리디렉션
    exit;
}

// 현재 보고서 데이터 가져오기
$stmt = $conn->prepare("SELECT content, report_date, team_id FROM reports WHERE id = :id");
$stmt->bindParam(':id', $reportId, PDO::PARAM_INT);
$stmt->execute();
$report = $stmt->fetch(PDO::FETCH_ASSOC);

// 팀 이름 가져오기
$teamName = '';
if ($report) {
    $teamStmt = $conn->prepare("SELECT team_name FROM teams WHERE id = :team_id");
    $teamStmt->bindParam(':team_id', $report['team_id'], PDO::PARAM_INT);
    $teamStmt->execute();
    $team = $teamStmt->fetch(PDO::FETCH_ASSOC);
    $teamName = $team['team_name'];
}
?>

<!DOCTYPE html>
<html lang="ko"> 
<head>
    <meta charset="UTF-8">
    <title>보고서 수정</title>
    <!-- CKEditor 편집기 스크립트 추가 -->
    <script src="https://cdn.ckeditor.com/4.16.0/standard/ckeditor.js"></script>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <h1>보고서 수정</h1>
    <form method="post">
        <table>
            <tr>
                <th>팀 이름</th>
                <td><strong><?= htmlspecialchars($teamName) ?></strong></td>
            </tr>
            <tr>
                <th>보고 날짜</th>
                <td><strong><?= htmlspecialchars($report['report_date']) ?></strong></td>
            </tr>
            <tr>
                <th>보고 내용</th>
                <td><textarea id="content" name="content" required><?= htmlspecialchars($report['content']) ?></textarea></td>
            </tr>
        </table>
        <script>
            CKEDITOR.replace('content');
        </script>
        <button type="submit">수정 완료</button>
        <button type="button" onclick="location.href='view_report.php?id=<?= $reportId ?>'">보고서 상세 보기</button>
    </form>
</body>
</html>
