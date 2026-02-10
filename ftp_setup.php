<?php
// FUTURE AUTOMOTIVE - FTP Configuration Setup
// إعداد إعدادات FTP

echo "<h2>⚙️ إعداد FTP للنشر التلقائي</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // حفظ الإعدادات
    $config = [
        'host' => $_POST['ftp_host'] ?? '',
        'username' => $_POST['ftp_username'] ?? '',
        'password' => $_POST['ftp_password'] ?? '',
        'port' => (int)($_POST['ftp_port'] ?? 21),
        'remote_path' => $_POST['remote_path'] ?? '/public_html/futureautomotive'
    ];
    
    // إنشاء ملف الإعدادات
    $config_content = "<?php\n";
    $config_content .= "// FTP Configuration for Auto Deploy\n";
    $config_content .= "return " . var_export($config, true) . ";\n";
    
    file_put_contents(__DIR__ . '/ftp_config.php', $config_content);
    
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>✅ تم حفظ الإعدادات بنجاح!</h3>";
    echo "<p>يمكنك الآن استخدام النشر التلقائي.</p>";
    echo "<a href='auto_deploy_ftp.php' class='btn' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🚀 ابدأ النشر التلقائي</a>";
    echo "</div>";
    
} else {
    echo "<div style='max-width: 600px; margin: 0 auto;'>";
    echo "<form method='POST' style='background: #f8f9fa; padding: 30px; border-radius: 10px;'>";
    
    echo "<h3 style='margin-bottom: 20px;'>📝 إعدادات FTP Hostinger</h3>";
    
    echo "<div style='margin-bottom: 15px;'>";
    echo "<label style='display: block; margin-bottom: 5px; font-weight: bold;'>🌐 FTP Host:</label>";
    echo "<input type='text' name='ftp_host' placeholder='ftp.your-domain.com' required style='width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;'>";
    echo "</div>";
    
    echo "<div style='margin-bottom: 15px;'>";
    echo "<label style='display: block; margin-bottom: 5px; font-weight: bold;'>👤 Username:</label>";
    echo "<input type='text' name='ftp_username' placeholder='your_username' required style='width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;'>";
    echo "</div>";
    
    echo "<div style='margin-bottom: 15px;'>";
    echo "<label style='display: block; margin-bottom: 5px; font-weight: bold;'>🔐 Password:</label>";
    echo "<input type='password' name='ftp_password' placeholder='your_password' required style='width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;'>";
    echo "</div>";
    
    echo "<div style='margin-bottom: 15px;'>";
    echo "<label style='display: block; margin-bottom: 5px; font-weight: bold;'>🔌 Port:</label>";
    echo "<input type='number' name='ftp_port' value='21' style='width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;'>";
    echo "</div>";
    
    echo "<div style='margin-bottom: 20px;'>";
    echo "<label style='display: block; margin-bottom: 5px; font-weight: bold;'>📁 Remote Path:</label>";
    echo "<input type='text' name='remote_path' value='/public_html/futureautomotive' style='width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;'>";
    echo "</div>";
    
    echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px; margin-bottom: 20px;'>";
    echo "<h4>📍 كيف تجد معلومات FTP في Hostinger:</h4>";
    echo "<ol>";
    echo "<li>سجل دخول إلى لوحة تحكم Hostinger</li>";
    echo "<li>اذهب إلى <strong>Hosting</strong> → <strong>Manage</strong></li>";
    echo "<li>ابحث عن <strong>FTP Accounts</strong></li>";
    echo "<li>انسخ معلومات FTP الرئيسية</li>";
    echo "<li>استخدم هذه المعلومات في النموذج أعلاه</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<button type='submit' style='background: #28a745; color: white; padding: 12px 30px; border: none; border-radius: 5px; font-weight: bold; cursor: pointer;'>💾 حفظ الإعدادات</button>";
    echo "</form>";
    echo "</div>";
}
?>
