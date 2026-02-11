<?php
// FUTURE AUTOMOTIVE - Shared helpers for BC print/PDF rendering

if (!function_exists('load_bc_document')) {
    /**
     * Load BC details with related supplier and article data.
     *
     * @param int $bc_id
     * @return array [array $bc, array $articles, array $company]
     * @throws Exception
     */
    function load_bc_document(int $bc_id): array
    {
        if (!$bc_id) {
            throw new InvalidArgumentException('Identifiant BC invalide.');
        }

        $database = new DatabaseAchat();
        $conn = $database->connect();

        $stmt = $conn->prepare("
            SELECT bc.*,
                   dp.ref_dp,
                   da.ref_da,
                   da.demandeur AS demandeur_service,
                   s.nom_fournisseur,
                   s.contact_nom AS supplier_contact,
                   s.email AS supplier_email,
                   s.telephone AS supplier_phone,
                   s.adresse AS supplier_address,
                   s.ice AS supplier_ice
            FROM bons_commande bc
            LEFT JOIN demandes_prix dp ON bc.dp_id = dp.id
            LEFT JOIN demandes_achat da ON dp.da_id = da.id
            LEFT JOIN suppliers s ON dp.fournisseur_id = s.id
            WHERE bc.id = ?
        ");
        $stmt->execute([$bc_id]);
        $bc = $stmt->fetch();

        if (!$bc) {
            throw new RuntimeException('Bon de commande introuvable.');
        }

        $articleStmt = $conn->prepare("
            SELECT * FROM bc_items
            WHERE bc_id = ?
            ORDER BY id
        ");
        $articleStmt->execute([$bc_id]);
        $articles = $articleStmt->fetchAll();

        // Collect affected bus numbers (distinct) based on DP items
        $busStmt = $conn->prepare("
            SELECT DISTINCT
                   COALESCE(b.bus_number, b.license_plate, CONCAT('Bus ', pi.bus_id)) AS bus_label
            FROM purchase_items pi
            LEFT JOIN buses b ON pi.bus_id = b.id
            WHERE pi.parent_type = 'DP'
              AND pi.parent_id = ?
              AND pi.bus_id IS NOT NULL
            ORDER BY bus_label ASC
        ");
        $busStmt->execute([$bc['dp_id']]);
        $busRows = $busStmt->fetchAll(PDO::FETCH_COLUMN);

        $bc['service_label'] = $bc['service'] ?? $bc['demandeur_service'] ?? '';
        $bc['bus_label'] = $busRows ? implode(', ', array_filter($busRows)) : '';
        $bc['payment_terms'] = $bc['payment_terms'] ?? "Vos factures seront réglées à 60 jours fin de mois à compter de la réception.";
        $bc['delivery_address'] = $bc['delivery_address'] ?? "Zone Industrielle Mghogha, Tanger";
        $bc['tva_rate'] = isset($bc['tva_rate']) ? (float)$bc['tva_rate'] : 20.0;

        // Optional company information (fallback to constants)
        $company = [
            'name' => APP_NAME,
            'slogan' => 'ISO 9001 & ISO 45001',
            'address' => 'Zone Industrielle, Tanger - Maroc',
            'phone' => '0539 00 00 00',
            'email' => 'contact@futureautomotive.net'
        ];

        try {
            $databaseStd = new Database();
            $pdo = $databaseStd->connect();
            $stmt = $pdo->query("SELECT * FROM company_info LIMIT 1");
            if ($info = $stmt->fetch()) {
                $company['name'] = $info['company_name'] ?? $company['name'];
                $company['address'] = $info['address'] ?? $company['address'];
                $company['phone'] = $info['phone'] ?? $company['phone'];
                $company['email'] = $info['email'] ?? $company['email'];
            }
        } catch (Exception $e) {
            // Silently fallback to defaults if table/config missing
        }

        return [$bc, $articles, $company];
    }
}

if (!function_exists('bc_format_currency')) {
    function bc_format_currency(float $value, int $decimals = 3): string
    {
        return number_format($value, $decimals, '.', ' ');
    }
}

if (!function_exists('bc_amount_to_words')) {
    /**
     * Convert amount to French words (Dirhams / Centimes).
     */
    function bc_amount_to_words(float $amount): string
    {
        $dirhams = (int) floor($amount + 1e-6);
        $centimes = (int) round(($amount - $dirhams) * 100);

        $dirhamWords = trim(bc_number_to_words_fr($dirhams));
        $centimeWords = $centimes > 0 ? trim(bc_number_to_words_fr($centimes)) . ' centime' . ($centimes > 1 ? 's' : '') : '';

        if ($dirhams === 0 && $centimes === 0) {
            return 'zéro dirham';
        }

        $parts = [];
        if ($dirhams > 0) {
            $parts[] = $dirhamWords . ' dirham' . ($dirhams > 1 ? 's' : '');
        }
        if ($centimes > 0) {
            $parts[] = $centimeWords;
        }

        return implode(' et ', $parts);
    }
}

if (!function_exists('bc_number_to_words_fr')) {
    function bc_number_to_words_fr(int $number): string
    {
        if ($number === 0) {
            return 'zéro';
        }

        $units = [
            '', 'un', 'deux', 'trois', 'quatre', 'cinq', 'six', 'sept', 'huit', 'neuf',
            'dix', 'onze', 'douze', 'treize', 'quatorze', 'quinze', 'seize',
            'dix-sept', 'dix-huit', 'dix-neuf'
        ];

        $tens = [
            '', 'dix', 'vingt', 'trente', 'quarante', 'cinquante', 'soixante',
            'soixante', 'quatre-vingt', 'quatre-vingt'
        ];

        $scales = [
            1000000000 => 'milliard',
            1000000    => 'million',
            1000       => 'mille',
            100        => 'cent'
        ];

        $words = [];
        foreach ($scales as $value => $label) {
            if ($number >= $value) {
                $count = (int) floor($number / $value);
                $number %= $value;

                if ($value === 100) {
                    if ($count > 1) {
                        $words[] = $units[$count] . ' cents';
                    } else {
                        $words[] = 'cent';
                    }
                } else {
                    $words[] = bc_number_to_words_fr($count) . ' ' . $label . ($count > 1 ? 's' : '');
                }
            }
        }

        if ($number >= 80 && $number < 100) {
            $words[] = 'quatre-vingt' . ($number > 80 ? '-' . ($number === 81 ? 'un' : bc_number_to_words_fr($number - 80)) : 's');
            return implode(' ', $words);
        }

        if ($number >= 60 && $number < 80) {
            $words[] = 'soixante' . ($number > 60 ? '-' . bc_number_to_words_fr($number - 60) : '');
            return implode(' ', $words);
        }

        if ($number >= 20) {
            $ten = (int) floor($number / 10);
            $unit = $number % 10;
            $words[] = $tens[$ten] . ($unit === 1 ? '-et-un' : ($unit ? '-' . $units[$unit] : ''));
            return implode(' ', $words);
        }

        if ($number > 0) {
            $words[] = $units[$number];
        }

        return implode(' ', array_filter($words));
    }
}

if (!function_exists('render_bc_document')) {
    /**
     * Render BC document as standalone HTML.
     *
     * @param array $bc
     * @param array $articles
     * @param array $company
     * @param array $options
     * @return string
     */
    function render_bc_document(array $bc, array $articles, array $company, array $options = []): string
    {
        $showActions = $options['show_actions'] ?? false;
        $printUrl = $options['print_url'] ?? '';
        $pdfUrl = $options['pdf_url'] ?? '';
        $documentTitle = 'Bon de commande - ' . ($bc['ref_bc'] ?? '');
        $amountWords = bc_amount_to_words((float) ($bc['total_ttc'] ?? 0));
        $pageLabel = $options['page_label'] ?? 'Page 1 / 1';
        $brandTag = $options['brand_tag'] ?? 'Bon de commande fournisseur';
        $busLabel = $bc['bus_label'] ?: 'Non spécifié';
        $serviceLabel = $bc['service_label'] ?: 'Non spécifié';
        $supplierIce = $bc['supplier_ice'] ?? '';

        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="fr" dir="ltr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo htmlspecialchars($documentTitle); ?></title>
            <style>
                <?php echo file_get_contents(__DIR__ . '/../assets/css/print/bc.css'); ?>
            </style>
        </head>
        <body>
        <?php if ($showActions): ?>
            <div class="bc-actions no-print">
                <button type="button" class="btn-print" onclick="window.print()">
                    🖨️ Imprimer
                </button>
                <?php if ($pdfUrl): ?>
                    <a class="btn-download" href="<?php echo htmlspecialchars($pdfUrl); ?>" target="_blank" rel="noopener">
                        ⬇️ Télécharger PDF
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <article class="bc-document">
            <header class="bc-document__header">
                <div class="bc-document__brand">
                    <h1><?php echo htmlspecialchars($company['name']); ?></h1>
                    <p><?php echo htmlspecialchars($company['slogan']); ?></p>
                    <p><?php echo htmlspecialchars($company['address']); ?></p>
                    <p><?php echo htmlspecialchars($company['phone']); ?> · <?php echo htmlspecialchars($company['email']); ?></p>
                </div>
                <div class="bc-document__meta">
                    <h2><?php echo htmlspecialchars($bc['ref_bc']); ?></h2>
                    <span>Bon de commande</span>
                    <span>Date: <?php echo htmlspecialchars(date('d/m/Y', strtotime($bc['date_commande']))); ?></span>
                    <span><?php echo htmlspecialchars($pageLabel); ?></span>
                    <div class="bc-pill"><?php echo htmlspecialchars($brandTag); ?></div>
                </div>
            </header>

            <section class="bc-section">
                <h3 class="bc-section__title">Coordonnées Fournisseur</h3>
                <div class="bc-grid bc-grid--two">
                    <div class="bc-card">
                        <div class="bc-card__label">Fournisseur</div>
                        <div class="bc-card__value"><?php echo htmlspecialchars($bc['nom_fournisseur'] ?? ''); ?></div>
                        <?php if ($supplierIce): ?>
                            <div class="bc-card__value" style="font-size:0.85rem;color:var(--text-secondary);">
                                ICE: <?php echo htmlspecialchars($supplierIce); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="bc-card">
                        <div class="bc-card__label">Contact</div>
                        <div class="bc-card__value"><?php echo htmlspecialchars($bc['supplier_contact'] ?? 'Non spécifié'); ?></div>
                        <div style="font-size:0.85rem;color:var(--text-secondary);margin-top:4px;">
                            <?php echo htmlspecialchars($bc['supplier_phone'] ?? 'Tél. non communiqué'); ?>
                        </div>
                        <div style="font-size:0.85rem;color:var(--text-secondary);">
                            <?php echo htmlspecialchars($bc['supplier_email'] ?? 'Email non communiqué'); ?>
                        </div>
                    </div>
                </div>
                <div class="bc-meta-bar">
                    <div class="bc-meta-bar__item">
                        <span class="bc-meta-bar__label">Service</span><?php echo htmlspecialchars($serviceLabel); ?>
                    </div>
                    <div class="bc-meta-bar__item">
                        <span class="bc-meta-bar__label">Bus</span><?php echo htmlspecialchars($busLabel); ?>
                    </div>
                    <div class="bc-meta-bar__item">
                        <span class="bc-meta-bar__label">Réf. DA</span><?php echo htmlspecialchars($bc['ref_da'] ?? '—'); ?>
                    </div>
                    <div class="bc-meta-bar__item">
                        <span class="bc-meta-bar__label">Réf. DP</span><?php echo htmlspecialchars($bc['ref_dp'] ?? '—'); ?>
                    </div>
                </div>
            </section>

            <section class="bc-section">
                <h3 class="bc-section__title">Conditions</h3>
                <div class="bc-grid bc-grid--two">
                    <div class="bc-card">
                        <div class="bc-card__label">Adresse de livraison</div>
                        <div class="bc-card__value" style="font-weight:500;">
                            <?php echo htmlspecialchars($bc['delivery_address']); ?>
                        </div>
                    </div>
                    <div class="bc-card">
                        <div class="bc-card__label">Conditions de paiement</div>
                        <div class="bc-card__value" style="font-weight:500;">
                            <?php echo htmlspecialchars($bc['payment_terms']); ?>
                        </div>
                    </div>
                </div>
            </section>

            <section class="bc-section">
                <h3 class="bc-section__title">Détails de la commande</h3>
                <table class="bc-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Réf Stock</th>
                            <th>Réf Pièce</th>
                            <th>Désignation</th>
                            <th>Quantité</th>
                            <th>Prix unitaire</th>
                            <th>Montant HT</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $totalHT = 0;
                    foreach ($articles as $index => $article):
                        $totalHT += (float) $article['total_price'];
                    ?>
                        <tr>
                            <td class="bc-table__numeric"><?php echo $index + 1; ?></td>
                            <td><?php echo htmlspecialchars($article['item_code'] ?? '—'); ?></td>
                            <td><?php echo htmlspecialchars($article['item_code'] ?? '—'); ?></td>
                            <td><?php echo htmlspecialchars($article['item_description']); ?></td>
                            <td class="bc-table__numeric"><?php echo bc_format_currency((float)$article['quantity'], 2); ?></td>
                            <td class="bc-table__numeric"><?php echo bc_format_currency((float)$article['unit_price']); ?></td>
                            <td class="bc-table__numeric"><?php echo bc_format_currency((float)$article['total_price']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <p class="bc-note bc-amount-words">
                    Arrêté le présent bon de commande à la somme de :
                    <strong><?php echo ucfirst($amountWords); ?></strong>
                </p>
            </section>

            <section class="bc-section">
                <h3 class="bc-section__title">Récapitulatif financier</h3>
                <div class="bc-summary">
                    <div class="bc-summary__block bc-summary__block--ht">
                        <div class="bc-summary__label">Montant HT</div>
                        <div class="bc-summary__value"><?php echo bc_format_currency((float) ($bc['total_ht'] ?? $totalHT)); ?> MAD</div>
                    </div>
                    <div class="bc-summary__block bc-summary__block--tva">
                        <div class="bc-summary__label">TVA (<?php echo number_format($bc['tva_rate'], 1); ?>%)</div>
                        <div class="bc-summary__value"><?php echo bc_format_currency((float) ($bc['tva'] ?? 0)); ?> MAD</div>
                    </div>
                    <div class="bc-summary__block bc-summary__block--ttc">
                        <div class="bc-summary__label">Net à payer</div>
                        <div class="bc-summary__value"><?php echo bc_format_currency((float) ($bc['total_ttc'] ?? ($totalHT + ($bc['tva'] ?? 0)))); ?> MAD</div>
                    </div>
                </div>
                <div class="bc-note">
                    N.B : Vos factures seront réglées à 60 jours fin du mois à partir de la date de la réception.
                </div>
            </section>

            <section class="bc-section">
                <div class="bc-signatures">
                    <div class="bc-signature">
                        <div>Signature Fournisseur</div>
                    </div>
                    <div class="bc-signature">
                        <div>La direction</div>
                    </div>
                </div>
            </section>

            <footer class="bc-footer">
                Généré le <?php echo date('d/m/Y H:i'); ?> · <?php echo htmlspecialchars($company['name']); ?> · Réf: <?php echo htmlspecialchars($bc['ref_bc']); ?>
            </footer>
        </article>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
}
