-- Update vendors table to store logo as BLOB instead of file path
USE qr_menu_system;

-- Add new logo column as BLOB
ALTER TABLE vendors ADD COLUMN logo_data LONGBLOB AFTER logo_path;

-- Add logo type column to store MIME type
ALTER TABLE vendors ADD COLUMN logo_type VARCHAR(100) AFTER logo_data;

-- Drop the old logo_path column (optional - you can keep it for backward compatibility)
-- ALTER TABLE vendors DROP COLUMN logo_path; 

ALTER TABLE users ADD COLUMN password_reset_token VARCHAR(64) DEFAULT NULL; 



-- Update menu_items table to support half and full prices
USE qr_menu_system;

-- Add new columns for half and full prices
ALTER TABLE menu_items ADD COLUMN price_full DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER price;
ALTER TABLE menu_items ADD COLUMN price_half DECIMAL(10,2) NULL AFTER price_full;
ALTER TABLE menu_items ADD COLUMN has_half_price BOOLEAN DEFAULT FALSE AFTER price_half;

-- Migrate existing data: set price_full to current price
UPDATE menu_items SET price_full = price WHERE price_full = 0.00;

-- Drop the old price column (optional - you can keep it for backward compatibility)
-- ALTER TABLE menu_items DROP COLUMN price; 


-- Allow duplicate phone numbers in users table
USE qr_menu_system;

-- Remove unique constraint from phone column
ALTER TABLE users DROP INDEX phone;

-- Note: If the above doesn't work, try this alternative:
-- ALTER TABLE users DROP INDEX phone_2;
-- or
-- SHOW INDEX FROM users; -- to see the exact index name 