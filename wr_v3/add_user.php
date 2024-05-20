<?php
include 'db.php';

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $teamId = $_POST['team_id'] ?? '';

    if (!empty($username) && !empty($password) && !empty($teamId)) {
        // 비밀번호 해시 처리
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // 사용자 추가 쿼리
        $stmt = $conn->prepare("INSERT INTO users (username, password, team_id) VALUES (:username, :password, :team_id)");
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $passwordHash);
        $stmt->bindParam(':team_id', $teamId);

        if ($stmt->execute()) {
            // 성공 메시지를 alert로 표시 후 login.php로 리디렉션
            echo "<script>alert('사용자 등록이 완료 되었습니다.'); window.location = 'login.php';</script>";
            exit;
        } else {
            $message = 'Error adding user.';
        }
    } else {
        $message = 'Please fill all fields.';
    }
}

// 팀 목록 가져오기
$teamsStmt = $conn->query("SELECT id, team_name FROM teams");
$teams = $teamsStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add User</title>
    <style>
        body {
            text-align: center;
            font-family: Arial, sans-serif;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
        }
        table {
            border-collapse: collapse;
            width: 300px; /* Adjust the table width as needed */
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        input, select, button {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <table>
        <form action="add_user.php" method="post">
            <tr>
                <th colspan="2"><h1>Add User</h1></th>
            </tr>
            <?php if (!empty($message)): ?>
            <tr>
                <td colspan="2"><p><?= $message ?></p></td>
            </tr>
            <?php endif; ?>
            <tr>
                <th><label for="username">Username:</label></th>
                <td><input type="text" id="username" name="username" required></td>
            </tr>
            <tr>
                <th><label for="password">Password:</label></th>
                <td><input type="password" id="password" name="password" required></td>
            </tr>
            <tr>
                <th><label for="team_id">Team:</label></th>
                <td>
                    <select id="team_id" name="team_id" required>
                        <option value="">Select a team</option>
                        <?php foreach ($teams as $team): ?>
                            <option value="<?= $team['id'] ?>"><?= htmlspecialchars($team['team_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td colspan="2"><button type="submit">Add User</button></td>
            </tr>
        </form>
    </table>
</body>
</html>
