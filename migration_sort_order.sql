-- Migration: Add sort_order field to accounts table

ALTER TABLE accounts ADD COLUMN sort_order INT DEFAULT 0 AFTER currency;

-- Update existing accounts with incremental sort_order grouped by bank
SET @row_number = 0;
SET @current_bank = 0;

UPDATE accounts a
JOIN (
    SELECT id, bank_id,
    @row_number := IF(@current_bank = bank_id, @row_number + 1, 1) AS new_order,
    @current_bank := bank_id
    FROM accounts
    ORDER BY bank_id, id
) AS sorted ON a.id = sorted.id
SET a.sort_order = sorted.new_order;
