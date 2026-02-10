<?php
// FUTURE AUTOMOTIVE - Universal Document Printer
// طابعة مستندات عالمية

// Include configuration
require_once '../config.php';
require_once '../includes/functions.php';

// Check authentication
require_login();

// Get document parameters
$doc_type = sanitize_input($_GET['type'] ?? 'bc'); // bc, be, da, dp
$doc_id = sanitize_input($_GET['id'] ?? $_GET['nbc'] ?? $_GET['nbe'] ?? $_GET['nda'] ?? $_GET['ndp'] ?? '');
$format = sanitize_input($_GET['format'] ?? 'simple'); // simple, detailed

// Redirect to appropriate template based on document type
switch ($doc_type) {
    case 'bc':
        header('Location: bc_simple.php?nbc=' . urlencode($doc_id));
        break;
    case 'be':
        header('Location: be_simple.php?nbe=' . urlencode($doc_id));
        break;
    case 'da':
        header('Location: da_simple.php?nda=' . urlencode($doc_id));
        break;
    case 'dp':
        header('Location: dp_simple.php?ndp=' . urlencode($doc_id));
        break;
    default:
        header('Location: bc_simple.php?nbc=' . urlencode($doc_id));
        break;
}
exit;
?>
