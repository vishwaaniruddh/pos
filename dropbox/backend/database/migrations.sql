-- ─────────────────────────────────────────────────────────────────────────────
-- Dropbox SKU Copier — MySQL 8+ Migrations
-- Run this once against your `dropbox_sku_copier` database.
-- ─────────────────────────────────────────────────────────────────────────────

CREATE DATABASE IF NOT EXISTS dropbox_sku_copier
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE dropbox_sku_copier;

-- ─── Table: dropbox_credentials ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS dropbox_credentials (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    access_token  TEXT        NOT NULL COMMENT 'AES-256-GCM encrypted',
    refresh_token TEXT        NOT NULL COMMENT 'AES-256-GCM encrypted',
    expires_at    INT         NOT NULL COMMENT 'UNIX timestamp of access token expiry',
    account_email VARCHAR(255)         COMMENT 'Dropbox account email for display',
    account_id    VARCHAR(100)         COMMENT 'Dropbox account_id from /users/get_current_account',
    scope         TEXT                 COMMENT 'Space-separated granted OAuth scopes',
    created_at    DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Table: jobs ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS jobs (
    id                   BIGINT AUTO_INCREMENT PRIMARY KEY,
    sku                  TEXT          NOT NULL                COMMENT 'SKU codes to match (comma/space separated)',
    source_path          VARCHAR(1000) NOT NULL                COMMENT 'Dropbox source folder path',
    destination_path     VARCHAR(1000) NOT NULL                COMMENT 'Dropbox destination folder path',
    status               ENUM(
                             'queued',
                             'scanning',
                             'copying',
                             'completed',
                             'completed_with_errors',
                             'failed',
                             'cancelled'
                         ) NOT NULL DEFAULT 'queued',
    folder_cursor        TEXT          NULL                    COMMENT 'Dropbox pagination cursor — persisted for crash resume',
    total_files_scanned  INT           NOT NULL DEFAULT 0,
    total_files_matched  INT           NOT NULL DEFAULT 0,
    total_copied         INT           NOT NULL DEFAULT 0,
    total_failed         INT           NOT NULL DEFAULT 0,
    total_images         INT           NOT NULL DEFAULT 0,
    total_videos         INT           NOT NULL DEFAULT 0,
    config               JSON          NULL                    COMMENT 'rate_delay_ms, max_retries, on_conflict',
    cancel_requested     TINYINT(1)    NOT NULL DEFAULT 0      COMMENT '1 = worker should stop cleanly after current file',
    error_message        TEXT          NULL,
    started_at           DATETIME      NULL,
    finished_at          DATETIME      NULL,
    created_at           DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status     (status),
    INDEX idx_created    (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Table: scanned_result ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS scanned_result (
    id               BIGINT AUTO_INCREMENT PRIMARY KEY,
    job_id           BIGINT        NOT NULL,
    file_name        VARCHAR(500)  NOT NULL                    COMMENT 'Basename of the file',
    sku              VARCHAR(100)  NULL                        COMMENT 'The specific SKU that triggered the match',
    scanned_path     VARCHAR(2000) NOT NULL                    COMMENT 'Full Dropbox source path',
    copied_path      VARCHAR(2000) NULL                        COMMENT 'Full Dropbox destination path (set after copy)',
    doc_type         ENUM('image', 'video', 'other') NOT NULL DEFAULT 'other',
    file_size        BIGINT        NOT NULL DEFAULT 0,
    status           ENUM('pending', 'queue', 'success', 'fail', 'skipped') NOT NULL DEFAULT 'pending',
    error_message    TEXT          NULL,
    retry_count      TINYINT       NOT NULL DEFAULT 0,
    created_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY      (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    INDEX idx_job_status  (job_id, status),
    INDEX idx_job_id      (job_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Table: job_logs ─────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS job_logs (
    id         BIGINT AUTO_INCREMENT PRIMARY KEY,
    job_id     BIGINT        NOT NULL,
    level      ENUM('info', 'warning', 'error') NOT NULL DEFAULT 'info',
    message    TEXT          NOT NULL,
    context    JSON          NULL                        COMMENT 'file_path, http_status, retry_count, etc.',
    created_at DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    INDEX idx_job_level   (job_id, level),
    INDEX idx_job_created (job_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Table: settings ─────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS settings (
    key_name   VARCHAR(100) NOT NULL PRIMARY KEY,
    payload    TEXT         NOT NULL,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
