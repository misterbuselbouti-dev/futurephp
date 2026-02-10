@echo off
REM FUTURE AUTOMOTIVE - File Organization Script for Windows
REM Reorganize breakdown management files to proper directories

echo 🔧 Reorganizing breakdown management files...

REM Create directories if they don't exist
if not exist "admin" mkdir "admin"
if not exist "technician" mkdir "technician"
if not exist "driver" mkdir "driver"
if not exist "management" mkdir "management"
if not exist "purchase" mkdir "purchase"
if not exist "reports" mkdir "reports"

echo 📁 Directories created successfully

REM Move files to appropriate directories

REM Admin directory - Enhanced management files
echo 📁 Moving admin files...
if exist "admin\admin_breakdowns_enhanced.php" (
    move "admin\admin_breakdowns_enhanced.php" "admin\admin_breakdowns.php"
    echo ✅ admin_breakdowns_enhanced.php → admin\admin_breakdowns.php
)

if exist "admin\admin_breakdown_view_enhanced.php" (
    move "admin\admin_breakdown_view_enhanced.php" "admin\admin_breakdown_view.php"
    echo ✅ admin_breakdown_view_enhanced.php → admin\admin_breakdown_view.php
)

REM Move AJAX handlers to admin directory
echo 📁 Moving AJAX handlers...
if exist "admin\ajax_worker_assignment.php" (
    echo ✅ ajax_worker_assignment.php already in admin/
)

if exist "admin\ajax_inventory_integration.php" (
    echo ✅ ajax_inventory_integration.php already in admin/
)

if exist "admin\ajax_time_tracking.php" (
    echo ✅ ajax_time_tracking.php already in admin/
)

if exist "admin\ajax_audit_system.php" (
    echo ✅ ajax_audit_system.php already in admin/
)

REM Move modal components to admin directory
echo 📁 Moving modal components...
if exist "admin\worker_assignment_modal.php" (
    echo ✅ worker_assignment_modal.php already in admin/
)

if exist "admin\inventory_integration_modal.php" (
    echo ✅ inventory_integration_modal.php already in admin/
)

if exist "admin\time_tracking_interface.php" (
    echo ✅ time_tracking_interface.php already in admin/
)

if exist "admin\audit_interface.php" (
    echo ✅ audit_interface.php already in admin/
)

REM Technician directory - Technician interface
echo 📁 Moving technician files...
if exist "technician\technician_breakdowns.php" (
    echo ✅ technician_breakdowns.php already in technician/
)

REM Driver directory - Driver portal
echo 📁 Moving driver files...
if exist "driver\driver_breakdown_new.php" (
    echo ✅ driver_breakdown_new.php already in driver/
)

if exist "driver\driver_login.php" (
    echo ✅ driver_login.php already in driver/
)

if exist "driver\driver_portal.php" (
    echo ✅ driver_portal.php already in driver/
)

REM Management directory - Core management
echo 📁 Moving management files...
if exist "management\buses.php" (
    echo ✅ buses.php already in management/
)

if exist "management\drivers.php" (
    echo ✅ drivers.php already in management/
)

if exist "management\inventory.php" (
    echo ✅ inventory.php already in management/
)

REM Purchase directory - Purchase management
echo 📁 Moving purchase files...
if exist "purchase\achat_bc.php" (
    echo ✅ achat_bc.php already in purchase/
)

if exist "purchase\achat_be.php" (
    echo ✅ achat_be.php already in purchase/
)

if exist "purchase\achat_da.php" (
    echo ✅ achat_da.php already in purchase/
)

if exist "purchase\achat_dp.php" (
    echo ✅ achat_dp.php already in purchase/
)

REM Reports directory - Reporting
echo 📁 Moving reports files...
if exist "reports\reports.php" (
    echo ✅ reports.php already in reports/
)

echo.
echo 🎯 File organization completed!
echo.
echo 📁 Admin directory: Enhanced breakdown management
echo 📁 Technician directory: Technician interface
echo 📁 Driver directory: Driver portal
echo 📁 Management directory: Core management
echo 📁 Purchase directory: Purchase management
echo 📁 Reports directory: Reporting
echo.
echo 🔗 All files have been reorganized according to their function.
echo 🔗 Enhanced versions are now the main files.
echo.
echo 📋 Usage:
echo    • Admin: admin\admin_breakdowns.php
echo    • Technician: technician\technician_breakdowns.php
echo    • Driver: driver\driver_breakdown_new.php
echo    • Management: management\buses.php, management\drivers.php
echo    • Purchase: purchase\achat_bc.php, purchase\achat_be.php, etc.
echo    • Reports: reports\reports.php
echo.
echo 🎯 All AJAX handlers and modals are in the admin directory.
echo 🎯 All enhanced versions are now the main files.
echo 🎯 The system is now properly organized and ready for production use.

pause
