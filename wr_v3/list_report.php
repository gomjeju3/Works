<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // 로그인 페이지로 리디렉션
    exit;
}

include 'db.php';

if (isset($_GET['logout'])) {
    // 세션 파괴하여 로그아웃 처리
    session_destroy();
    header("Location: login.php");
    exit;
}

$perPage = 4;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $perPage;
$search = isset($_GET['search']) ? $_GET['search'] : '';
$selectedDate = isset($_GET['date']) ? $_GET['date'] : '';
$selectedTeam = isset($_GET['team']) ? $_GET['team'] : '';

// 보고 날짜 선택용 데이터
$dateStmt = $conn->query("SELECT DISTINCT DATE_FORMAT(report_date, '%Y-%m-%d') as report_date FROM reports ORDER BY report_date DESC");
$dates = $dateStmt->fetchAll(PDO::FETCH_ASSOC);

// 팀 이름 선택용 데이터
$teamStmt = $conn->query("SELECT id, team_name FROM teams ORDER BY team_name");
$teams = $teamStmt->fetchAll(PDO::FETCH_ASSOC);

// 검색과 날짜 및 팀 필터
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


$query = " SELECT r.id, a.category_name, r.content, t.team_name, r.report_date, r.team_id";
$query .= " FROM WRDB.task_categories a, WRDB.reports r JOIN teams t ON r.team_id = t.id";
$query .= $conditions;
$query .= " and r.category_id = a.id";
$query .= " ORDER BY r.report_date DESC LIMIT :perPage OFFSET :offset";

$stmt = $conn->prepare($query);
//  echo ">>주간보고 목록 queryString ==>[".$stmt->queryString."] <br>";  // 사용한 쿼리문 출력
//  echo ">>주간보고 목록 queryString ___ perPage ==>[".$perPage."] <br>";
//  echo ">>주간보고 목록 queryString ___ offset ==>[".$offset."] <br>";
//  echo ">>주간보고 목록 queryString ___ search ==>[".$params['search']."] <br>";

$stmt->bindParam(':perPage', $perPage, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->bindParam(':search', $params['search'], PDO::PARAM_STR);
if ($selectedDate) {
    $stmt->bindParam(':report_date', $params['report_date']);
}
if ($selectedTeam) {
    $stmt->bindParam(':team_id', $params['team_id']);
}
$stmt->execute();
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get previous week's report content
$previousContentQuery = "SELECT content,id FROM reports WHERE team_id = :team_id AND DATE(report_date) = DATE_SUB(:report_date, INTERVAL 7 DAY)";
$prevContentStmt = $conn->prepare($previousContentQuery);

// 전체 보고서 수 계산
$countStmt = $conn->prepare("SELECT COUNT(*) FROM reports r $conditions");
foreach ($params as $key => &$val) {
    $countStmt->bindParam($key, $val);
}
$countStmt->execute();
$totalReports = $countStmt->fetchColumn();
$totalPages = ceil($totalReports / $perPage);
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>주간보고 목록 V3</title>
    <style>
        body { text-align: center; font-family: Arial, sans-serif; }
        table { margin: auto; width: 80%; border-collapse: collapse; }
        th, td { border: 1px solid black; padding: 8px; }
        th { background-color: #f2f2f2; }
        a { text-decoration: none; 
    </style>
</head>
<body>
    <div class="button-group" align="right">
        <a href="?logout=true"><button type="button">로그아웃</button></a>
    </div>
    <h1>주간보고 목록 V3</h1> 
    <form action="" method="get">
        <select name="team" onchange="this.form.submit()">
            <option value="">모든 팀</option>
            <?php foreach ($teams as $team): ?>
                <option value="<?= $team['id'] ?>" <?= $selectedTeam == $team['id'] ? 'selected' : '' ?>><?= htmlspecialchars($team['team_name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="date" onchange="this.form.submit()">
            <option value="">모든 날짜</option>
            <?php foreach ($dates as $date): ?>
                <option value="<?= $date['report_date'] ?>" <?= $selectedDate == $date['report_date'] ? 'selected' : '' ?>><?= $date['report_date'] ?></option>
            <?php endforeach; ?>
        </select>
        <input type="text" name="search" placeholder="검색" value="<?= htmlspecialchars($search) ?>">
        <button type="submit">검색</button>
        <a href="create_report.php"><button type="button">주간보고 입력하기</button></a>
        &nbsp;&nbsp;&nbsp;<a href="manage_task_categories.php"><button type="button">업무 카테고리 입력하기</button></a>
    </form>
    </br> 
    <table>
        <thead>
            <tr> 
                <td align="right">
                    <!-- Add an Excel download button -->
                    <form action="download_excel.php" method="get">
                        <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                        <input type="hidden" name="date" value="<?= $selectedDate ?>">
                        <input type="hidden" name="team" value="<?= $selectedTeam ?>">
                        <button type="submit">Download Excel</button>
                    </form>
                </td>
            </tr>
        </thead>
    </table>
    <br>
    <?php if (empty($reports)): ?>
        <p>데이터가 없습니다.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr> 
                    <th>팀 이름</th>
                    <th>업무 카테고리</th>
                    <th>전주 주간보고 내용</th>  
                    <th>이번주 주간보고 내용</th>  
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reports as $report): ?>
                    <?php
                    // echo ">>> team_id =>[".$report['team_id']."]<br>"; 
                    $prevContentStmt->execute(['team_id' => $report['team_id'], 'report_date' => $report['report_date']]);
                    
                    $prevContent = "-";
                    $prevId = 0;
                    $preRow = $prevContentStmt->fetch(PDO::FETCH_ASSOC);
                    if($prevContentStmt -> rowCount() > 0){
                        // $prevContent = $prevContentStmt->fetchColumn();
                        $prevContent = $preRow["content"];
                        $prevId = $preRow["id"];
                        // echo ">>> prevContent =>[".$prevContent."]<br>"; 
                        // echo ">>> prevId =>[".$prevId."]<br>"; 
                    }
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($report['team_name']) ?><br>(<?= $report['report_date'] ?>)</td>
                        <td><?= htmlspecialchars($report['category_name']) ?></td>
                        <td valign="top" align="left"><a href="view_report.php?id=<?= $prevId ?>"><?= htmlspecialchars_decode($prevContent) ?></a></td>
                        <td valign="top" align="left"><a href="view_report.php?id=<?= $report['id'] ?>"><?= htmlspecialchars_decode($report['content']) ?></a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- 페이징 링크 -->
        <div>
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>&search=<?= $search ?>&date=<?= $selectedDate ?>&team=<?= $selectedTeam ?>">이전</a>
            <?php endif; ?>
            <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page + 1 ?>&search=<?= $search ?>&date=<?= $selectedDate ?>&team=<?= $selectedTeam ?>">다음</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</body>
</html>
