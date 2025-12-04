-- Migration: Rename balance to initial_balance and add computed balance logic

-- Rename balance to initial_balance
ALTER TABLE accounts CHANGE COLUMN balance initial_balance DECIMAL(10, 2) DEFAULT 0.00;

-- Note: current_balance will be calculated dynamically as: initial_balance + SUM(transactions.amount)
