-- Initial MySQL setup script
-- This file runs automatically when the MySQL container is first created

-- Set character set and collation for the database
ALTER DATABASE icc_munich CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create additional databases if needed (e.g., for testing)
CREATE DATABASE IF NOT EXISTS icc_munich_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Grant privileges to the application user
GRANT ALL PRIVILEGES ON icc_munich.* TO 'icc_user'@'%';
GRANT ALL PRIVILEGES ON icc_munich_testing.* TO 'icc_user'@'%';
FLUSH PRIVILEGES;
