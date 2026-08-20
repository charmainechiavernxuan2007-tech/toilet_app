-- =========================================================
-- Toilet Cleanliness Monitoring System - Database Schema
-- =========================================================

CREATE DATABASE IF NOT EXISTS toilet_monitor
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE toilet_monitor;

-- ---------------------------------------------------------
-- Users (Admin + Students). Admin creates all accounts.
-- ---------------------------------------------------------
CREATE TABLE users (
  id                    INT PRIMARY KEY AUTO_INCREMENT,
  username              VARCHAR(50)  NOT NULL UNIQUE,
  password_hash         VARCHAR(255) NOT NULL,
  full_name             VARCHAR(100) NOT NULL,
  email                 VARCHAR(120) NULL,
  role                  ENUM('admin','user') NOT NULL DEFAULT 'user',
  -- Forces the student to set their own password on first login
  must_change_password  TINYINT(1) NOT NULL DEFAULT 1,
  status                ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                         ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Toilets (toilet name / number / location)
-- ---------------------------------------------------------
CREATE TABLE toilets (
  id          INT PRIMARY KEY AUTO_INCREMENT,
  code        VARCHAR(20)  NOT NULL UNIQUE,   -- e.g. T01
  name        VARCHAR(100) NOT NULL,          -- e.g. "Block A - Level 1 Male Toilet"
  location    VARCHAR(150) NULL,
  status      ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
              ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Many-to-many: a user can be assigned many toilets,
-- a toilet can be assigned to many users.
-- ---------------------------------------------------------
CREATE TABLE user_toilets (
  id           INT PRIMARY KEY AUTO_INCREMENT,
  user_id      INT NOT NULL,
  toilet_id    INT NOT NULL,
  assigned_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_user_toilet (user_id, toilet_id),
  CONSTRAINT fk_ut_user   FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE,
  CONSTRAINT fk_ut_toilet FOREIGN KEY (toilet_id) REFERENCES toilets(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Each visit (check-in -> check-out) is one session record.
-- A session is IMMUTABLE once status = 'completed'. The app
-- layer never edits a completed session; it only ever creates
-- new sessions. This preserves accountability history.
-- ---------------------------------------------------------
CREATE TABLE toilet_sessions (
  id                INT PRIMARY KEY AUTO_INCREMENT,
  toilet_id         INT NOT NULL,
  user_id           INT NOT NULL,
  checkin_time      DATETIME NOT NULL,
  checkin_comment   TEXT NULL,
  checkout_time     DATETIME NULL,
  checkout_comment  TEXT NULL,
  status            ENUM('active','completed') NOT NULL DEFAULT 'active',
  created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_ts_toilet FOREIGN KEY (toilet_id) REFERENCES toilets(id),
  CONSTRAINT fk_ts_user   FOREIGN KEY (user_id)   REFERENCES users(id),
  INDEX idx_toilet_status (toilet_id, status),
  INDEX idx_user_status   (user_id, status)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Multiple photos per check-in / check-out
-- ---------------------------------------------------------
CREATE TABLE session_photos (
  id           INT PRIMARY KEY AUTO_INCREMENT,
  session_id   INT NOT NULL,
  photo_path   VARCHAR(255) NOT NULL,
  photo_type   ENUM('checkin','checkout') NOT NULL,
  uploaded_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_sp_session FOREIGN KEY (session_id) REFERENCES toilet_sessions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Seed: default admin account (username: admin / password: admin123)
-- CHANGE THIS PASSWORD IMMEDIATELY AFTER FIRST LOGIN.
-- Hash below is password_hash('admin123', PASSWORD_DEFAULT)
-- ---------------------------------------------------------
INSERT INTO users (username, password_hash, full_name, role, must_change_password)
VALUES ('admin', '$2b$10$64tCsEhriWu7I5k.TQiNOe4GnL9KdgWdm8eWhQSDTx2strf.zT5/q', 'System Administrator', 'admin', 0);
