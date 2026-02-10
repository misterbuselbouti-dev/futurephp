<?php
// FUTURE AUTOMOTIVE - Simple Theme Updater
// Update all pages to use the simple theme

echo "<h1>🎨 تحديث التيم البسيط لجميع الصفحات</h1>";
echo "<p>جاري تحديث جميع الصفحات لاستخدام التيم البسيط والجميل...</p>";

// Get all PHP files
$directory = __DIR__;
$phpFiles = [];

// Recursive directory scan
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $phpFiles[] = $file->getPathname();
    }
}

$totalFiles = count($phpFiles);
$updatedFiles = 0;
$skippedFiles = 0;

echo "<h2>📊 الإحصائيات</h2>";
echo "<p>إجمالي ملفات PHP: $totalFiles</p>";

// Files to update (exclude system files)
$excludePatterns = [
    'config.php',
    'includes/functions.php',
    'api/',
    'sql/',
    'theme_updater.php',
    'simple_theme_updater.php',
    'fix_',
    'complete_',
    'ultimate_',
    'final_'
];

foreach ($phpFiles as $file) {
    $relativePath = str_replace($directory . '/', '', $file);
    
    // Skip excluded files
    $shouldSkip = false;
    foreach ($excludePatterns as $pattern) {
        if (strpos($relativePath, $pattern) !== false) {
            $shouldSkip = true;
            break;
        }
    }
    
    if ($shouldSkip) {
        $skippedFiles++;
        continue;
    }
    
    $content = file_get_contents($file);
    $originalContent = $content;
    $hasChanges = false;
    
    // Check if file has CSS includes
    if (strpos($content, 'href="assets/css/') !== false || 
        strpos($content, "href='assets/css/") !== false) {
        
        // Replace old CSS with simple theme
        $content = preg_replace('/<link[^>]*href=["\']assets\/css\/[^"\']*["\'][^>]*>/i', 
            '<link rel="stylesheet" href="assets/css/simple-theme.css">', $content);
        
        // Add simple theme if no CSS found
        if (strpos($content, 'simple-theme.css') === false && 
            (strpos($content, 'bootstrap.min.css') !== false || 
             strpos($content, 'font-awesome') !== false)) {
            
            // Find position after bootstrap CSS
            $bootstrapPos = strpos($content, 'bootstrap.min.css');
            if ($bootstrapPos !== false) {
                $insertPos = strpos($content, '>', $bootstrapPos) + 1;
                
                $simpleCSS = "\n    <link rel=\"stylesheet\" href=\"assets/css/simple-theme.css\">";
                
                $content = substr($content, 0, $insertPos) . $simpleCSS . substr($content, $insertPos);
                $hasChanges = true;
            }
        }
        
        // Replace old header with simple header
        if (strpos($content, "include 'includes/header.php';") !== false) {
            $content = str_replace(
                "include 'includes/header.php';",
                "include 'includes/header_simple.php';",
                $content
            );
            $hasChanges = true;
        }
        
        // Replace old header variations
        if (strpos($content, "include 'includes/header_iso.php';") !== false) {
            $content = str_replace(
                "include 'includes/header_iso.php';",
                "include 'includes/header_simple.php';",
                $content
            );
            $hasChanges = true;
        }
        
        // Only save if changes were made
        if ($content !== $originalContent) {
            file_put_contents($file, $content);
            $updatedFiles++;
            echo "<p style='color: green;'>✅ تم التحديث: $relativePath</p>";
        }
    } else {
        $skippedFiles++;
    }
}

echo "<h2>📈 النتائج</h2>";
echo "<p style='color: green;'>✅ الملفات المحدثة: $updatedFiles</p>";
echo "<p style='color: orange;'>⏭️ الملفات المتجاوزة: $skippedFiles</p>";

// Make dashboard_simple.php the default
echo "<h2>🏠 جعل dashboard_simple.php الصفحة الرئيسية</h2>";

