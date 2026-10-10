-- Миграция коммерческого ремонта для уже установленной базы КвартираФормат.
-- Перед импортом сделайте резервную копию в phpMyAdmin или:
-- mysqldump -u USER -p DATABASE > storage/backups/pre-commercial.sql
-- Если колонка уже есть, соответствующий ALTER пропустите.

SET NAMES utf8mb4;

ALTER TABLE services ADD COLUMN category VARCHAR(40) NOT NULL DEFAULT 'apartment';
ALTER TABLE services ADD INDEX idx_services_category (category, is_published, sort_order);
ALTER TABLE faqs ADD COLUMN category VARCHAR(40) NOT NULL DEFAULT 'apartment';
ALTER TABLE advantages ADD COLUMN category VARCHAR(40) NOT NULL DEFAULT 'apartment';
ALTER TABLE portfolio_items ADD COLUMN category VARCHAR(40) NOT NULL DEFAULT 'apartment';
ALTER TABLE repair_tariffs ADD COLUMN category VARCHAR(40) NOT NULL DEFAULT 'apartment';
ALTER TABLE leads ADD COLUMN lead_type VARCHAR(40) NOT NULL DEFAULT 'apartment';
ALTER TABLE leads ADD COLUMN work_start VARCHAR(160) NULL;
ALTER TABLE leads ADD COLUMN has_project VARCHAR(80) NULL;
ALTER TABLE leads ADD COLUMN premise_condition VARCHAR(160) NULL;
ALTER TABLE leads ADD INDEX idx_leads_type (lead_type, created_at);

CREATE TABLE IF NOT EXISTS lead_files (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  lead_id INT UNSIGNED NOT NULL,
  stored_name VARCHAR(255) NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  mime_type VARCHAR(160) NULL,
  size_bytes INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  INDEX idx_lead_files_lead (lead_id),
  CONSTRAINT fk_lead_files_lead FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Контент коммерческих услуг и настроек досевается при первом открытии сайта
-- (App\Support\SchemaMigrator) либо повторным импортом блока из database.sql.
