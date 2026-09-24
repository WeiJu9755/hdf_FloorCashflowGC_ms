ALTER TABLE buildings_sub_detail
	CHANGE COLUMN first_actual_invoice_amount first_actual_invoice_amount_1 DECIMAL(12,2) NULL DEFAULT NULL COMMENT '第一次之一實開發票金額(含稅)',
	ADD COLUMN first_actual_invoice_amount_2 DECIMAL(12,2) NULL DEFAULT NULL COMMENT '第一次之二實開發票金額(含稅)' AFTER first_actual_invoice_amount_1,
	CHANGE COLUMN second_actual_invoice_amount second_actual_invoice_amount_1 DECIMAL(12,2) NULL DEFAULT NULL COMMENT '第二次之一實開發票金額(含稅)',
	ADD COLUMN second_actual_invoice_amount_2 DECIMAL(12,2) NULL DEFAULT NULL COMMENT '第二次之二實開發票金額(含稅)' AFTER second_actual_invoice_amount_1,
	CHANGE COLUMN first_actual_collection_amount first_actual_collection_amount_1 DECIMAL(12,2) NULL DEFAULT NULL COMMENT '第一次之一實際收款金額(含稅)',
	CHANGE COLUMN first_layout_actual_collection_amount first_actual_collection_amount_2 DECIMAL(12,2) NULL DEFAULT NULL COMMENT '第一次之二實際收款金額(含稅)',
	CHANGE COLUMN second_actual_collection_amount second_actual_collection_amount_1 DECIMAL(12,2) NULL DEFAULT NULL COMMENT '第二次之一實際收款金額(含稅)',
	CHANGE COLUMN second_layout_actual_collection_amount second_actual_collection_amount_2 DECIMAL(12,2) NULL DEFAULT NULL COMMENT '第二次之二實際收款金額(含稅)',
	CHANGE COLUMN first_actual_collection_amount_source first_actual_collection_amount_source_1 ENUM('auto', 'manual') NOT NULL DEFAULT 'auto' COMMENT '第一次之一實際收款金額來源',
	ADD COLUMN first_actual_collection_amount_source_2 ENUM('auto', 'manual') NOT NULL DEFAULT 'auto' COMMENT '第一次之二實際收款金額來源' AFTER first_actual_collection_amount_2,
	CHANGE COLUMN second_actual_collection_amount_source second_actual_collection_amount_source_1 ENUM('auto', 'manual') NOT NULL DEFAULT 'auto' COMMENT '第二次之一實際收款金額來源',
	ADD COLUMN second_actual_collection_amount_source_2 ENUM('auto', 'manual') NOT NULL DEFAULT 'auto' COMMENT '第二次之二實際收款金額來源' AFTER second_actual_collection_amount_2;

-- 先保留欄位改名前的第一次／第二次總額，避免 UPDATE 時被前一個欄位賦值影響。
CREATE TEMPORARY TABLE tmp_floorcashflow_actual_stage_amounts AS
SELECT
	auto_seq,
	first_actual_invoice_amount_1 AS first_invoice_total,
	second_actual_invoice_amount_1 AS second_invoice_total,
	CASE
		WHEN first_actual_collection_amount_1 IS NULL
		 AND first_actual_collection_amount_2 IS NULL
		THEN NULL
		ELSE COALESCE(first_actual_collection_amount_1, 0)
			+ COALESCE(first_actual_collection_amount_2, 0)
	END AS first_collection_total,
	CASE
		WHEN second_actual_collection_amount_1 IS NULL
		 AND second_actual_collection_amount_2 IS NULL
		THEN NULL
		ELSE COALESCE(second_actual_collection_amount_1, 0)
			+ COALESCE(second_actual_collection_amount_2, 0)
	END AS second_collection_total
FROM buildings_sub_detail;

UPDATE buildings_sub_detail d
INNER JOIN tmp_floorcashflow_actual_stage_amounts old_amount
	ON old_amount.auto_seq = d.auto_seq
LEFT JOIN CasePricingGC_sub p
	ON p.case_id = d.case_id
