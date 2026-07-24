ALTER TABLE buildings_sub_detail
	ADD COLUMN first_layout_actual_completed_qty DECIMAL(12,2) NULL DEFAULT NULL COMMENT '第一次實際放樣數量' AFTER first_actual_completed_qty,
	ADD COLUMN second_layout_actual_completed_qty DECIMAL(12,2) NULL DEFAULT NULL COMMENT '第二次實際放樣數量' AFTER second_actual_completed_qty;

UPDATE buildings_sub_detail
SET
	first_layout_actual_completed_qty = COALESCE(first_actual_completed_qty, completed_qty, 0),
	second_layout_actual_completed_qty = COALESCE(second_actual_completed_qty, 0)
WHERE (COALESCE(first_actual_completed_qty, completed_qty, 0) <> 0 OR COALESCE(second_actual_completed_qty, 0) <> 0)
	AND first_layout_actual_completed_qty IS NULL
	AND second_layout_actual_completed_qty IS NULL;
