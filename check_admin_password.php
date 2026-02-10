<?php
// Check admin password
$hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

// Common passwords to test
$passwords = ['admin', 'admin123', '123456', 'password', 'root', '1234', '12345', 'admin2024', 'future'];

echo "Testing admin password hash:\n";
echo "Hash: $hash\n\n";

foreach ($passwords as $password) {
    if (password_verify($password, $hash)) {
        echo "✅ FOUND: '$password' is the correct password!\n";
    } else {
        echo "❌ '$password' - incorrect\n";
    }
}

// Also check what this hash actually represents
echo "\nHash info:\n";
$info = password_get_info($hash);
echo "Algorithm: " . $info['algo'] . " (" . $info['algoName'] . ")\n";
echo "Options: " . json_encode($info['options']) . "\n";
?>
