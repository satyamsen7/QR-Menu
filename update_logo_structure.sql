-- Update vendors table to store logo as BLOB instead of file path
USE qr_menu_system;

-- Add new logo column as BLOB
ALTER TABLE vendors ADD COLUMN logo_data LONGBLOB AFTER logo_path;

-- Add logo type column to store MIME type
ALTER TABLE vendors ADD COLUMN logo_type VARCHAR(100) AFTER logo_data;

-- Drop the old logo_path column (optional - you can keep it for backward compatibility)
-- ALTER TABLE vendors DROP COLUMN logo_path; 

ALTER TABLE users ADD COLUMN password_reset_token VARCHAR(64) DEFAULT NULL; 