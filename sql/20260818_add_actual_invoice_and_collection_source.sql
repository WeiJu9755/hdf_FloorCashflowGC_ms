ALTER TABLE buildings_sub_detail
	ADD COLUMN first_actual_invoice_amount DECIMAL(12,2) NULL DEFAULT NULL COMMENT '第一次實開發票金額(含稅)' AFTER second_actual_billing_amount,
	ADD COLUMN second_actual_invoice_amount DECIMAL(12,2) NULL DEFAULT NULL COMMENT '第二次實開發票金額(含稅)' AFTER first_actual_invoice_amount,
	ADD COLUMN first_actual_collection_amount_source ENUM('auto', 'manual') NOT NULL DEFAULT 'auto' COMMENT '第一次實際收款金額來源' AFTER first_actual_collection_amount,
	ADD COLUMN second_actual_collection_amount_source ENUM('auto', 'manual') NOT NULL DEFAULT 'auto' COMMENT '第二次實際收款金額來源' AFTER second_actual_collection_amount;

UPDATE buildings_sub_detail
SET
	first_actual_invoice_amount = CASE
		WHEN first_actual_billing_amount IS NULL THEN NULL
		ELSE ROUND(
			first_actual_billing_amount
			- COALESCE(advance_payment_deduction_amount, 0)
			- COALESCE(first_other_deduction_amount, 0),
			2
		)
	END,
	second_actual_invoice_amount = CASE
		WHEN second_actual_billing_amount IS NULL THEN NULL
		ELSE ROUND(
			second_actual_billing_amount
			- COALESCE(second_advance_payment_deduction_amount, 0)
			- COALESCE(second_other_deduction_amount, 0),
			2
		)
	END
WHERE first_actual_billing_amount IS NOT NULL
	OR second_actual_billing_amount IS NOT NULL;
