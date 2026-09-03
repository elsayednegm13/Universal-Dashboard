CREATE TABLE IF NOT EXISTS cms_release_history (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  version VARCHAR(32) NOT NULL UNIQUE,
  source VARCHAR(40) NOT NULL DEFAULT 'migration',
  installed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_cms_release_installed_at (installed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO cms_release_history(version,source) VALUES ('0.4.0','migration') ON DUPLICATE KEY UPDATE source=VALUES(source);
