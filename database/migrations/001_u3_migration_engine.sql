CREATE TABLE IF NOT EXISTS cms_schema_migrations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  scope_key VARCHAR(120) NOT NULL,
  migration_key VARCHAR(190) NOT NULL,
  checksum CHAR(64) NOT NULL,
  applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_cms_migration_scope_key (scope_key,migration_key),
  INDEX idx_cms_migration_applied_at (applied_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
