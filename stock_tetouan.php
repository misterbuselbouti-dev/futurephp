<?php
// FUTURE AUTOMOTIVE - Stock Tétouan (page indépendante)
require_once 'config.php';
require_login();
$region_code = 'tetouan';
$region_name = 'Tétouan';
?>
<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock <?php echo $region_name; ?> - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .main-content { margin-left: 260px; padding: 2rem; }
        .stock-card { border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); border: 1px solid #e2e8f0; }
        .stock-card .card-header { background: #dbeafe; color: #1d4ed8; font-weight: 600; padding: 1rem 1.25rem; }
        .table-stock th { background: #f1f5f9; font-size: 0.75rem; text-transform: uppercase; }
        .breadcrumb { background: transparent; padding: 0; }
        @media (max-width: 992px) { .main-content { margin-left: 0; } }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid">
            <nav aria-label="breadcrumb" class="mb-2">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="buses.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="articles_stockables.php">Articles stockables</a></li>
                    <li class="breadcrumb-item active">Stock <?php echo $region_name; ?></li>
                </ol>
            </nav>
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                <h1 class="mb-0"><i class="fas fa-warehouse me-2"></i>Stock <?php echo $region_name; ?></h1>
                <a href="articles_stockables.php" class="btn btn-outline-primary"><i class="fas fa-arrow-left me-1"></i>Retour aux articles</a>
            </div>

            <div class="stock-card card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Mise à jour du stock - <?php echo $region_name; ?></span>
                    <div class="input-group input-group-sm" style="max-width: 280px;">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" id="searchStock" placeholder="Chercher...">
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-stock table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Réf</th>
                                    <th>Produit & Réf pièce</th>
                                    <th>Prix</th>
                                    <th>Stock actuel</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="stockTableBody">
                                <tr><td colspan="5" class="text-center py-4 text-muted">Chargement...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const REGION = '<?php echo $region_code; ?>';
        let allRows = [];

        function load() {
            fetch('api/articles_stockables/list.php')
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        allRows = res.data.map(a => ({
                            ...a,
                            stock: REGION === 'tetouan' ? parseFloat(a.stock_tetouan) : parseFloat(a.stock_ksar)
                        }));
                        render(allRows);
                    } else document.getElementById('stockTableBody').innerHTML = '<tr><td colspan="5" class="text-center text-danger">Erreur</td></tr>';
                })
                .catch(() => document.getElementById('stockTableBody').innerHTML = '<tr><td colspan="5" class="text-center text-danger">Erreur</td></tr>');
        }

        function render(rows) {
            const tbody = document.getElementById('stockTableBody');
            if (!rows.length) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4">Aucun article</td></tr>';
                return;
            }
            tbody.innerHTML = rows.map(a => `
                <tr>
                    <td><strong>${escapeHtml(a.reference)}</strong></td>
                    <td>
                        <div>${escapeHtml(a.designation)}</div>
                        ${a.ref_piece ? '<small class="text-muted">' + escapeHtml(a.ref_piece) + '</small>' : ''}
                    </td>
                    <td>${parseFloat(a.prix_unitaire).toFixed(2)} MAD</td>
                    <td>
                        <input type="number" class="form-control form-control-sm d-inline-block" style="width:90px" 
                               value="${a.stock}" step="0.01" min="0" data-id="${a.id}">
                    </td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick="saveOne(${a.id}, this)">Enregistrer</button>
                    </td>
                </tr>
            `).join('');
        }

        function escapeHtml(s) { return (s || '').toString().replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

        function saveOne(id, btn) {
            const row = btn.closest('tr');
            const input = row.querySelector('input[type="number"]');
            const val = parseFloat(input.value) || 0;
            btn.disabled = true;
            const fd = new FormData();
            fd.append('article_id', id);
            fd.append('region', REGION);
            fd.append('stock', val);
            fetch('api/articles_stockables/update_stock.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        const r = allRows.find(x => x.id == id);
                        if (r) r.stock = val;
                        alert('Stock mis à jour.');
                    } else alert('Erreur: ' + (res.error || ''));
                })
                .catch(() => alert('Erreur réseau'))
                .finally(() => { btn.disabled = false; });
        }

        document.getElementById('searchStock').addEventListener('input', function() {
            const q = this.value.trim().toLowerCase();
            const f = q ? allRows.filter(a =>
                (a.reference || '').toLowerCase().includes(q) ||
                (a.designation || '').toLowerCase().includes(q) ||
                (a.ref_piece || '').toLowerCase().includes(q)
            ) : allRows;
            render(f);
        });

        load();
    </script>
</body>
</html>
