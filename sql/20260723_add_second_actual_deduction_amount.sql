ALTER TABLE buildings_sub_detail
	ADD COLUMN second_retention_deduction_amount DECIMAL(12,2) NULL DEFAULT NULL COMMENT '第二次扣抵保留/租賃預收差款' AFTER retention_deduction_amount,
	ADD COLUMN second_advance_payment_deduction_amount DECIMAL(12,2) NULL DEFAULT NULL COMMENT '第二次扣抵預收款' AFTER advance_payment_deduction_amount;