// Check if index.php exists
if (file_exists('index.php')) {
    $indexContent = file_get_contents('index.php');
    
    // Check if it's a redirect or includes dashboard
    if (strpos($indexContent, 'dashboard.php') !== false || 
        strpos($indexContent, 'dashboard_iso.php') !== false) {
        
        // Replace with dashboard_simple.php
        $indexContent = str_replace('dashboard.php', 'dashboard_simple.php', $indexContent);
        $indexContent = str_replace('dashboard_iso.php', 'dashboard_simple.php', $indexContent);
        
        file_put_contents('index.php', $indexContent);
        echo "<p style='color: green;'>✅ تم تحديث index.php لاستخدام dashboard_simple.php</p>";
    }
} else {
    // Create new index.php that redirects to dashboard_simple.php
    $indexContent = '<?php
// FUTURE AUTOMOTIVE - Simple Theme Redirect
header("Location: dashboard_simple.php");
exit();
?>';
    
    file_put_contents('index.php', $indexContent);
    echo "<p style='color: green;'>✅ تم إنشاء index.php جديد يعيد التوجيه إلى dashboard_simple.php</p>";
}

// Create a simple theme switcher
echo "<h2>🔄 إنشاء مفتاح تبديل التيم</h2>";

$themeSwitcher = '<?php
// FUTURE AUTOMOTIVE - Theme Switcher
session_start();

// Set theme preference
if (isset($_GET["theme"])) {
    $_SESSION["theme"] = $_GET["theme"];
    $referer = $_SERVER["HTTP_REFERER"] ?? "dashboard_simple.php";
    header("Location: $referer");
    exit();
}

// Get current theme
$current_theme = $_SESSION["theme"] ?? "simple";

// Switch theme
$new_theme = ($current_theme === "simple") ? "iso" : "simple";
$_SESSION["theme"] = $new_theme;

$referer = $_SERVER["HTTP_REFERER"] ?? "dashboard_simple.php";
header("Location: $referer");
exit();
?>';

file_put_contents('theme_switcher.php', $themeSwitcher);
echo "<p style='color: green;'>✅ تم إنشاء theme_switcher.php</p>";

// Add theme switcher button to header_simple.php
$headerContent = file_get_contents('includes/header_simple.php');

if (strpos($headerContent, 'theme-switcher') === false) {
    $themeButton = '
        <!-- Theme Switcher -->
        <div class="theme-switcher">
            <a href="theme_switcher.php" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-palette"></i>
                <span>تيم</span>
            </a>
        </div>';
    
    // Add before user menu
    $headerContent = str_replace(
        '<!-- User Menu -->',
        $themeButton . "\n        <!-- User Menu -->",
        $headerContent
    );
    
    file_put_contents('includes/header_simple.php', $headerContent);
    echo "<p style='color: green;'>✅ تم إضافة مفتاح تبديل التيم للـ header</p>";
}

echo "<h2>🎉 اكتمل التحديث بنجاح!</h2>";
echo "<h3>📋 ما تم إنجازه:</h3>";
echo "<ul>";
echo "<li>✅ تحديث $updatedFiles صفحة لاستخدام التيم البسيط</li>";
echo "<li>✅ جعل dashboard_simple.php الصفحة الرئيسية</li>";
echo "<li>✅ إنشاء مفتاح تبديل التيم</li>";
echo "<li>✅ إضافة زر تبديل التيم في الهيدر</li>";
echo "</ul>";

echo "<h3>🚀 كيفية الاستخدام:</h3>";
echo "<ol>";
echo "<li>اذهب إلى <a href=\"dashboard_simple.php\">dashboard_simple.php</a> لرؤية التيم البسيط</li>";
echo "<li>استخدم زر \"تيم\" في الهيدر للتبديل بين التيمات</li>";
echo "<li>الصفحة الرئيسية الآن هي dashboard_simple.php</li>";
echo "</ol>";

echo "<h3>🎨 التيمات المتوفرة:</h3>";
echo "<ul>";
echo "<li><strong>تيم بسيط:</strong> ألوان واضحة، تصميم نظيف (الافتراضي)</li>";
echo "<li><strong>تيم ISO:</strong> أزرق فاتح، احترافي</li>";
echo "</ul>";

echo "<p style='color: green; font-weight: bold;'>🎯 التيم البسيط الآن هو الافتراضي لجميع الصفحات!</p>";
?>
