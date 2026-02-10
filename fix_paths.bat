@echo off
REM Fix paths in all new PHP files

echo 🔧 Fixing paths in PHP files...

REM Fix admin_breakdowns.php
if exist "admin\admin_breakdowns.php" (
    echo Fixing admin\admin_breakdowns.php...
    powershell -Command "(Get-Content 'admin\admin_breakdowns.php') -replace 'href=\"admin_breakdown_view.php', 'href=\"admin_breakdown_view.php\"'"
    powershell -Command "(Get-Content 'admin\admin_breakdowns.php') -replace 'href=\"admin_breakdowns.php', 'href=\"admin_breakdowns.php\"'"
    echo ✅ admin_breakdowns.php fixed
)

REM Fix admin_breakdown_view.php
if exist "admin\admin_breakdown_view.php" (
    echo Fixing admin\admin_breakdown_view.php...
    powershell -Command "(Get-Content 'admin\admin_breakdown_view.php') -replace 'require_once ''config\.php''', 'require_once ''../config.php'''"
    powershell -Command "(Get-Content 'admin\admin_breakdown_view.php') -replace 'require_once ''includes/functions\.php''', 'require_once ''../includes/functions.php'''"
    powershell -Command "(Get-Content 'admin\admin_breakdown_view.php') -replace 'include ''includes/header\.php''', 'include ''../includes/header.php'''"
    powershell -Command "(Get-Content 'admin\admin_breakdown_view.php') -replace 'include ''includes/sidebar\.php''', 'include ''../includes/sidebar.php'''"
    powershell -Command "(Get-Content 'admin\admin_breakdown_view.php') -replace 'include ''includes/footer\.php''', 'include ''../includes/footer.php'''"
    powershell -Command "(Get-Content 'admin\admin_breakdown_view.php') -replace 'assets/css/style\.css', '../assets/css/style.css'"
    echo ✅ admin_breakdown_view.php fixed
)

REM Fix technician_breakdowns.php
if exist "technician\technician_breakdowns.php" (
    echo Fixing technician\technician_breakdowns.php...
    powershell -Command "(Get-Content 'technician\technician_breakdowns.php') -replace 'require_once ''config\.php''', 'require_once ''../config.php'''"
    powershell -Command "(Get-Content 'technician\technician_breakdowns.php') -replace 'require_once ''includes/functions\.php''', 'require_once ''../includes/functions.php'''"
    powershell -Command "(Get-Content 'technician\technician_breakdowns.php') -replace 'include ''includes/header\.php''', 'include ''../includes/header.php'''"
    powershell -Command "(Get-Content 'technician\technician_breakdowns.php') -replace 'include ''includes/sidebar\.php''', 'include ''../includes/sidebar.php'''"
    powershell -Command "(Get-Content 'technician\technician_breakdowns.php') -replace 'include ''includes/footer\.php''', 'include ''../includes/footer.php'''"
    powershell -Command "(Get-Content 'technician\technician_breakdowns.php') -replace 'assets/css/style\.css', '../assets/css/style.css'"
    echo ✅ technician_breakdowns.php fixed
)

REM Fix driver files
if exist "driver\driver_breakdown_new.php" (
    echo Fixing driver\driver_breakdown_new.php...
    powershell -Command "(Get-Content 'driver\driver_breakdown_new.php') -replace 'require_once ''config\.php''', 'require_once ''../config.php'''"
    powershell -Command "(Get-Content 'driver\driver_breakdown_new.php') -replace 'require_once ''includes/functions\.php''', 'require_once ''../includes/functions.php'''"
    powershell -Command "(Get-Content 'driver\driver_breakdown_new.php') -replace 'assets/css/style\.css', '../assets/css/style.css'"
    echo ✅ driver_breakdown_new.php fixed
)

if exist "driver\driver_login.php" (
    echo Fixing driver\driver_login.php...
    powershell -Command "(Get-Content 'driver\driver_login.php') -replace 'require_once ''config\.php''', 'require_once ''../config.php'''"
    powershell -Command "(Get-Content 'driver\driver_login.php') -replace 'require_once ''includes/functions\.php''', 'require_once ''../includes/functions.php'''"
    powershell -Command "(Get-Content 'driver\driver_login.php') -replace 'assets/css/style\.css', '../assets/css/style.css'"
    echo ✅ driver_login.php fixed
)

if exist "driver\driver_portal.php" (
    echo Fixing driver\driver_portal.php...
    powershell -Command "(Get-Content 'driver\driver_portal.php') -replace 'require_once ''config\.php''', 'require_once ''../config.php'''"
    powershell -Command "(Get-Content 'driver\driver_portal.php') -replace 'require_once ''includes/functions\.php''', 'require_once ''../includes/functions.php'''"
    powershell -Command "(Get-Content 'driver\driver_portal.php') -replace 'include ''includes/header\.php''', 'include ''../includes/header.php'''"
    powershell -Command "(Get-Content 'driver\driver_portal.php') -replace 'include ''includes/sidebar\.php''', 'include ''../includes/sidebar.php'''"
    powershell -Command "(Get-Content 'driver\driver_portal.php') -replace 'include ''includes/footer\.php''', 'include ''../includes/footer.php'''"
    powershell -Command "(Get-Content 'driver\driver_portal.php') -replace 'assets/css/style\.css', '../assets/css/style.css'"
    echo ✅ driver_portal.php fixed
)

