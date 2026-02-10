#!/bin/bash
# Fix paths in all new PHP files

echo "🔧 Fixing paths in PHP files..."

# Fix admin_breakdowns.php
if [ -f "admin/admin_breakdowns.php" ]; then
    echo "Fixing admin/admin_breakdowns.php..."
    sed -i 's|href="admin_breakdown_view.php|href="admin_breakdown_view.php|g' admin/admin_breakdowns.php
    sed -i 's|href="admin_breakdowns.php|href="admin_breakdowns.php|g' admin/admin_breakdowns.php
    echo "✅ admin_breakdowns.php fixed"
fi

# Fix admin_breakdown_view.php
if [ -f "admin/admin_breakdown_view.php" ]; then
    echo "Fixing admin/admin_breakdown_view.php..."
    sed -i 's|require_once '\''config\.php'\''|require_once '\''../config.php'\''|g' admin/admin_breakdown_view.php
    sed -i 's|require_once '\''includes/functions\.php'\''|require_once '\''../includes/functions.php'\''|g' admin/admin_breakdown_view.php
    sed -i 's|include '\''includes/header\.php'\''|include '\''../includes/header.php'\''|g' admin/admin_breakdown_view.php
    sed -i 's|include '\''includes/sidebar\.php'\''|include '\''../includes/sidebar.php'\''|g' admin/admin_breakdown_view.php
    sed -i 's|include '\''includes/footer\.php'\''|include '\''../includes/footer.php'\''|g' admin/admin_breakdown_view.php
    sed -i 's|assets/css/style\.php|../assets/css/style.php|g' admin/admin_breakdown_view.php
    echo "✅ admin_breakdown_view.php fixed"
fi

# Fix technician_breakdowns.php
if [ -f "technician/technician_breakdowns.php" ]; then
    echo "Fixing technician/technician_breakdowns.php..."
    sed -i 's|require_once '\''config\.php'\''|require_once '\''../config.php'\''|g' technician/technician_breakdowns.php
    sed -i 's|require_once '\''includes/functions\.php'\''|require_once '\''../includes/functions.php'\''|g' technician/technician_breakdowns.php
    sed -i 's|include '\''includes/header\.php'\''|include '\''../includes/header.php'\''|g' technician/technician_breakdowns.php
    sed -i 's|include '\''includes/sidebar\.php'\''|include '\''../includes/sidebar.php'\''|g' technician/technician_breakdowns.php
    sed -i 's|include '\''includes/footer\.php'\''|include '\''../includes/footer.php'\''|g' technician/technician_breakdowns.php
    sed -i 's|assets/css/style\.php|../assets/css/style.php|g' technician/technician_breakdowns.php
    echo "✅ technician_breakdowns.php fixed"
fi

# Fix driver files
if [ -f "driver/driver_breakdown_new.php" ]; then
    echo "Fixing driver/driver_breakdown_new.php..."
    sed -i 's|require_once '\''config\.php'\''|require_once '\''../config.php'\''|g' driver/driver_breakdown_new.php
    sed -i 's|require_once '\''includes/functions\.php'\''|require_once '\''../includes/functions.php'\''|g' driver/driver_breakdown_new.php
    sed -i 's|assets/css/style\.php|../assets/css/style.php|g' driver/driver_breakdown_new.php
    echo "✅ driver_breakdown_new.php fixed"
fi

if [ -f "driver/driver_login.php" ]; then
    echo "Fixing driver/driver_login.php..."
    sed -i 's|require_once '\''config\.php'\''|require_once '\''../config.php'\''|g' driver/driver_login.php
    sed -i 's|require_once '\''includes/functions\.php'\''|require_once '\''../includes/functions.php'\''|g' driver/driver_login.php
    sed -i 's|assets/css/style\.php|../assets/css/style.php|g' driver/driver_login.php
    echo "✅ driver_login.php fixed"
fi

if [ -f "driver/driver_portal.php" ]; then
    echo "Fixing driver/driver_portal.php..."
    sed -i 's|require_once '\''config\.php'\''|require_once '\''../config.php'\''|g' driver/driver_portal.php
    sed -i 's|require_once '\''includes/functions\.php'\''|require_once '\''../includes/functions.php'\''|g' driver/driver_portal.php
    sed -i 's|include '\''includes/header\.php'\''|include '\''../includes/header.php'\''|g' driver/driver_portal.php
    sed -i 's|include '\''includes/sidebar\.php'\''|include '\''../includes/sidebar.php'\''|g' driver/driver_portal.php
    sed -i 's|include '\''includes/footer\.php'\''|include '\''../includes/footer.php'\''|g' driver/driver_portal.php
    sed -i 's|assets/css/style\.php|../assets/css/style.php|g' driver/driver_portal.php
    echo "✅ driver_portal.php fixed"
