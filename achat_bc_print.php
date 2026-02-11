<?php
// FUTURE AUTOMOTIVE - BC print-friendly view

require_once 'config.php';
require_once 'config_achat_hostinger.php';
require_once __DIR__ . '/includes/bc_document.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit();
}

$bc_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$bc_id) {
    header('Location: achat_bc.php');
    exit();
}

try {
    [$bc, $articles, $company] = load_bc_document($bc_id);
} catch (Exception $e) {
    http_response_code(500);
    echo '<p style="font-family:Inter,sans-serif;margin:40px;text-align:center;color:#b91c1c;">'
        . htmlspecialchars($e->getMessage()) . '</p>';
    exit();
}

$printUrl = 'achat_bc_print.php?id=' . $bc_id;
$pdfUrl = 'achat_bc_pdf.php?id=' . $bc_id;

echo render_bc_document(
    $bc,
    $articles,
    $company,
    [
        'show_actions' => true,
        'print_url' => $printUrl,
        'pdf_url' => $pdfUrl,
        'page_label' => 'Page 1 / 1',
        'brand_tag' => 'ISO 9001 · ISO 45001'
    ]
);
