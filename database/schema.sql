-- =====================================================================
-- University Asset Management System — Somali National University
-- Full MySQL schema + seed data
-- Engine: InnoDB | Charset: utf8mb4
--
-- Table count note: the brief asked for 10 tables but only listed 9,
-- and referenced two entities (asset location, audit results) with no
-- backing table. Per the agreed scope, this schema adds `locations` and
-- `asset_audits`, plus full workflow tables `requisitions` and
-- `asset_disposals`, and `settings` / `activity_logs` / `login_logs` to
-- support the admin Settings module. 15 tables total.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS university_asset_management
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE university_asset_management;

SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- 1. roles
-- ---------------------------------------------------------------------
CREATE TABLE roles (
    role_id     INT AUTO_INCREMENT PRIMARY KEY,
    role_name   VARCHAR(50) NOT NULL UNIQUE,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 2. departments  (head_id FK added after `users` exists — see below)
-- ---------------------------------------------------------------------
CREATE TABLE departments (
    department_id    INT AUTO_INCREMENT PRIMARY KEY,
    department_name  VARCHAR(100) NOT NULL,
    head_id           INT NULL,
    location          VARCHAR(150) NULL,
    created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 3. users
-- ---------------------------------------------------------------------
CREATE TABLE users (
    user_id        INT AUTO_INCREMENT PRIMARY KEY,
    name           VARCHAR(100) NOT NULL,
    email          VARCHAR(150) NOT NULL UNIQUE,
    password       VARCHAR(255) NOT NULL,
    role_id        INT NOT NULL,
    department_id  INT NULL,
    status         ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(role_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_users_department FOREIGN KEY (department_id) REFERENCES departments(department_id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

ALTER TABLE departments
    ADD CONSTRAINT fk_departments_head FOREIGN KEY (head_id) REFERENCES users(user_id)
        ON UPDATE CASCADE ON DELETE SET NULL;

-- ---------------------------------------------------------------------
-- 4. categories
-- ---------------------------------------------------------------------
CREATE TABLE categories (
    category_id    INT AUTO_INCREMENT PRIMARY KEY,
    category_name  VARCHAR(100) NOT NULL UNIQUE,
    description    TEXT NULL,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 5. locations  (physical location of an asset: building/room)
-- ---------------------------------------------------------------------
CREATE TABLE locations (
    location_id    INT AUTO_INCREMENT PRIMARY KEY,
    location_name  VARCHAR(100) NOT NULL,
    building       VARCHAR(100) NULL,
    room           VARCHAR(50) NULL,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 6. assets
-- ---------------------------------------------------------------------
CREATE TABLE assets (
    asset_id         INT AUTO_INCREMENT PRIMARY KEY,
    name             VARCHAR(150) NOT NULL,
    category_id      INT NOT NULL,
    serial_no        VARCHAR(100) NULL,
    location_id      INT NULL,
    department_id    INT NULL COMMENT 'current custodian department',
    purchase_date    DATE NOT NULL,
    purchase_cost    DECIMAL(12,2) NOT NULL,
    warranty_expiry  DATE NULL,
    status           ENUM('active','under_repair','disposed') NOT NULL DEFAULT 'active',
    description      TEXT NULL,
    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_assets_category FOREIGN KEY (category_id) REFERENCES categories(category_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_assets_location FOREIGN KEY (location_id) REFERENCES locations(location_id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_assets_department FOREIGN KEY (department_id) REFERENCES departments(department_id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 7. asset_assigned  (allocation of an asset to a department / custodian)
-- ---------------------------------------------------------------------
CREATE TABLE asset_assigned (
    assign_id          INT AUTO_INCREMENT PRIMARY KEY,
    asset_id           INT NOT NULL,
    assigned_to        INT NOT NULL COMMENT 'department the asset is allocated to',
    assigned_user_id   INT NULL COMMENT 'specific staff custodian within the department',
    assigned_by        INT NOT NULL COMMENT 'user who processed the allocation',
    assigned_date      DATE NOT NULL,
    return_date        DATE NULL,
    status             ENUM('active','returned','repair') NOT NULL DEFAULT 'active',
    remarks            VARCHAR(255) NULL,
    created_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_assigned_asset FOREIGN KEY (asset_id) REFERENCES assets(asset_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_assigned_department FOREIGN KEY (assigned_to) REFERENCES departments(department_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_assigned_user FOREIGN KEY (assigned_user_id) REFERENCES users(user_id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_assigned_by FOREIGN KEY (assigned_by) REFERENCES users(user_id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 8. asset_transfers
-- ---------------------------------------------------------------------
CREATE TABLE asset_transfers (
    transfer_id          INT AUTO_INCREMENT PRIMARY KEY,
    asset_id             INT NOT NULL,
    from_department_id   INT NULL,
    to_department_id     INT NOT NULL,
    transfer_date        DATE NOT NULL,
    handled_by           INT NOT NULL,
    reason               VARCHAR(255) NULL,
    created_at           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_transfers_asset FOREIGN KEY (asset_id) REFERENCES assets(asset_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_transfers_from_dept FOREIGN KEY (from_department_id) REFERENCES departments(department_id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_transfers_to_dept FOREIGN KEY (to_department_id) REFERENCES departments(department_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_transfers_handled_by FOREIGN KEY (handled_by) REFERENCES users(user_id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 9. asset_maintenance
-- ---------------------------------------------------------------------
CREATE TABLE asset_maintenance (
    maintenance_id      INT AUTO_INCREMENT PRIMARY KEY,
    asset_id             INT NOT NULL,
    issue_description    TEXT NOT NULL,
    reported_by          INT NOT NULL,
    reported_date        DATE NOT NULL,
    status               ENUM('pending','in_progress','completed') NOT NULL DEFAULT 'pending',
    completed_date       DATE NULL,
    cost                 DECIMAL(12,2) NULL,
    technician_vendor    VARCHAR(150) NULL,
    created_at           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_maintenance_asset FOREIGN KEY (asset_id) REFERENCES assets(asset_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_maintenance_reported_by FOREIGN KEY (reported_by) REFERENCES users(user_id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 10. asset_audits  (physical verification of assets)
-- ---------------------------------------------------------------------
CREATE TABLE asset_audits (
    audit_id     INT AUTO_INCREMENT PRIMARY KEY,
    asset_id     INT NOT NULL,
    audited_by   INT NOT NULL,
    audit_date   DATE NOT NULL,
    result       ENUM('found','missing','damaged') NOT NULL,
    remarks      VARCHAR(255) NULL,
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_audits_asset FOREIGN KEY (asset_id) REFERENCES assets(asset_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_audits_by FOREIGN KEY (audited_by) REFERENCES users(user_id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 11. requisitions  (department requests for new/replacement assets)
-- ---------------------------------------------------------------------
CREATE TABLE requisitions (
    requisition_id      INT AUTO_INCREMENT PRIMARY KEY,
    department_id        INT NOT NULL,
    requester_id          INT NOT NULL,
    category_id            INT NULL,
    quantity               INT NOT NULL DEFAULT 1,
    requisition_date      DATE NOT NULL,
    purpose                VARCHAR(255) NOT NULL,
    status                  ENUM('pending','approved','rejected','issued') NOT NULL DEFAULT 'pending',
    description             TEXT NULL,
    reviewed_by             INT NULL,
    reviewed_date           DATE NULL,
    created_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_requisitions_department FOREIGN KEY (department_id) REFERENCES departments(department_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_requisitions_requester FOREIGN KEY (requester_id) REFERENCES users(user_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_requisitions_category FOREIGN KEY (category_id) REFERENCES categories(category_id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_requisitions_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(user_id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 12. asset_disposals  (disposal request -> Top Management approval)
-- ---------------------------------------------------------------------
CREATE TABLE asset_disposals (
    disposal_id    INT AUTO_INCREMENT PRIMARY KEY,
    asset_id        INT NOT NULL,
    requested_by     INT NOT NULL,
    request_date      DATE NOT NULL,
    method             ENUM('sold','scrapped','donated','other') NOT NULL,
    reason              VARCHAR(255) NOT NULL,
    status               ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    approved_by          INT NULL,
    approved_date         DATE NULL,
    disposal_date          DATE NULL,
    remarks                 VARCHAR(255) NULL,
    created_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_disposals_asset FOREIGN KEY (asset_id) REFERENCES assets(asset_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_disposals_requested_by FOREIGN KEY (requested_by) REFERENCES users(user_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_disposals_approved_by FOREIGN KEY (approved_by) REFERENCES users(user_id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 13. settings  (key/value store for the admin Settings module)
-- ---------------------------------------------------------------------
CREATE TABLE settings (
    setting_key    VARCHAR(100) PRIMARY KEY,
    setting_value  TEXT NULL,
    updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 14. activity_logs  (system-wide audit trail)
-- ---------------------------------------------------------------------
CREATE TABLE activity_logs (
    log_id       INT AUTO_INCREMENT PRIMARY KEY,
    user_id       INT NULL,
    action         VARCHAR(100) NOT NULL,
    module          VARCHAR(50) NULL,
    description       TEXT NULL,
    ip_address         VARCHAR(45) NULL,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_activity_logs_user FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 15. login_logs  (authentication attempts, success + failure)
-- ---------------------------------------------------------------------
CREATE TABLE login_logs (
    login_log_id     INT AUTO_INCREMENT PRIMARY KEY,
    user_id            INT NULL,
    email_attempted     VARCHAR(150) NULL,
    status                ENUM('success','failed') NOT NULL,
    ip_address              VARCHAR(45) NULL,
    user_agent                VARCHAR(255) NULL,
    created_at                  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_login_logs_user FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- SEED DATA
-- All demo users share the password:  Password123!
-- (bcrypt hash below is verified compatible with PHP password_verify())
-- =====================================================================

INSERT INTO roles (role_name) VALUES
('Admin'), ('Asset Officer'), ('Department Head'), ('Top Management');

INSERT INTO departments (department_name, location) VALUES
('ICT Department', 'Main Building, 2nd Floor'),
('Finance Department', 'Admin Block, Ground Floor'),
('Library', 'Library Building'),
('Registrar Office', 'Admin Block, 1st Floor'),
('Human Resources', 'Admin Block, 2nd Floor'),
('Faculty of Engineering', 'Engineering Block');

-- Demo password for every seeded user: Password123!
INSERT INTO users (name, email, password, role_id, department_id, status) VALUES
('System Admin',      'admin@sun.edu.so',      '$2b$10$OKVDvK0w4e3PeOCsrmx74uy4Re8t20qwfXX6yVLNWNwhCL3NeFyYO', 1, 1, 'active'),
('Asad Hassan',        'asset.officer1@sun.edu.so', '$2b$10$OKVDvK0w4e3PeOCsrmx74uy4Re8t20qwfXX6yVLNWNwhCL3NeFyYO', 2, 1, 'active'),
('Faadumo Nuur',        'asset.officer2@sun.edu.so', '$2b$10$OKVDvK0w4e3PeOCsrmx74uy4Re8t20qwfXX6yVLNWNwhCL3NeFyYO', 2, 1, 'active'),
('Cabdi Warsame',       'head.ict@sun.edu.so',       '$2b$10$OKVDvK0w4e3PeOCsrmx74uy4Re8t20qwfXX6yVLNWNwhCL3NeFyYO', 3, 1, 'active'),
('Hodan Ali',            'head.library@sun.edu.so',   '$2b$10$OKVDvK0w4e3PeOCsrmx74uy4Re8t20qwfXX6yVLNWNwhCL3NeFyYO', 3, 3, 'active'),
('Mohamed Yusuf',         'head.finance@sun.edu.so',   '$2b$10$OKVDvK0w4e3PeOCsrmx74uy4Re8t20qwfXX6yVLNWNwhCL3NeFyYO', 3, 2, 'active'),
('Amina Sheikh',           'topmanagement@sun.edu.so',  '$2b$10$OKVDvK0w4e3PeOCsrmx74uy4Re8t20qwfXX6yVLNWNwhCL3NeFyYO', 4, NULL, 'active');

UPDATE departments SET head_id = 4 WHERE department_id = 1; -- ICT -> Cabdi Warsame
UPDATE departments SET head_id = 5 WHERE department_id = 3; -- Library -> Hodan Ali
UPDATE departments SET head_id = 6 WHERE department_id = 2; -- Finance -> Mohamed Yusuf

INSERT INTO categories (category_name, description) VALUES
('Computers & Laptops', 'Desktops, laptops and related computing devices'),
('Networking Equipment', 'Routers, switches, access points and cabling hardware'),
('Office Furniture', 'Desks, chairs, cabinets and other furniture'),
('Office Electronics', 'Printers, scanners, projectors and similar equipment'),
('Lab Equipment', 'Engineering and science laboratory equipment'),
('Vehicles', 'University-owned vehicles');

INSERT INTO locations (location_name, building, room) VALUES
('ICT Lab 1', 'Main Building', 'Room 201'),
('ICT Store', 'Main Building', 'Room 105'),
('Library Reading Hall', 'Library Building', 'Ground Floor'),
('Finance Office', 'Admin Block', 'Room 12'),
('Registrar Front Desk', 'Admin Block', 'Room 3'),
('Engineering Workshop', 'Engineering Block', 'Workshop 1'),
('HR Office', 'Admin Block', 'Room 20');

INSERT INTO assets (name, category_id, serial_no, location_id, department_id, purchase_date, purchase_cost, warranty_expiry, status, description) VALUES
('Dell Latitude 5420 Laptop',   1, 'SN-DL5420-001', 1, 1, '2023-02-10', 950.00,  '2026-02-10', 'active',      'Assigned to ICT lab instructors'),
('Dell Latitude 5420 Laptop',   1, 'SN-DL5420-002', 1, 1, '2023-02-10', 950.00,  '2026-02-10', 'active',      'Assigned to ICT lab instructors'),
('HP ProDesk Desktop',          1, 'SN-HPPD-101',   1, 1, '2022-08-15', 620.00,  '2025-08-15', 'under_repair','Desktop for ICT lab'),
('Cisco Catalyst 2960 Switch',  2, 'SN-CIS2960-01', 2, 1, '2021-05-20', 480.00,  '2024-05-20', 'active',      '24-port managed switch'),
('TP-Link Wireless Router',     2, 'SN-TPWR-11',    2, 1, '2023-01-05', 85.00,   '2025-01-05', 'active',      'Backup router in ICT store'),
('Executive Office Desk',       3, NULL,             4, 2, '2020-03-12', 210.00,  NULL,          'active',      'Finance office desk'),
('Ergonomic Office Chair',      3, NULL,             4, 2, '2020-03-12', 95.00,   NULL,          'active',      'Finance office chair'),
('Steel Filing Cabinet',        3, NULL,             5, 4, '2019-11-01', 130.00,  NULL,          'active',      'Registrar records cabinet'),
('HP LaserJet Pro Printer',     4, 'SN-HPLJ-77',    4, 2, '2022-06-18', 260.00,  '2024-06-18', 'under_repair','Finance office printer, paper jam issue'),
('Epson Projector EB-X05',      4, 'SN-EPX05-09',   6, 6, '2021-09-09', 340.00,  '2023-09-09', 'active',      'Lecture hall projector'),
('Canon Scanner LiDE 300',      4, 'SN-CANLIDE-3',  5, 4, '2021-02-22', 90.00,   '2023-02-22', 'disposed',    'Registrar scanner, beyond repair'),
('Digital Oscilloscope',        5, 'SN-OSC-4021',   6, 6, '2020-07-30', 720.00,  '2023-07-30', 'active',      'Engineering workshop equipment'),
('3D Printer - Creality Ender', 5, 'SN-3DP-556',    6, 6, '2022-04-11', 410.00,  '2024-04-11', 'active',      'Engineering prototyping lab'),
('Toyota Hiace Van',            6, 'SN-VEH-HIACE1', 7, 5, '2018-01-15', 18500.00,NULL,          'active',      'Staff transport van'),
('Toyota Corolla Sedan',        6, 'SN-VEH-COR22',  7, 5, '2019-06-10', 14500.00,NULL,          'under_repair','HR pool car, engine service'),
('Library Bookshelf Unit',      3, NULL,             3, 3, '2020-01-20', 150.00,  NULL,          'active',      'Reading hall shelving'),
('Library Reading Table',       3, NULL,             3, 3, '2020-01-20', 110.00,  NULL,          'active',      'Reading hall table'),
('Lenovo ThinkCentre Desktop',  1, 'SN-LNV-TC220',  1, 1, '2021-10-05', 540.00,  '2024-10-05', 'disposed',    'Retired after motherboard failure');

INSERT INTO asset_assigned (asset_id, assigned_to, assigned_user_id, assigned_by, assigned_date, return_date, status, remarks) VALUES
(1, 1, 4, 2, '2023-02-15', NULL, 'active', 'Issued to ICT department head for lab supervision'),
(2, 1, 4, 2, '2023-02-15', NULL, 'active', 'Issued for ICT lab instruction'),
(6, 2, 6, 3, '2020-03-15', NULL, 'active', 'Assigned to Finance office'),
(14,5, NULL, 2, '2018-01-20', NULL, 'active', 'Staff transport van assigned to HR'),
(9, 2, 6, 3, '2022-06-20', '2023-11-01', 'returned', 'Returned for repair, later reassigned'),
(18,1, 4, 2, '2021-10-08', '2024-01-10', 'returned', 'Retired unit, returned before disposal');

INSERT INTO asset_transfers (asset_id, from_department_id, to_department_id, transfer_date, handled_by, reason) VALUES
(9, 4, 2, '2022-06-20', 2, 'Reallocated printer from Registrar to Finance office'),
(10,1, 6, '2021-09-15', 3, 'Projector moved to Engineering lecture hall'),
(18,3, 1, '2021-10-06', 2, 'Desktop transferred from Library to ICT before it was retired');

INSERT INTO asset_maintenance (asset_id, issue_description, reported_by, reported_date, status, completed_date, cost, technician_vendor) VALUES
(3, 'Desktop not powering on, suspected PSU failure', 4, '2024-05-02', 'in_progress', NULL, NULL, 'Golis Tech Repairs'),
(9, 'Printer showing repeated paper jam error', 6, '2024-04-20', 'pending', NULL, NULL, NULL),
(15,'Engine warning light and unusual noise', 6, '2024-03-10', 'in_progress', NULL, 150.00, 'Mogadishu Auto Care'),
(11,'Scanner not detected by any PC, tested on 3 machines', 4, '2022-12-01', 'completed', '2022-12-20', 40.00, 'ICT Internal Team'),
(18,'Desktop fails to boot, motherboard suspected dead', 4, '2023-12-15', 'completed', '2024-01-05', 0.00, 'ICT Internal Team');

INSERT INTO asset_audits (asset_id, audited_by, audit_date, result, remarks) VALUES
(1, 2, '2024-01-15', 'found', 'Verified in ICT Lab 1, in good condition'),
(2, 2, '2024-01-15', 'found', 'Verified in ICT Lab 1, in good condition'),
(6, 3, '2024-01-16', 'found', 'Verified in Finance office'),
(14,2, '2024-01-18', 'found', 'Verified in HR parking area'),
(11,2, '2022-12-01', 'damaged', 'Confirmed beyond repair, recommended for disposal');

INSERT INTO requisitions (department_id, requester_id, category_id, quantity, requisition_date, purpose, status, description, reviewed_by, reviewed_date) VALUES
(3, 5, 1, 2, '2024-05-01', 'New laptops for library digital catalog stations', 'pending', 'Current PCs are over 5 years old and frequently freeze.', NULL, NULL),
(2, 6, 4, 1, '2024-04-10', 'Replacement printer for Finance office', 'approved', 'Existing printer under repair for over a month.', 2, '2024-04-15'),
(6, 4, 5, 1, '2024-03-01', 'Additional oscilloscope for engineering workshop', 'issued', 'Needed for second-year electronics lab sessions.', 3, '2024-03-10'),
(1, 4, 2, 3, '2024-02-18', 'Extra networking switches for new ICT lab expansion', 'rejected', 'Requested ahead of lab expansion approval.', 2, '2024-02-25');

INSERT INTO asset_disposals (asset_id, requested_by, request_date, method, reason, status, approved_by, approved_date, disposal_date, remarks) VALUES
(18, 2, '2024-01-10', 'scrapped', 'Motherboard failure, repair cost exceeds asset value', 'approved', 7, '2024-01-20', '2024-01-25', 'Approved by Top Management, unit scrapped'),
(11, 2, '2022-12-05', 'scrapped', 'Scanner beyond repair after failed diagnostics', 'approved', 7, '2022-12-10', '2022-12-15', 'Approved and disposed of via IT recycler'),
(15, 3, '2024-05-05', 'sold', 'High maintenance cost, recommend replacement', 'pending', NULL, NULL, NULL, 'Awaiting Top Management review');

INSERT INTO settings (setting_key, setting_value) VALUES
('university_name', 'Somali National University'),
('system_name', 'University Asset Management System'),
('logo_path', ''),
('academic_year', '2025/2026'),
('timezone', 'Africa/Mogadishu'),
('date_format', 'd-m-Y'),
('language', 'en'),
('session_timeout_minutes', '60'),
('records_per_page', '15'),
('theme', 'light'),
('smtp_host', ''),
('smtp_port', '587'),
('smtp_username', ''),
('smtp_password', ''),
('smtp_encryption', 'tls'),
('maintenance_mode', '0');
