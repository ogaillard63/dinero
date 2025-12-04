-- Migration: Add is_active column to accounts table

ALTER TABLE accounts ADD COLUMN is_active TINYINT(1) DEFAULT 1 AFTER sort_order;
