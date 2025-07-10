-- Update menu_items table to support half and full prices
USE qr_menu_system;

-- Add new columns for half and full prices
ALTER TABLE menu_items ADD COLUMN price_full DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER price;
ALTER TABLE menu_items ADD COLUMN price_half DECIMAL(10,2) NULL AFTER price_full;
ALTER TABLE menu_items ADD COLUMN has_half_price BOOLEAN DEFAULT FALSE AFTER price_half;

-- Migrate existing data: set price_full to current price
UPDATE menu_items SET price_full = price WHERE price_full = 0.00; 