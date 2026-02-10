-- FUTURE AUTOMOTIVE - Supplier Related SQL Tables
-- جداول SQL للموردين للتوافق مع نظام الطباعة

-- 1. Table for suppliers (fournisseurs) - إذا لم تكن موجودة
CREATE TABLE IF NOT EXISTS suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code_fournisseur VARCHAR(50) UNIQUE NOT NULL,
    nom_fournisseur VARCHAR(255) NOT NULL,
    telephone VARCHAR(50),
    adresse TEXT,
    ville VARCHAR(100),
    email VARCHAR(255),
    ice VARCHAR(50) UNIQUE,
    rc VARCHAR(50) UNIQUE,
    statut ENUM('actif', 'inactif') DEFAULT 'actif',
    date_creation DATE DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 2. Table for supplier purchase orders (bons de commande fournisseurs)
CREATE TABLE IF NOT EXISTS bons_commande_fournisseurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_bcf VARCHAR(50) UNIQUE NOT NULL,
    fournisseur_id INT NOT NULL,
    date_bcf DATE NOT NULL,
    date_livraison_prevue DATE,
    statut ENUM('en_attente', 'valide', 'partiellement_livre', 'livre', 'annule') DEFAULT 'en_attente',
    montant_ht DECIMAL(10,2) DEFAULT 0,
    montant_tva DECIMAL(10,2) DEFAULT 0,
    montant_ttc DECIMAL(10,2) DEFAULT 0,
    conditions_paiement TEXT,
    mode_livraison VARCHAR(255),
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (fournisseur_id) REFERENCES suppliers(id) ON DELETE RESTRICT
);

-- 3. Table for supplier purchase order items
CREATE TABLE IF NOT EXISTS bcf_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_bcf INT NOT NULL,
    reference VARCHAR(100),
    designation TEXT NOT NULL,
    quantite DECIMAL(10,2) NOT NULL DEFAULT 0,
    prix_unitaire DECIMAL(10,2) NOT NULL DEFAULT 0,
    remise DECIMAL(5,2) DEFAULT 0,
    total_ht DECIMAL(10,2) GENERATED ALWAYS AS (quantite * prix_unitaire * (1 - remise/100)) STORED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_bcf) REFERENCES bons_commande_fournisseurs(id) ON DELETE CASCADE
);

-- 4. Table for supplier invoices (factures fournisseurs)
CREATE TABLE IF NOT EXISTS factures_fournisseurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_facture VARCHAR(50) UNIQUE NOT NULL,
    bcf_id INT,
    fournisseur_id INT NOT NULL,
    date_facture DATE NOT NULL,
    date_echeance DATE,
    statut ENUM('en_attente', 'payee_partiellement', 'payee', 'retard') DEFAULT 'en_attente',
    montant_ht DECIMAL(10,2) DEFAULT 0,
    montant_tva DECIMAL(10,2) DEFAULT 0,
    montant_ttc DECIMAL(10,2) DEFAULT 0,
    montant_paye DECIMAL(10,2) DEFAULT 0,
    montant_reste DECIMAL(10,2) GENERATED ALWAYS AS (montant_ttc - montant_paye) STORED,
    conditions_paiement TEXT,
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (bcf_id) REFERENCES bons_commande_fournisseurs(id) ON DELETE SET NULL,
    FOREIGN KEY (fournisseur_id) REFERENCES suppliers(id) ON DELETE RESTRICT
);

-- 5. Table for supplier invoice items
CREATE TABLE IF NOT EXISTS facture_fournisseur_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_facture INT NOT NULL,
    reference VARCHAR(100),
    designation TEXT NOT NULL,
    quantite DECIMAL(10,2) NOT NULL DEFAULT 0,
    prix_unitaire DECIMAL(10,2) NOT NULL DEFAULT 0,
    remise DECIMAL(5,2) DEFAULT 0,
    total_ht DECIMAL(10,2) GENERATED ALWAYS AS (quantite * prix_unitaire * (1 - remise/100)) STORED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_facture) REFERENCES factures_fournisseurs(id) ON DELETE CASCADE
);

