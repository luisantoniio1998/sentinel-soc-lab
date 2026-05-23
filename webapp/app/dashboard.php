<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            padding: 20px;
            border-radius: 4px;
            color: #155724;
        }
    </style>
</head>
<body>
    <div class="success">
        <h1>Login Successful!</h1>
        <p><strong>Welcome, <?php echo htmlspecialchars($_SESSION['user']); ?>!</strong></p>
        <p>Role: <?php echo htmlspecialchars($_SESSION['role']); ?></p>
        <p>This page demonstrates a successful authentication bypass.</p>
    </div>
    <p><a href="logout.php">Logout</a> | <a href="index.php">Home</a></p>
</body>
</html>
