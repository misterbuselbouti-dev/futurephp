<!DOCTYPE html>
<html>
<head>
    <title>Check Admin Password</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .result { margin: 10px 0; padding: 10px; border-radius: 5px; }
        .match { background-color: #d4edda; color: #155724; }
        .no-match { background-color: #f8d7da; color: #721c24; }
        .info { background-color: #d1ecf1; color: #0c5460; }
    </style>
</head>
<body>
    <h1>Admin Password Check</h1>
    
    <?php
    $hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
    
    // Common passwords to test
    $passwords = ['admin', 'admin123', '123456', 'password', 'root', '1234', '12345', 'admin2024', 'future', 'test', 'user'];
    
    echo "<div class='info'><strong>Hash:</strong> $hash</div>";
    echo "<div class='info'><strong>Testing passwords:</strong></div>";
    
    foreach ($passwords as $password) {
        if (password_verify($password, $hash)) {
            echo "<div class='result match'>✅ <strong>FOUND:</strong> '$password' is the correct password!</div>";
        } else {
            echo "<div class='result no-match'>❌ '$password' - incorrect</div>";
        }
    }
    
    // Hash info
    $info = password_get_info($hash);
    echo "<div class='info'><strong>Hash Info:</strong><br>";
    echo "Algorithm: " . $info['algo'] . " (" . $info['algoName'] . ")<br>";
    echo "Options: " . json_encode($info['options']) . "</div>";
    ?>
    
    <hr>
    <h2>Create New Admin Password</h2>
    <form method="post">
        <label>New Password: <input type="text" name="new_password" required></label><br><br>
        <input type="submit" value="Generate Hash">
    </form>
    
    <?php
    if ($_POST['new_password']) {
        $new_hash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        echo "<div class='info'><strong>New Hash for '{$_POST['new_password']}':</strong><br>$new_hash</div>";
    }
    ?>
</body>
</html>