REM Fix management files
if exist "management\buses.php" (
    echo Fixing management\buses.php...
    powershell -Command "(Get-Content 'management\buses.php') -replace 'require_once ''config\.php''', 'require_once ''../config.php'''"
    powershell -Command "(Get-Content 'management\buses.php') -replace 'require_once ''includes/functions\.php''', 'require_once ''../includes/functions.php'''"
    powershell -Command "(Get-Content 'management\buses.php') -replace 'include ''includes/header\.php''', 'include ''../includes/header.php'''"
    powershell -Command "(Get-Content 'management\buses.php') -replace 'include ''includes/sidebar\.php''', 'include ''../includes/sidebar.php'''"
    powershell -Command "(Get-Content 'management\buses.php') -replace 'include ''includes/footer\.php''', 'include ''../includes/footer.php'''"
    powershell -Command "(Get-Content 'management\buses.php') -replace 'assets/css/style\.css', '../assets/css/style.css'"
    echo ✅ buses.php fixed
)

if exist "management\drivers.php" (
    echo Fixing management\drivers.php...
    powershell -Command "(Get-Content 'management\drivers.php') -replace 'require_once ''config\.php''', 'require_once ''../config.php'''"
    powershell -Command "(Get-Content 'management\drivers.php') -replace 'require_once ''includes/functions\.php''', 'require_once ''../includes/functions.php'''"
    powershell -Command "(Get-Content 'management\drivers.php') -replace 'include ''includes/header\.php''', 'include ''../includes/header.php'''"
    powershell -Command "(Get-Content 'management\drivers.php') -replace 'include ''includes/sidebar\.php''', 'include ''../includes/sidebar.php'''"
    powershell -Command "(Get-Content 'management\drivers.php') -replace 'include ''includes/footer\.php''', 'include ''../includes/footer.php'''"
    powershell -Command "(Get-Content 'management\drivers.php') -replace 'assets/css/style\.css', '../assets/css/style.css'"
    echo ✅ drivers.php fixed
)

if exist "management\inventory.php" (
    echo Fixing management\inventory.php...
    powershell -Command "(Get-Content 'management\inventory.php') -replace 'require_once ''config\.php''', 'require_once ''../config.php'''"
    powershell -Command "(Get-Content 'management\inventory.php') -replace 'require_once ''includes/functions\.php''', 'require_once ''../includes/functions.php'''"
    powershell -Command "(Get-Content 'management\inventory.php') -replace 'include ''includes/header\.php''', 'include ''../includes/header.php'''"
    powershell -Command "(Get-Content 'management\inventory.php') -replace 'include ''includes/sidebar\.php''', 'include ''../includes/sidebar.php'''"
    powershell -Command "(Get-Content 'management\inventory.php') -replace 'include ''includes/footer\.php''', 'include ''../includes/footer.php'''"
    powershell -Command "(Get-Content 'management\inventory.php') -replace 'assets/css/style\.css', '../assets/css/style.css'"
    echo ✅ inventory.php fixed
)

REM Fix purchase files
for %%f in (purchase\achat_*.php) do (
    if exist "%%f" (
        echo Fixing %%f...
        powershell -Command "(Get-Content '%%f') -replace 'require_once ''config\.php''', 'require_once ''../config.php'''"
        powershell -Command "(Get-Content '%%f') -replace 'require_once ''includes/functions\.php''', 'require_once ''../includes/functions.php'''"
        powershell -Command "(Get-Content '%%f') -replace 'include ''includes/header\.php''', 'include ''../includes/header.php'''"
        powershell -Command "(Get-Content '%%f') -replace 'include ''includes/sidebar\.php''', 'include ''../includes/sidebar.php'''"
        powershell -Command "(Get-Content '%%f') -replace 'include ''includes/footer\.php''', 'include ''../includes/footer.php'''"
        powershell -Command "(Get-Content '%%f') -replace 'assets/css/style\.css', '../assets/css/style.css'"
        echo ✅ %%~nxf fixed
    )
)

REM Fix reports.php
if exist "reports\reports.php" (
    echo Fixing reports\reports.php...
    powershell -Command "(Get-Content 'reports\reports.php') -replace 'require_once ''config\.php''', 'require_once ''../config.php'''"
    powershell -Command "(Get-Content 'reports\reports.php') -replace 'require_once ''includes/functions\.php''', 'require_once ''../includes/functions.php'''"
    powershell -Command "(Get-Content 'reports\reports.php') -replace 'include ''includes/header\.php''', 'include ''../includes/header.php'''"
    powershell -Command "(Get-Content 'reports\reports.php') -replace 'include ''includes/sidebar\.php''', 'include ''../includes/sidebar.php'''"
    powershell -Command "(Get-Content 'reports\reports.php') -replace 'include ''includes/footer\.php''', 'include ''../includes/footer.php'''"
    powershell -Command "(Get-Content 'reports\reports.php') -replace 'assets/css/style\.css', '../assets/css/style.css'"
    echo ✅ reports.php fixed
)

echo.
echo 🎯 All paths fixed successfully!
echo 🔗 All PHP files now use correct relative paths
echo 🚀 The pages should now load properly

pause
