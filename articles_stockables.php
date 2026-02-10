<?php
// FUTURE AUTOMOTIVE - Articles Stockables
// Liste des articles stockables avec Stock Tétouan et Stock Ksar-Larache
require_once 'config.php';
require_login();
$user = get_logged_in_user();
$full_name = $user['full_name'] ?? 'Administrateur';
?>
<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Articles Stockables - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .main-content { margin-left: 260px; padding: 2rem; }
        .articles-card { border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); border: 1px solid #e2e8f0; }
        .articles-card .card-header { background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 1rem 1.25rem; font-weight: 600; }
        .stock-badge { display: inline-block; padding: 0.35rem 0.6rem; border-radius: 8px; font-size: 0.8rem; font-weight: 500; margin: 0 0.25rem; }
        .stock-tetouan { background: #dbeafe; color: #1d4ed8; }
        .stock-ksar { background: #dcfce7; color: #166534; }
        .search-wrap { max-width: 320px; }
        .table-articles th { background: #f1f5f9; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .table-articles td { vertical-align: middle; }
        .btn-action { padding: 0.35rem 0.6rem; border-radius: 6px; }
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
                    <li class="breadcrumb-item active">Articles stockables</li>
                </ol>
            </nav>
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                <h1 class="mb-0"><i class="fas fa-box-open me-2"></i>Liste des articles stockables</h1>
                <div class="d-flex gap-2 flex-wrap">
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addArticleModal">
                        <i class="fas fa-plus me-1"></i>Ajouter un article
                    </button>
                    <a href="stock_tetouan.php" class="btn btn-outline-primary"><i class="fas fa-warehouse me-1"></i>Stock Tétouan</a>
                    <a href="stock_ksar.php" class="btn btn-outline-success"><i class="fas fa-warehouse me-1"></i>Stock Ksar-Larache</a>
                </div>
            </div>

            <div class="articles-card card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span>Articles</span>
                    <div class="search-wrap">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" id="searchArticles" placeholder="Chercher...">
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-articles table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Réf</th>
                                    <th>Produit & Réf pièce</th>
                                    <th>Prix</th>
                                    <th>Stock Ksar</th>
                                    <th>Stock Tétouan</th>
                                    <th>Stock Total</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="articlesTableBody">
                                <tr><td colspan="8" class="text-center py-4 text-muted">Chargement...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Ajouter / Modifier -->
    <div class="modal fade" id="addArticleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalArticleTitle">Ajouter un article</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="articleForm">
                        <input type="hidden" name="action" id="formAction" value="add">
                        <input type="hidden" name="id" id="formId" value="">
                        <div class="mb-3">
                            <label class="form-label">Référence *</label>
                            <input type="text" class="form-control" name="code_article" id="formReference" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Désignation *</label>
                            <input type="text" class="form-control" name="designation" id="formDesignation" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Catégorie</label>
                            <select class="form-select" name="categorie" id="formCategorie">
                                <option value="Filtres">Filtres</option>
                                <option value="Freinage">Freinage</option>
                                <option value="Moteur">Moteur</option>
                                <option value="Refroidissement">Refroidissement</option>
                                <option value="Suspension">Suspension</option>
                                <option value="Électrique">Électrique</option>
                                <option value="Éclairage">Éclairage</option>
                                <option value="Liquides">Liquides</option>
                                <option value="Accessoires">Accessoires</option>
                                <option value="Divers">Divers</option>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Stock Ksar</label>
                                <input type="number" class="form-control" name="stock_ksar" id="formStockKsar" step="0.01" min="0" value="0">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Stock Tétouan</label>
                                <input type="number" class="form-control" name="stock_tetouan" id="formStockTetouan" step="0.01" min="0" value="0">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Prix unitaire (MAD)</label>
                                <input type="number" class="form-control" name="prix_unitaire" id="formPrix" step="0.0001" value="0">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Unité</label>
                                <select class="form-select" name="unite" id="formUnite" disabled>
                                    <option value="pièce" selected>pièce</option>
                                </select>
                                <small class="text-muted">Unité standardisée</small>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Stock minimal</label>
                                <input type="number" class="form-control" name="stock_minimal" id="formStockMinimal" step="0.01" min="0" value="0">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Fournisseur</label>
                                <input type="text" class="form-control" name="fournisseur_principal" id="formFournisseur" placeholder="Optionnel" disabled>
                                <small class="text-muted">Non disponible dans cette version</small>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-success" id="btnSaveArticle"><i class="fas fa-save me-1"></i>Enregistrer</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Modifier le stock -->
    <div class="modal fade" id="modifyStockModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Modifier le stock</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2"><strong id="modifyStockArticleName"></strong></p>
                    <div class="mb-3">
                        <label class="form-label">Stock Tétouan</label>
                        <input type="number" class="form-control" id="modifyStockTetouan" step="0.01" min="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Stock Ksar-Larache</label>
                        <input type="number" class="form-control" id="modifyStockKsar" step="0.01" min="0">
                    </div>
                    <input type="hidden" id="modifyStockArticleId" value="">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary" id="btnSaveStock"><i class="fas fa-save me-1"></i>Enregistrer</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let allArticles = [];
        const searchInput = document.getElementById('searchArticles');
        const tbody = document.getElementById('articlesTableBody');

        function loadArticles() {
            fetch('api/articles_stockables/list.php')
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        allArticles = res.data;
                        renderTable(allArticles);
                    } else {
                        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-danger">Erreur: ' + (res.error || '') + '</td></tr>';
                    }
                })
                .catch(() => {
                    tbody.innerHTML = '<tr><td colspan="8" class="text-center text-danger">Erreur de chargement</td></tr>';
                });
        }

        function renderTable(rows) {
            if (!rows || rows.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4 text-muted">Aucun article</td></tr>';
                return;
            }
            tbody.innerHTML = rows.map(a => `
                <tr>
                    <td><strong>${escapeHtml(a.reference)}</strong></td>
                    <td>
                        <div>${escapeHtml(a.designation)}</div>
                        <small class="text-muted d-block">Catégorie: ${escapeHtml(a.categorie || 'Divers')}</small>
                    </td>
                    <td>${parseFloat(a.prix_unitaire).toFixed(2)} MAD</td>
                    <td>
                        <span class="stock-badge stock-ksar">${parseFloat(a.stock_ksar || 0).toFixed(2)}</span>
                    </td>
                    <td>
                        <span class="stock-badge stock-tetouan">${parseFloat(a.stock_tetouan || 0).toFixed(2)}</span>
                    </td>
                    <td>
                        <span class="badge bg-info">${parseFloat((a.stock_ksar || 0) + (a.stock_tetouan || 0)).toFixed(2)}</span>
                    </td>
                    <td><span class="badge ${getStockBadgeClass((a.stock_ksar || 0) + (a.stock_tetouan || 0), a.stock_minimal)}">${getStockStatus((a.stock_ksar || 0) + (a.stock_tetouan || 0), a.stock_minimal)}</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary btn-action" onclick="openModifyStock(${a.id}, '${escapeJs(a.designation)}', ${a.stock_tetouan}, ${a.stock_ksar})" title="Modifier stock"><i class="fas fa-warehouse"></i></button>
                        <button class="btn btn-sm btn-outline-secondary btn-action" onclick="editArticle(${a.id})" title="Modifier"><i class="fas fa-pen"></i></button>
                        <button class="btn btn-sm btn-outline-danger btn-action" onclick="deleteArticle(${a.id}, '${escapeJs(a.reference)}')" title="Supprimer"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
            `).join('');
        }

        function escapeHtml(s) { return (s || '').toString().replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
        function escapeJs(s) { return (s || '').toString().replace(/'/g,"\\'").replace(/"/g,'\\"'); }
        
        function getStockStatus(current, minimal) {
            current = parseFloat(current) || 0;
            minimal = parseFloat(minimal) || 0;
            if (current <= minimal) return 'Critique';
            if (current <= (minimal * 2)) return 'Bas';
            return 'Normal';
        }
        
        function getStockBadgeClass(current, minimal) {
            current = parseFloat(current) || 0;
            minimal = parseFloat(minimal) || 0;
            if (current <= minimal) return 'bg-danger';
            if (current <= (minimal * 2)) return 'bg-warning';
            return 'bg-success';
        }

        function openAddArticle() {
            document.getElementById('modalArticleTitle').textContent = 'Ajouter un article';
            document.getElementById('formAction').value = 'add';
            document.getElementById('formId').value = '';
            document.getElementById('articleForm').reset();
        }

        function editArticle(id) {
            const a = allArticles.find(x => x.id == id);
            if (!a) return;
            document.getElementById('modalArticleTitle').textContent = 'Modifier l\'article';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('formId').value = a.id;
            document.getElementById('formReference').value = a.code_article;
            document.getElementById('formDesignation').value = a.designation;
            document.getElementById('formCategorie').value = a.categorie || 'Divers';
            document.getElementById('formStockKsar').value = a.stock_ksar || 0;
            document.getElementById('formStockTetouan').value = a.stock_tetouan || 0;
            document.getElementById('formPrix').value = a.prix_unitaire;
            document.getElementById('formStockMinimal').value = a.stock_minimal || 0;
            document.getElementById('formFournisseur').value = '';
            new bootstrap.Modal(document.getElementById('addArticleModal')).show();
        }

        function openModifyStock(id, name, st, sk) {
            document.getElementById('modifyStockArticleId').value = id;
            document.getElementById('modifyStockArticleName').textContent = name;
            document.getElementById('modifyStockTetouan').value = parseFloat(st) || 0;
            document.getElementById('modifyStockKsar').value = parseFloat(sk) || 0;
            new bootstrap.Modal(document.getElementById('modifyStockModal')).show();
        }

        function saveArticle() {
            const form = document.getElementById('articleForm');
            const fd = new FormData(form);
            fd.append('action', document.getElementById('formAction').value);
            if (document.getElementById('formId').value) fd.append('id', document.getElementById('formId').value);

            fetch('api/articles_stockables/save.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        bootstrap.Modal.getInstance(document.getElementById('addArticleModal')).hide();
                        loadArticles();
                        alert('Enregistré.');
                    } else alert('Erreur: ' + (res.error || ''));
                })
                .catch(() => alert('Erreur réseau'));
        }

        function saveStock() {
            const id = document.getElementById('modifyStockArticleId').value;
            const st = document.getElementById('modifyStockTetouan').value;
            const sk = document.getElementById('modifyStockKsar').value;

            const done = (region, val) => fetch('api/articles_stockables/update_stock.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'article_id=' + id + '&region=' + region + '&stock=' + encodeURIComponent(val)
            }).then(r => r.json());

            Promise.all([done('tetouan', st), done('ksar', sk)])
                .then(([r1, r2]) => {
                    if (r1.success && r2.success) {
                        bootstrap.Modal.getInstance(document.getElementById('modifyStockModal')).hide();
                        loadArticles();
                        alert('Stock mis à jour.');
                    } else alert('Erreur: ' + (r1.error || r2.error || ''));
                })
                .catch(() => alert('Erreur réseau'));
        }

        function deleteArticle(id, ref) {
            if (!confirm('Supprimer l\'article ' + ref + ' ?')) return;
            fetch('api/articles_stockables/delete.php?id=' + id)
                .then(r => r.json())
                .then(res => {
                    if (res.success) loadArticles();
                    else alert('Erreur: ' + (res.error || ''));
                })
                .catch(() => alert('Erreur réseau'));
        }

        document.getElementById('addArticleModal').addEventListener('show.bs.modal', openAddArticle);
        document.getElementById('btnSaveArticle').addEventListener('click', saveArticle);
        document.getElementById('btnSaveStock').addEventListener('click', saveStock);

        searchInput.addEventListener('input', () => {
            const q = searchInput.value.trim().toLowerCase();
            const filtered = q ? allArticles.filter(a =>
                (a.reference || '').toLowerCase().includes(q) ||
                (a.ref_piece || '').toLowerCase().includes(q) ||
                (a.designation || '').toLowerCase().includes(q)
            ) : allArticles;
            renderTable(filtered);
        });

        loadArticles();
    </script>
</body>
</html>
