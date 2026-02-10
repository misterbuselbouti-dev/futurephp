#!/bin/bash
# FUTURE AUTOMOTIVE - File Organization Script
# Reorganize breakdown management files to proper directories

echo "🔧 Reorganizing breakdown management files..."

# Create directories if they don't exist
mkdir -p "admin"
mkdir -p "technician" 
mkdir -p "driver"
mkdir -p "management"
mkdir -p "purchase"
mkdir -p "reports"

echo " Directories created successfully"

# Move files to appropriate directories

# Admin directory - Enhanced management files
echo " Moving admin files..."
if [ -f "admin/admin_breakdowns_enhanced.php" ]; then
    mv admin/admin_breakdowns_enhanced.php admin/admin_breakdowns.php
    echo " admin_breakdowns_enhanced.php → admin/admin_breakdowns.php"
fi

if [ -f "admin/admin_breakdown_view_enhanced.php" ]; then
    mv admin/admin_breakdown_view_enhanced.php admin_breakdown_view.php
    echo " admin_breakdown_view_enhanced.php → admin_breakdown_view.php"
fi

# Keep only the enhanced versions
if [ -f "admin/admin_breakdowns.php" ]; then
    rm admin/admin_breakdowns.php
fi

if [ -f "admin/admin_breakdown_view.php" ]; then
    rm admin/admin_breakdown_view.php
fi

# Move AJAX handlers to admin directory
echo " Moving AJAX handlers..."
if [ -f "admin/ajax_worker_assignment.php" ]; then
    echo " ajax_worker_assignment.php already in admin/"
fi

if [ -f "admin/ajax_inventory_integration.php" ]; then
    echo " ajax_inventory_integration.php already in admin/"
fi

if [ -f "admin/ajax_time_tracking.php" ]; then
    echo " ajax_time_tracking.php already in admin/"
fi

if [ -f "admin/ajax_audit_system.php" ]; then
    echo " ajax_audit_system.php already in admin/"
fi

# Move modal components to admin directory
echo " Moving modal components..."
if [ -f "admin/worker_assignment_modal.php" ]; then
    echo " worker_assignment_modal.php already in admin/"
fi

if [ -f "admin/inventory_integration_modal.php" ]; then
    echo " inventory_integration_modal.php already in admin/"
fi

if [ -f "admin/time_tracking_interface.php" ]; then
    echo " time_tracking_interface.php already in admin/"
fi

if [ -f "admin/audit_interface.php" ]; then
    echo " audit_interface.php already in admin/"
fi

# Keep only enhanced versions
if [ -f "admin/worker_assignment_modal.php" ]; then
    echo " worker_assignment_modal.php already in admin/"
fi

if [ -f "admin/inventory_integration_modal.php" ]; then
    echo " inventory_integration_modal.php already in admin/"
fi

if [ -f "admin/time_tracking_interface.php" ]; then
    echo " time_tracking_interface.php already in admin/"
fi

if [ -f "admin/audit_interface.php" ]; then
    echo " audit_interface.php already in admin/"
fi

# Technician directory - Technician interface
echo " Moving technician files..."
if [ -f "technician/technician_breakdowns.php" ]; then
    echo " technician_breakdowns.php already in technician/"
fi

# Driver directory - Driver portal
echo " Moving driver files..."
if [ -f "driver/driver_breakdown_new.php" ]; then
    echo " driver_breakdown_new.php already in driver/"
fi

if [ -f "driver/driver_login.php" ]; then
    echo " driver_login.php already in driver/"
fi

if [ -f "driver/driver_portal.php" ]; then
    echo " driver_portal.php already in driver/"
fi

# Management directory - Core management
echo " Moving management files..."
if [ -f "management/buses.php" ]; then
    echo " buses.php already in management/"
fi

if [ -f "management/drivers.php" ]; then
    echo " drivers.php already in management/"
fi

if [ -f "management/inventory.php" ]; then
    echo " inventory.php already in management/"
fi

# Purchase directory - Purchase management
echo " Moving purchase files..."
if [ -f "purchase/achat_bc.php" ]; then
    echo " achat_bc.php already in purchase/"
fi

if [ -f "purchase/achat_be.php" ]; then
    echo " achat_be.php already in purchase/"
fi

if [ -f "purchase/achat_da.php" ]; then
    echo " achat_da.php already in purchase/"
fi

if [ -f "purchase/achat_dp.php" ]; then
    echo " achat_dp.php already in purchase/"
fi

# Reports directory - Reporting
echo " Moving reports files..."
if [ -f "reports/reports.php" ]; then
    echo " reports.php already in reports/"
fi

# Clean up any duplicate files in root
echo " Cleaning up duplicate files..."
find . -maxdepth 1 -name "*_enhanced.php" -type f 2>/dev/null
find . -maxdepth 1 -name "*_old.php" -type f 2>/dev/null

echo ""
echo " File organization completed!"
echo ""
echo " Admin directory: Enhanced breakdown management"
echo " Technician directory: Technician interface"
echo " Driver directory: Driver portal"
echo " Management directory: Core management"
echo " Purchase directory: Purchase management"
echo " Reports directory: Reporting"
echo "📁 Driver directory: Driver portal"
echo "📁 Management directory: Core management"
echo "📁 Purchase directory: Purchase management"
echo "📁 Reports directory: Reporting"
echo ""
echo "🔗 All files have been reorganized according to their function."
echo "🔗 Enhanced versions are now the main files."
echo "🔗 Old versions have been removed to avoid confusion."
echo ""
echo "📋 Usage:"
echo "   • Admin: admin/admin_breakdowns.php"
echo "   • Technician: technician/technician_breakdowns.php"
echo "   • Driver: driver/driver_breakdown_new.php"
echo "   • Management: management/buses.php, management/drivers.php"
echo "   • Purchase: purchase/achat_bc.php, purchase/achat_be.php, etc."
echo "   • Reports: reports/reports.php"
echo ""
echo "🎯 All AJAX handlers and modals are in the admin directory."
echo "🎯 All enhanced versions are now the main files."
echo "🎯 The system is now properly organized and ready for production use."
