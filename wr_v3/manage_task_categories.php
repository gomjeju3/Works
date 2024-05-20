<?php
include 'db.php'; // 데이터베이스 연결 설정 파일을 포함합니다.

$message = ''; // 사용자 피드백 메시지

// 페이지네이션 설정
$perPage = 10; // 한 페이지당 항목 수
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $perPage;

// 업무 카테고리 추가
if (isset($_POST['add'])) {
   
    $category_name = $_POST['category_name'];
    $duplication = "N";

    if (!empty($category_name)) {

        // 업무 카테고리 관리 목록 가져오기
        $find_categoryStmt = $conn->query("SELECT id, category_name FROM task_categories");
        $find_categorys = $find_categoryStmt->fetchAll();

        // 업무 카테고리 중복 등록 체크
        foreach ($find_categorys as $find_category):
            if($find_category['category_name'] == $category_name){
                // echo ">>> [".$find_category['category_name']."==".$category_name."] <br>";
                $message = "추가 실패! 등록하신 카테고리명이 중복됩니다.";
                $duplication = "Y";
                break;
            }
        endforeach; 

        if($duplication == "N"){
            $stmt = $conn->prepare("INSERT INTO task_categories (category_name) VALUES (?)");
            if ($stmt->execute([$category_name])) {
                $message = "업무 카테고리가 성공적으로 추가되었습니다.^^";
            } else {
                $message = "업무 카테고리를 추가하는 데 실패했습니다.";
            }
        }
    } else {
        $message = "추가 실패! 모든 필드를 채워주세요.";
    }
}

// 업무 카테고리 수정
if (isset($_POST['edit'])) {
    $category_id = $_POST['category_id'];
    $category_name = $_POST['category_name'];

    if (!empty($category_name) && !empty($category_id)) {
        $stmt = $conn->prepare("UPDATE task_categories SET category_name = ? WHERE id = ?");
        // echo ">>카테고리 수정 queryString ==>[".$stmt->queryString."] <br>";  // 사용한 쿼리문 출력

        if ($stmt->execute([$category_name, $category_id])) {
            $message = "카테고리가 수정되었습니다.^^";
        } else {
            $message = "카테고리 수정에 실패했습니다.";
        }
    } else {
        $message = "수정 실패! 모든 필드를 채워주세요.";
    }
}

// 업무 카테고리 삭제
if (isset($_POST['delete'])) {
    $category_id = $_POST['category_id'];
    if (!empty($category_id)) {
        $stmt = $conn->prepare("DELETE FROM task_categories WHERE id = ?");
        if ($stmt->execute([$category_id])) {
            $message = "카테고리가 삭제되었습니다.^^;";
        } else {
            $message = "카테고리 삭제에 실패했습니다.";
        }
    } else { 
        $message = "삭제 실패! 삭제할 카테고리를 지정해주세요.";
    }
}

// 카테고리 검색
$search = $_GET['search'] ?? ''; 
$searchQuery = "";

$searchQuery = " WHERE a.category_name LIKE ? ";

// 카테고리 목록 가져오기
$stmt = $conn->prepare("SELECT a.id, a.category_name FROM task_categories a $searchQuery ORDER BY a.id DESC LIMIT ? OFFSET ?");
// echo ">>카테고리 목록 queryString ==>[".$stmt->queryString."] <br>";  // 사용한 쿼리문 출력

    $stmt->bindValue(1, "%$search%", PDO::PARAM_STR);
    $stmt->bindValue(2, $perPage, PDO::PARAM_INT);
    $stmt->bindValue(3, $offset, PDO::PARAM_INT);

$stmt->execute();

$categories = $stmt->fetchAll();

// 전체 카테고리 수 계산
$countStmt = $conn->prepare("SELECT COUNT(a.id) FROM task_categories a $searchQuery");
$countStmt->execute(["%$search%"]);
$totalItems = $countStmt->fetchColumn();
$totalPages = ceil($totalItems / $perPage);
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>업무 카테고리 관리</title>
    <style>
        body { text-align: center; font-family: Arial, sans-serif; }
        table { margin: auto; width: 80%; border-collapse: collapse; }
        th, td { border: 1px solid black; padding: 8px; text-align: center; }
        th { background-color: #f2f2f2; }
        a, button { text-decoration: none; }
        form { margin-bottom: 20px; display: inline-block; }
        .search-form { text-align: left; }
    </style>
</head>
<body>
    <h1>업무 카테고리 관리</h1>
    <?php if (!empty($message)): ?>
        <table>
        <tr>
            <td style="color:red;"><p><?= $message ?></p></td>
        </tr>
        </table>
    <?php endif; ?>
    <br>

    <!-- 업무 카테고리 추가 폼 -->
    <form method="post">
        <input type="text" name="category_name" placeholder="새 카테고리 이름">
        <button type="submit" name="add">추가</button>
    </form>

    <!-- 카테고리 검색 -->
    <form method="get" class="search-form">
        <input type="text" name="search" placeholder="카테고리 검색" value="<?= htmlspecialchars($search) ?>">
        <button type="submit">검색</button>
    </form>
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="list_report.php"><button type="button">주간보고 목록</button></a>            
    <!-- 카테고리 목록 -->
    <table>
        <thead>
            <tr>
                <th>카테고리 이름</th> 
                <th>수정 / 삭제</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($categories as $category): ?>
                <tr>
                    <td><?= htmlspecialchars($category['category_name']) ?></td>
                    <td>
                        <!-- 수정 폼 -->
                        <form action="manage_task_categories.php" method="post">
                            <input type="hidden" name="category_id" value="<?= $category['id'] ?>">
                            <input type="text" name="category_name" value="<?= htmlspecialchars($category['category_name']) ?>">
                            <button type="submit" name="edit">수정</button>
                        </form>
                        <!-- 삭제 폼 -->
                        <form method="post" onsubmit="return confirm('정말 삭제하시겠습니까?');">
                            <input type="hidden" name="category_id" value="<?= $category['id'] ?>">
                            <button type="submit" name="delete">삭제</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$categories): ?>
                <tr>
                    <td colspan="3">카테고리가 없습니다.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- 페이징 -->
    <div>
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?page=<?= $i ?>&search=<?= htmlspecialchars($search) ?>&team_id=<?= $selectedTeam ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
</body>
</html>
