ALTER TABLE buildings_sub_detail
	ADD COLUMN first_actual_billing_amount DECIMAL(12,2) NULL DEFAULT NULL COMMENT '第一次實際金額(含放樣)' AFTER second_layout_actual_completed_qty,
	ADD COLUMN second_actual_billing_amount DECIMAL(12,2) NULL DEFAULT NULL COMMENT '第二次實際金額(含放樣)' AFTER first_actual_billing_amount;

UPDATE buildings_sub_detail
SET
	first_actual_collection_amount = COALESCE(first_actual_collection_amount, 0) + COALESCE(first_layout_actual_collection_amount, 0),
	first_layout_actual_collection_amount = 0,
	second_actual_collection_amount = COALESCE(second_actual_collection_amount, 0) + COALESCE(second_layout_actual_collection_amount, 0),
	second_layout_actual_collection_amount = 0,
	actual_collection_amount =
		COALESCE(first_actual_collection_amount, 0) +
		COALESCE(first_layout_actual_collection_amount, 0) +
		COALESCE(second_actual_collection_amount, 0) +
		COALESCE(second_layout_actual_collection_amount, 0)
WHERE
	COALESCE(first_layout_actual_collection_amount, 0) <> 0
	OR COALESCE(second_layout_actual_collection_amount, 0) <> 0;
