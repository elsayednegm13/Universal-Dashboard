CREATE TABLE IF NOT EXISTS analytics_daily (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  event_date DATE NOT NULL,
  event_type VARCHAR(40) NOT NULL,
  entity_type VARCHAR(40) NOT NULL DEFAULT '',
  entity_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
  entity_key VARCHAR(120) NOT NULL DEFAULT '',
  language CHAR(2) NOT NULL DEFAULT '',
  event_count BIGINT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_analytics_bucket(event_date,event_type,entity_type,entity_id,entity_key,language),
  INDEX idx_analytics_date_event(event_date,event_type),
  INDEX idx_analytics_entity(entity_type,entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
