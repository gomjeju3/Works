<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // 로그인 페이지로 리디렉션
    exit;
}

include 'db.php';  // 데이터베이스 연결 설정 파일을 포함합니다.

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $reportDate = $_POST['report_date'] ?? '';
    $teamId = $_POST['team_id'] ?? '';
    $userId = $_POST['user_id'] ?? '';
    $content = $_POST['content'] ?? '';

    if (empty($reportDate) || empty($teamId) || empty($userId) || empty($content)) {
        echo "<script>alert('모든 필드를 채워주세요.'); history.back();</script>";
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO reports (report_date, team_id, user_id, content) VALUES (?, ?, ?, ?)");
    $stmt->execute([$reportDate, $teamId, $userId, $content]);
    header("Location: list_report.php");
    exit();
}

$teams = $conn->query("SELECT id, team_name FROM teams")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>주간보고 등록</title>
    <script src="https://cdn.ckeditor.com/4.16.0/standard/ckeditor.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <style>
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid black; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>주간보고 등록</h1>
    <form method="post" onsubmit="return validateForm()">
        <table>
            <tr>
                <th>주간보고 날짜</th>
                <td>
                    <input type="date" id="report_date" name="report_date" required>
                    <button type="button" onclick="loadLastWeekReport()">전주 주간보고 불러오기</button>
                </td>
            </tr>
            <tr>
                <th>팀 선택</th>
                <td>
                    <select id="team_id" name="team_id" required onchange="updateUsers(this.value)">
                        <option value="">팀을 선택하세요</option>
                        <?php foreach ($teams as $team): ?>
                            <option value="<?= $team['id'] ?>"><?= htmlspecialchars($team['team_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th>사용자 선택</th>
                <td>
                    <select id="user_id" name="user_id" required>
                        <option value="">사용자를 선택하세요</option>
                        <!-- 동적 사용자 목록 -->
                    </select>
                </td>
            </tr>
            <tr>
                <th>보고 내용</th>
                <td><textarea id="content" name="content" required></textarea></td>
            </tr>
        </table>
        <button type="submit">보고서 제출</button>
        <button type="button" onclick="location.href='list_report.php'">목록으로 돌아가기</button>
    </form>
    <script>
        CKEDITOR.replace('content');

        function loadLastWeekReport() {
            var reportDate = document.getElementById('report_date').value;
            var teamId = document.getElementById('team_id').value;
            if (!teamId) {
                alert("팀을 먼저 선택하세요.");
                return;
            }
            // if (!reportDate) {
            //     alert("날짜를 선택하세요.");
            //     return;
            // }

            $.ajax({
                url: 'fetch_last_week_report.php',
                type: 'GET',
                data: { team_id: teamId, report_date: reportDate },
                success: function(response) {
                    CKEDITOR.instances['content'].setData(response);
                },
                error: function() {
                    alert("지난 주 보고서를 불러오는 데 실패했습니다.");
                }
            });
        }
    
        function validateForm() {
            var reportDate = document.getElementById('report_date').value;
            var teamId = document.getElementById('team_id').value;
            var userId = document.getElementById('user_id').value;
            var content = CKEDITOR.instances['content'].getData();

            if (!reportDate || !teamId || !userId || !content) {
                alert("모든 필드를 채워주세요.");
                return false;
            }
            return true;
        }

        function updateUsers(teamId) {
            $.ajax({
                url: 'fetch_users.php', // 사용자 목록을 가져올 PHP 파일
                type: 'GET',
                data: { team_id: teamId },
                success: function(response) {
                    $('#user_id').html(response);
                }
            });
        }
    </script>
</body>
</html>
