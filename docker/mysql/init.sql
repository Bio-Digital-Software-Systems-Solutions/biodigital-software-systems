-- Initial MySQL setup script
-- This file runs automatically when the MySQL container is first created

-- Set character set and collation for the database
ALTER DATABASE bio_digital_software_systems CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create additional databases if needed (e.g., for testing)
CREATE DATABASE IF NOT EXISTS bio_digital_software_systems_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- The application connects as root, which already has full privileges on all databases.
