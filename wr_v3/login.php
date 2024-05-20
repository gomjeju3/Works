<?php
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header("Location: list_report.php"); // 로그인 성공 시 주간보고 목록 페이지로 리디렉션
            exit;
        } else {
            $error = 'Invalid login credentials.';
        }
    } else {
        $error = 'Please enter username and password.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
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
        input, button {
            width: 100%;
            padding: 8px;
        }
        button {
            cursor: pointer;
        }
    </style>
</head>
<body>
    <form action="login.php" method="post">
        <table>
            <tr>
                <th colspan="2"><h1>Login</h1></th>
            </tr>
            <?php if (!empty($error)): ?>
            <tr>
                <td colspan="2"><p><?= $error ?></p></td>
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
                <td colspan="2"><button type="submit">Login</button></td>
            </tr>
            <tr>
                <td colspan="2">
                    <a href="add_user.php"><button type="button">사용자 추가</button></a>
                </td>
            </tr>
        </table>
    </form>
</body>
</html>