-- 6. Table for supplier delivery receipts (bons de livraison fournisseurs)
CREATE TABLE IF NOT EXISTS bons_livraison_fournisseurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_blf VARCHAR(50) UNIQUE NOT NULL,
    bcf_id INT,
    fournisseur_id INT NOT NULL,
    date_livraison DATE NOT NULL,
    statut ENUM('en_attente', 'livre', 'retourne') DEFAULT 'en_attente',
    montant_ht DECIMAL(10,2) DEFAULT 0,
    montant_tva DECIMAL(10,2) DEFAULT 0,
    montant_ttc DECIMAL(10,2) DEFAULT 0,
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (bcf_id) REFERENCES bons_commande_fournisseurs(id) ON DELETE SET NULL,
    FOREIGN KEY (fournisseur_id) REFERENCES suppliers(id) ON DELETE RESTRICT
);

-- 7. Table for supplier delivery items
CREATE TABLE IF NOT EXISTS blf_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_blf INT NOT NULL,
    reference VARCHAR(100),
    designation TEXT NOT NULL,
    quantite_commandee DECIMAL(10,2) DEFAULT 0,
    quantite_livree DECIMAL(10,2) NOT NULL DEFAULT 0,
    prix_unitaire DECIMAL(10,2) NOT NULL DEFAULT 0,
    remise DECIMAL(5,2) DEFAULT 0,
    total_ht DECIMAL(10,2) GENERATED ALWAYS AS (quantite_livree * prix_unitaire * (1 - remise/100)) STORED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_blf) REFERENCES bons_livraison_fournisseurs(id) ON DELETE CASCADE
);

-- 8. Table for supplier payments
CREATE TABLE IF NOT EXISTS paiements_fournisseurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_paiement VARCHAR(50) UNIQUE NOT NULL,
    facture_fournisseur_id INT,
    fournisseur_id INT NOT NULL,
    date_paiement DATE NOT NULL,
    montant DECIMAL(10,2) NOT NULL,
    mode_paiement ENUM('espece', 'cheque', 'virement', 'autre') DEFAULT 'espece',
    reference_paiement VARCHAR(255),
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (facture_fournisseur_id) REFERENCES factures_fournisseurs(id) ON DELETE SET NULL,
    FOREIGN KEY (fournisseur_id) REFERENCES suppliers(id) ON DELETE RESTRICT
);

-- 9. Table for supplier returns (retours fournisseurs)
CREATE TABLE IF NOT EXISTS retours_fournisseurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_retour VARCHAR(50) UNIQUE NOT NULL,
    bcf_id INT,
    blf_id INT,
    fournisseur_id INT NOT NULL,
    date_retour DATE NOT NULL,
    motif_retour TEXT,
    statut ENUM('en_attente', 'accepte', 'refuse', 'rembourse') DEFAULT 'en_attente',
    montant_ht DECIMAL(10,2) DEFAULT 0,
    montant_tva DECIMAL(10,2) DEFAULT 0,
    montant_ttc DECIMAL(10,2) DEFAULT 0,
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (bcf_id) REFERENCES bons_commande_fournisseurs(id) ON DELETE SET NULL,
    FOREIGN KEY (blf_id) REFERENCES bons_livraison_fournisseurs(id) ON DELETE SET NULL,
    FOREIGN KEY (fournisseur_id) REFERENCES suppliers(id) ON DELETE RESTRICT
);

-- 10. Table for supplier return items
CREATE TABLE IF NOT EXISTS retour_fournisseur_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_retour INT NOT NULL,
    reference VARCHAR(100),
    designation TEXT NOT NULL,
    quantite_retournee DECIMAL(10,2) NOT NULL DEFAULT 0,
    prix_unitaire DECIMAL(10,2) NOT NULL DEFAULT 0,
    remise DECIMAL(5,2) DEFAULT 0,
    total_ht DECIMAL(10,2) GENERATED ALWAYS AS (quantite_retournee * prix_unitaire * (1 - remise/100)) STORED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_retour) REFERENCES retours_fournisseurs(id) ON DELETE CASCADE
);

