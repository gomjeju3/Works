<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // 로그인 페이지로 리디렉션
    exit;
}

include 'db.php';  // 데이터베이스 연결 설정 파일을 포함합니다.

if (isset($_GET['id'])) {
    $reportId = (int)$_GET['id'];

    // 보고서 삭제 쿼리 실행
    $stmt = $conn->prepare("DELETE FROM reports WHERE id = :id");
    $stmt->bindParam(':id', $reportId, PDO::PARAM_INT);
    $stmt->execute();

    // 성공적으로 삭제된 경우, 보고서 목록 페이지로 리디렉션
    if ($stmt->rowCount() > 0) {
        header("Location: list_report.php");
        exit();
    } else {
        echo "<script>alert('보고서 삭제에 실패했습니다.'); history.back();</script>";
    }
} else {
    // ID가 제공되지 않은 경우 경고 메시지와 함께 이전 페이지로
    echo "<script>alert('잘못된 접근입니다.'); history.back();</script>";
}
?>
