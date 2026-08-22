-- =====================================================================
-- Milestone A migration — quantity model + location hierarchy foundation
-- Additive only: no table dropped, no column dropped, no column renamed.
-- Safe to re-run: every statement checks for existence first.
-- Run once against the live `university_asset_management` database.
-- A pre-migration mysqldump is required before running this (see
-- storage/backups/). Also folded into database/schema.sql for fresh installs.
-- =====================================================================

USE university_asset_management;

-- ---------------------------------------------------------------------
-- 17. buildings (new — top of the location hierarchy)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS buildings (
    building_id    INT AUTO_INCREMENT PRIMARY KEY,
    building_name  VARCHAR(100) NOT NULL,
    campus         VARCHAR(100) NULL,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Backfill buildings from the distinct existing free-text locations.building values
INSERT INTO buildings (building_name)
SELECT DISTINCT building FROM locations
WHERE building IS NOT NULL AND building <> ''
  AND building NOT IN (SELECT building_name FROM buildings);

-- ---------------------------------------------------------------------
-- locations: link to buildings, add floor
-- ---------------------------------------------------------------------
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'locations' AND COLUMN_NAME = 'building_id');
SET @sql := IF(@col_exists = 0,
    'ALTER TABLE locations ADD COLUMN building_id INT NULL AFTER building',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'locations' AND COLUMN_NAME = 'floor');
SET @sql := IF(@col_exists = 0,
    'ALTER TABLE locations ADD COLUMN floor VARCHAR(50) NULL AFTER building_id',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

UPDATE locations l
JOIN buildings b ON b.building_name = l.building
SET l.building_id = b.building_id
WHERE l.building_id IS NULL AND l.building IS NOT NULL AND l.building <> '';

SET @fk_exists := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'locations' AND CONSTRAINT_NAME = 'fk_locations_building');
SET @sql := IF(@fk_exists = 0,
    'ALTER TABLE locations ADD CONSTRAINT fk_locations_building FOREIGN KEY (building_id) REFERENCES buildings(building_id) ON UPDATE CASCADE ON DELETE SET NULL',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------
-- assets: quantity (existing rows default to 1 -> identical to today's meaning)
-- ---------------------------------------------------------------------
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'assets' AND COLUMN_NAME = 'quantity');
SET @sql := IF(@col_exists = 0,
    'ALTER TABLE assets ADD COLUMN quantity INT NOT NULL DEFAULT 1 AFTER purchase_cost',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------
-- asset_assigned / asset_transfers / asset_maintenance / asset_disposals: quantity
-- ---------------------------------------------------------------------
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'asset_assigned' AND COLUMN_NAME = 'quantity');
SET @sql := IF(@col_exists = 0,
    'ALTER TABLE asset_assigned ADD COLUMN quantity INT NOT NULL DEFAULT 1 AFTER asset_id',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'asset_transfers' AND COLUMN_NAME = 'quantity');
SET @sql := IF(@col_exists = 0,
    'ALTER TABLE asset_transfers ADD COLUMN quantity INT NOT NULL DEFAULT 1 AFTER asset_id',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'asset_maintenance' AND COLUMN_NAME = 'quantity');
SET @sql := IF(@col_exists = 0,
    'ALTER TABLE asset_maintenance ADD COLUMN quantity INT NOT NULL DEFAULT 1 AFTER asset_id',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'asset_disposals' AND COLUMN_NAME = 'quantity');
SET @sql := IF(@col_exists = 0,
    'ALTER TABLE asset_disposals ADD COLUMN quantity INT NOT NULL DEFAULT 1 AFTER asset_id',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------
-- asset_audits: expected/actual/missing/damaged quantity (0 existing rows, no backfill concern)
-- ---------------------------------------------------------------------
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'asset_audits' AND COLUMN_NAME = 'expected_quantity');
SET @sql := IF(@col_exists = 0,
    'ALTER TABLE asset_audits ADD COLUMN expected_quantity INT NULL AFTER asset_id',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'asset_audits' AND COLUMN_NAME = 'actual_quantity');
SET @sql := IF(@col_exists = 0,
    'ALTER TABLE asset_audits ADD COLUMN actual_quantity INT NULL AFTER expected_quantity',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'asset_audits' AND COLUMN_NAME = 'missing_quantity');
SET @sql := IF(@col_exists = 0,
    'ALTER TABLE asset_audits ADD COLUMN missing_quantity INT NULL AFTER actual_quantity',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'asset_audits' AND COLUMN_NAME = 'damaged_quantity');
SET @sql := IF(@col_exists = 0,
    'ALTER TABLE asset_audits ADD COLUMN damaged_quantity INT NULL AFTER missing_quantity',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
