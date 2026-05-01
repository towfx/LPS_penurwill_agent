-- Initial MySQL setup for Penurwill
-- This file is executed once when the MySQL container starts

-- Create initial database and user if they don't exist
-- Note: These may already be created by environment variables in docker-compose

-- Create indexing for better performance
ALTER DATABASE penurwill CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Set up replication user (optional, for future scaling)
-- CREATE USER IF NOT EXISTS 'repl'@'%' IDENTIFIED BY 'repl_password';
-- GRANT REPLICATION SLAVE ON *.* TO 'repl'@'%';

-- Set global variables for better performance
SET GLOBAL max_connections = 1000;
SET GLOBAL max_allowed_packet = 256M;
SET GLOBAL innodb_buffer_pool_size = 1024M;

FLUSH PRIVILEGES;
