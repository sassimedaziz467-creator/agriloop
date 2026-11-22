CREATE TABLE IF NOT EXISTS parcelles (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    culture VARCHAR(24) NOT NULL,
    superficie_ha DECIMAL(12,2) NOT NULL,
    localisation VARCHAR(180) NOT NULL,
    mode_irrigation VARCHAR(32) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT chk_parcelles_superficie CHECK (superficie_ha > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS consommations_eau (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    parcelle_id BIGINT UNSIGNED NOT NULL,
    date_consommation DATE NOT NULL,
    volume_m3 DECIMAL(14,2) NOT NULL,
    remarque VARCHAR(500) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_consommations_parcelle_date (parcelle_id, date_consommation),
    CONSTRAINT fk_consommations_parcelle FOREIGN KEY (parcelle_id)
        REFERENCES parcelles (id) ON DELETE CASCADE,
    CONSTRAINT chk_consommations_volume CHECK (volume_m3 > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
