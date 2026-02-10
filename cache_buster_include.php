<?php
// FUTURE AUTOMOTIVE - Cache Buster
// معطل تحديث الكاش

// إضافة معلمات تحديث الكاش إلى جميع الروابط
function addCacheBuster($url) {
    $timestamp = date('YmdHis');
    $separator = strpos($url, '?') !== false ? '&' : '?';
    return $url . $separator . 'v=' . $timestamp;
}

// إضافة رابط بمعلمات تحديث الكاش
echo "<script>
// Force cache refresh
(function() {
    // Add cache buster to all CSS and JS files
    const links = document.querySelectorAll('link[rel=\"stylesheet\"], script[src]');
    links.forEach(link => {
        const href = link.href || link.src;
        if (href) {
            const separator = href.includes('?') ? '&' : '?';
            const newHref = href + separator + 'v=<?php echo date('YmdHis'); ?>';
            if (link.href) link.href = newHref;
            if (link.src) link.src = newHref;
        }
    });
    
    // Force reload if needed
    if (performance.navigation.type === 1) {
        // Page was reloaded
        console.log('Page reloaded');
    }
    
    // Clear any potential caches
    if ('caches' in window) {
        caches.keys().then(function(names) {
            names.forEach(function(name) {
                caches.delete(name);
            });
        });
    }
    
    console.log('Cache buster applied at <?php echo date('Y-m-d H:i:s'); ?>');
})();
</script>";

// إضافة وسم meta لمنع الكاش
echo "<meta http-equiv='Cache-Control' content='no-cache, no-store, must-revalidate'>
<meta http-equiv='Pragma' content='no-cache'>
<meta http-equiv='Expires' content='0'>";

// طباعة معلومات التحديث
echo "<!-- Cache Buster: " . date('Y-m-d H:i:s') . " -->";
?>
