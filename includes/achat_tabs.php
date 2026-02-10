<?php
// FUTURE AUTOMOTIVE - Achat Workflow Tabs
// Onglets partagés: DA | DP | BC | BE
$current = basename($_SERVER['PHP_SELF']);
?>
<nav class="nav nav-tabs nav-tabs-achat mb-4" role="tablist">
    <a class="nav-link <?php echo $current === 'achat_da.php' ? 'active' : ''; ?>" href="achat_da.php">
        <i class="fas fa-file-alt me-1"></i>Demandes d'achats
    </a>
    <a class="nav-link <?php echo $current === 'achat_dp.php' ? 'active' : ''; ?>" href="achat_dp.php">
        <i class="fas fa-file-invoice me-1"></i>Demandes de prix
    </a>
    <a class="nav-link <?php echo $current === 'achat_bc.php' ? 'active' : ''; ?>" href="achat_bc.php">
        <i class="fas fa-file-invoice-dollar me-1"></i>Bon de commande
    </a>
    <a class="nav-link <?php echo $current === 'achat_be.php' ? 'active' : ''; ?>" href="achat_be.php">
        <i class="fas fa-truck-loading me-1"></i>Historique ajouts stock
    </a>
</nav>
<style>
.nav-tabs-achat { border-bottom: 2px solid #e2e8f0; }
.nav-tabs-achat .nav-link { color: #64748b; font-weight: 500; border: none; border-bottom: 3px solid transparent; padding: 0.75rem 1rem; }
.nav-tabs-achat .nav-link:hover { color: #2563eb; border-bottom-color: #93c5fd; }
.nav-tabs-achat .nav-link.active { color: #2563eb; border-bottom-color: #2563eb; background: transparent; }
.breadcrumb { background: transparent; padding: 0; }
</style>
