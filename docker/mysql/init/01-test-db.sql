-- Provision a dedicated database for the PHPUnit Feature suite and grant
-- the application user access to it. Runs once on first MySQL container
-- boot (placed in /docker-entrypoint-initdb.d/ via docker-compose).
CREATE DATABASE IF NOT EXISTS device_event_ingestion_svc_test
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

GRANT ALL PRIVILEGES ON device_event_ingestion_svc_test.* TO 'app'@'%';
FLUSH PRIVILEGES;