fi

# Fix management files
if [ -f "management/buses.php" ]; then
    echo "Fixing management/buses.php..."
    sed -i 's|require_once '\''config\.php'\''|require_once '\''../config.php'\''|g' management/buses.php
    sed -i 's|require_once '\''includes/functions\.php'\''|require_once '\''../includes/functions.php'\''|g' management/buses.php
    sed -i 's|include '\''includes/header\.php'\''|include '\''../includes/header.php'\''|g' management/buses.php
    sed -i 's|include '\''includes/sidebar\.php'\''|include '\''../includes/sidebar.php'\''|g' management/buses.php
    sed -i 's|include '\''includes/footer\.php'\''|include '\''../includes/footer.php'\''|g' management/buses.php
    sed -i 's|assets/css/style\.php|../assets/css/style.php|g' management/buses.php
    echo "✅ buses.php fixed"
fi

if [ -f "management/drivers.php" ]; then
    echo "Fixing management/drivers.php..."
    sed -i 's|require_once '\''config\.php'\''|require_once '\''../config.php'\''|g' management/drivers.php
    sed -i 's|require_once '\''includes/functions\.php'\''|require_once '\''../includes/functions.php'\''|g' management/drivers.php
    sed -i 's|include '\''includes/header\.php'\''|include '\''../includes/header.php'\''|g' management/drivers.php
    sed -i 's|include '\''includes/sidebar\.php'\''|include '\''../includes/sidebar.php'\''|g' management/drivers.php
    sed -i 's|include '\''includes/footer\.php'\''|include '\''../includes/footer.php'\''|g' management/drivers.php
    sed -i 's|assets/css/style\.php|../assets/css/style.php|g' management/drivers.php
    echo "✅ drivers.php fixed"
fi

if [ -f "management/inventory.php" ]; then
    echo "Fixing management/inventory.php..."
    sed -i 's|require_once '\''config\.php'\''|require_once '\''../config.php'\''|g' management/inventory.php
    sed -i 's|require_once '\''includes/functions\.php'\''|require_once '\''../includes/functions.php'\''|g' management/inventory.php
    sed -i 's|include '\''includes/header\.php'\''|include '\''../includes/header.php'\''|g' management/inventory.php
    sed -i 's|include '\''includes/sidebar\.php'\''|include '\''../includes/sidebar.php'\''|g' management/inventory.php
    sed -i 's|include '\''includes/footer\.php'\''|include '\''../includes/footer.php'\''|g' management/inventory.php
    sed -i 's|assets/css/style\.php|../assets/css/style.php|g' management/inventory.php
    echo "✅ inventory.php fixed"
fi

# Fix purchase files
for file in purchase/achat_*.php; do
    if [ -f "$file" ]; then
        echo "Fixing $file..."
        sed -i 's|require_once '\''config\.php'\''|require_once '\''../config.php'\''|g' "$file"
        sed -i 's|require_once '\''includes/functions\.php'\''|require_once '\''../includes/functions.php'\''|g' "$file"
        sed -i 's|include '\''includes/header\.php'\''|include '\''../includes/header.php'\''|g' "$file"
        sed -i 's|include '\''includes/sidebar\.php'\''|include '\''../includes/sidebar.php'\''|g' "$file"
        sed -i 's|include '\''includes/footer\.php'\''|include '\''../includes/footer.php'\''|g' "$file"
        sed -i 's|assets/css/style\.php|../assets/css/style.php|g' "$file"
        echo "✅ $(basename $file) fixed"
    fi
done

# Fix reports.php
if [ -f "reports/reports.php" ]; then
    echo "Fixing reports/reports.php..."
    sed -i 's|require_once '\''config\.php'\''|require_once '\''../config.php'\''|g' reports/reports.php
    sed -i 's|require_once '\''includes/functions\.php'\''|require_once '\''../includes/functions.php'\''|g' reports/reports.php
    sed -i 's|include '\''includes/header\.php'\''|include '\''../includes/header.php'\''|g' reports/reports.php
    sed -i 's|include '\''includes/sidebar\.php'\''|include '\''../includes/sidebar.php'\''|g' reports/reports.php
    sed -i 's|include '\''includes/footer\.php'\''|include '\''../includes/footer.php'\''|g' reports/reports.php
    sed -i 's|assets/css/style\.php|../assets/css/style.php|g' reports/reports.php
    echo "✅ reports.php fixed"
fi

echo ""
echo "🎯 All paths fixed successfully!"
echo "🔗 All PHP files now use correct relative paths"
echo "🚀 The pages should now load properly"