-- Indexes for better performance
CREATE INDEX IF NOT EXISTS idx_suppliers_code ON suppliers(code_fournisseur);
CREATE INDEX IF NOT EXISTS idx_suppliers_nom ON suppliers(nom_fournisseur);
CREATE INDEX IF NOT EXISTS idx_suppliers_ice ON suppliers(ice);
CREATE INDEX IF NOT EXISTS idx_suppliers_rc ON suppliers(rc);

CREATE INDEX IF NOT EXISTS idx_bcf_numero ON bons_commande_fournisseurs(numero_bcf);
CREATE INDEX IF NOT EXISTS idx_bcf_fournisseur ON bons_commande_fournisseurs(fournisseur_id);
CREATE INDEX IF NOT EXISTS idx_bcf_date ON bons_commande_fournisseurs(date_bcf);
CREATE INDEX IF NOT EXISTS idx_bcf_statut ON bons_commande_fournisseurs(statut);

CREATE INDEX IF NOT EXISTS idx_factures_numero ON factures_fournisseurs(numero_facture);
CREATE INDEX IF NOT EXISTS idx_factures_fournisseur ON factures_fournisseurs(fournisseur_id);
CREATE INDEX IF NOT EXISTS idx_factures_date ON factures_fournisseurs(date_facture);
CREATE INDEX IF NOT EXISTS idx_factures_statut ON factures_fournisseurs(statut);

CREATE INDEX IF NOT EXISTS idx_blf_numero ON bons_livraison_fournisseurs(numero_blf);
CREATE INDEX IF NOT EXISTS idx_blf_fournisseur ON bons_livraison_fournisseurs(fournisseur_id);
CREATE INDEX IF NOT EXISTS idx_blf_date ON bons_livraison_fournisseurs(date_livraison);

-- Views for common queries
CREATE VIEW IF NOT EXISTS v_supplier_summary AS
SELECT 
    s.id,
    s.code_fournisseur,
    s.nom_fournisseur,
    s.telephone,
    s.ville,
    s.statut,
    COUNT(DISTINCT bcf.id) as nb_commandes,
    COUNT(DISTINCT ff.id) as nb_factures,
    COALESCE(SUM(ff.montant_ttc), 0) as total_factures,
    COALESCE(SUM(pf.montant), 0) as total_paye,
    COALESCE(SUM(ff.montant_ttc), 0) - COALESCE(SUM(pf.montant), 0) as solde
FROM suppliers s
LEFT JOIN bons_commande_fournisseurs bcf ON s.id = bcf.fournisseur_id
LEFT JOIN factures_fournisseurs ff ON s.id = ff.fournisseur_id
LEFT JOIN paiements_fournisseurs pf ON ff.id = pf.facture_fournisseur_id
GROUP BY s.id, s.code_fournisseur, s.nom_fournisseur, s.telephone, s.ville, s.statut;

CREATE VIEW IF NOT EXISTS v_supplier_documents AS
SELECT 
    'BCF' as type_doc,
    bcf.numero_bcf as numero,
    bcf.date_bcf as date_doc,
    s.nom_fournisseur,
    bcf.montant_ttc,
    bcf.statut
FROM bons_commande_fournisseurs bcf
JOIN suppliers s ON bcf.fournisseur_id = s.id

UNION ALL

SELECT 
    'FACTURE' as type_doc,
    ff.numero_facture as numero,
    ff.date_facture as date_doc,
    s.nom_fournisseur,
    ff.montant_ttc,
    ff.statut
FROM factures_fournisseurs ff
JOIN suppliers s ON ff.fournisseur_id = s.id

UNION ALL

SELECT 
    'BLF' as type_doc,
    blf.numero_blf as numero,
    blf.date_livraison as date_doc,
    s.nom_fournisseur,
    blf.montant_ttc,
    blf.statut
FROM bons_livraison_fournisseurs blf
JOIN suppliers s ON blf.fournisseur_id = s.id

ORDER BY date_doc DESC;