SET
	d.first_actual_invoice_amount_1 = CASE
		WHEN old_amount.first_invoice_total IS NULL THEN NULL
		WHEN COALESCE(p.fp_receiving_payment_percent_1, 0) = 0
		 AND COALESCE(p.fp_receiving_payment_percent_2, 0) = 0
			THEN old_amount.first_invoice_total
		WHEN COALESCE(p.fp_receiving_payment_percent_1, 0) > 0
			THEN ROUND(old_amount.first_invoice_total * p.fp_receiving_payment_percent_1 / 100, 2)
		ELSE 0
	END,
	d.first_actual_invoice_amount_2 = CASE
		WHEN old_amount.first_invoice_total IS NULL THEN NULL
		WHEN COALESCE(p.fp_receiving_payment_percent_1, 0) = 0
		 AND COALESCE(p.fp_receiving_payment_percent_2, 0) = 0
			THEN 0
		WHEN COALESCE(p.fp_receiving_payment_percent_1, 0) > 0
		 AND COALESCE(p.fp_receiving_payment_percent_2, 0) > 0
		 AND ABS(p.fp_receiving_payment_percent_1 + p.fp_receiving_payment_percent_2 - 100) < 0.00001
			THEN old_amount.first_invoice_total
				- ROUND(old_amount.first_invoice_total * p.fp_receiving_payment_percent_1 / 100, 2)
		WHEN COALESCE(p.fp_receiving_payment_percent_2, 0) > 0
			THEN ROUND(old_amount.first_invoice_total * p.fp_receiving_payment_percent_2 / 100, 2)
		ELSE 0
	END,
	d.second_actual_invoice_amount_1 = CASE
		WHEN old_amount.second_invoice_total IS NULL THEN NULL
		WHEN COALESCE(p.sp_receiving_payment_percent_1, 0) = 0
		 AND COALESCE(p.sp_receiving_payment_percent_2, 0) = 0
			THEN old_amount.second_invoice_total
		WHEN COALESCE(p.sp_receiving_payment_percent_1, 0) > 0
			THEN ROUND(old_amount.second_invoice_total * p.sp_receiving_payment_percent_1 / 100, 2)
		ELSE 0
	END,
	d.second_actual_invoice_amount_2 = CASE
		WHEN old_amount.second_invoice_total IS NULL THEN NULL
		WHEN COALESCE(p.sp_receiving_payment_percent_1, 0) = 0
		 AND COALESCE(p.sp_receiving_payment_percent_2, 0) = 0
			THEN 0
		WHEN COALESCE(p.sp_receiving_payment_percent_1, 0) > 0
		 AND COALESCE(p.sp_receiving_payment_percent_2, 0) > 0
		 AND ABS(p.sp_receiving_payment_percent_1 + p.sp_receiving_payment_percent_2 - 100) < 0.00001
			THEN old_amount.second_invoice_total
				- ROUND(old_amount.second_invoice_total * p.sp_receiving_payment_percent_1 / 100, 2)
		WHEN COALESCE(p.sp_receiving_payment_percent_2, 0) > 0
			THEN ROUND(old_amount.second_invoice_total * p.sp_receiving_payment_percent_2 / 100, 2)
		ELSE 0
	END,
	d.first_actual_collection_amount_1 = CASE
		WHEN old_amount.first_collection_total IS NULL THEN NULL
		WHEN COALESCE(p.fp_receiving_payment_percent_1, 0) = 0
		 AND COALESCE(p.fp_receiving_payment_percent_2, 0) = 0
			THEN old_amount.first_collection_total
		WHEN COALESCE(p.fp_receiving_payment_percent_1, 0) > 0
			THEN ROUND(old_amount.first_collection_total * p.fp_receiving_payment_percent_1 / 100, 2)
		ELSE 0
	END,
	d.first_actual_collection_amount_2 = CASE
		WHEN old_amount.first_collection_total IS NULL THEN NULL
		WHEN COALESCE(p.fp_receiving_payment_percent_1, 0) = 0
		 AND COALESCE(p.fp_receiving_payment_percent_2, 0) = 0
			THEN 0
		WHEN COALESCE(p.fp_receiving_payment_percent_1, 0) > 0
		 AND COALESCE(p.fp_receiving_payment_percent_2, 0) > 0
		 AND ABS(p.fp_receiving_payment_percent_1 + p.fp_receiving_payment_percent_2 - 100) < 0.00001
			THEN old_amount.first_collection_total
				- ROUND(old_amount.first_collection_total * p.fp_receiving_payment_percent_1 / 100, 2)
		WHEN COALESCE(p.fp_receiving_payment_percent_2, 0) > 0
			THEN ROUND(old_amount.first_collection_total * p.fp_receiving_payment_percent_2 / 100, 2)
		ELSE 0
	END,
	d.second_actual_collection_amount_1 = CASE
		WHEN old_amount.second_collection_total IS NULL THEN NULL
		WHEN COALESCE(p.sp_receiving_payment_percent_1, 0) = 0
		 AND COALESCE(p.sp_receiving_payment_percent_2, 0) = 0
			THEN old_amount.second_collection_total
		WHEN COALESCE(p.sp_receiving_payment_percent_1, 0) > 0
			THEN ROUND(old_amount.second_collection_total * p.sp_receiving_payment_percent_1 / 100, 2)
		ELSE 0
	END,
	d.second_actual_collection_amount_2 = CASE
		WHEN old_amount.second_collection_total IS NULL THEN NULL
		WHEN COALESCE(p.sp_receiving_payment_percent_1, 0) = 0
		 AND COALESCE(p.sp_receiving_payment_percent_2, 0) = 0
			THEN 0
		WHEN COALESCE(p.sp_receiving_payment_percent_1, 0) > 0
		 AND COALESCE(p.sp_receiving_payment_percent_2, 0) > 0
		 AND ABS(p.sp_receiving_payment_percent_1 + p.sp_receiving_payment_percent_2 - 100) < 0.00001
			THEN old_amount.second_collection_total
				- ROUND(old_amount.second_collection_total * p.sp_receiving_payment_percent_1 / 100, 2)
		WHEN COALESCE(p.sp_receiving_payment_percent_2, 0) > 0
			THEN ROUND(old_amount.second_collection_total * p.sp_receiving_payment_percent_2 / 100, 2)
		ELSE 0
	END,
	d.first_actual_collection_amount_source_2 = d.first_actual_collection_amount_source_1,
	d.second_actual_collection_amount_source_2 = d.second_actual_collection_amount_source_1;

UPDATE buildings_sub_detail
SET actual_collection_amount =
	COALESCE(first_actual_collection_amount_1, 0)
	+ COALESCE(first_actual_collection_amount_2, 0)
	+ COALESCE(second_actual_collection_amount_1, 0)
	+ COALESCE(second_actual_collection_amount_2, 0);

DROP TEMPORARY TABLE tmp_floorcashflow_actual_stage_amounts;

-- 部署新版程式後，樓層每次存檔都會依 CasePricingGC_sub 的 fp/sp 收款比例
-- 重算四階段實開發票金額；來源為 auto 的實際收款金額也會同步重算。
