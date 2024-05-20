<?php
include 'db.php';

$teamId = $_GET['team_id'] ?? 0;
if ($teamId) {
    $stmt = $conn->prepare("SELECT id, username FROM users WHERE team_id = :team_id");
    $stmt->bindParam(':team_id', $teamId, PDO::PARAM_INT);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($users as $user) {
        echo "<option value='" . htmlspecialchars($user['id']) . "'>" . htmlspecialchars($user['username']) . "</option>";
    }
}
?>
