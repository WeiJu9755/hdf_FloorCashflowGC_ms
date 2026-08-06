ALTER TABLE buildings_sub_detail
	ADD COLUMN retention_deduction_source ENUM('auto', 'manual') NOT NULL DEFAULT 'auto' COMMENT '第一次扣抵保留/租賃預收差款來源' AFTER retention_deduction_amount,
	ADD COLUMN advance_payment_deduction_source ENUM('auto', 'manual') NOT NULL DEFAULT 'auto' COMMENT '第一次扣抵預收款來源' AFTER advance_payment_deduction_amount,
	ADD COLUMN second_retention_deduction_source ENUM('auto', 'manual') NOT NULL DEFAULT 'auto' COMMENT '第二次扣抵保留/租賃預收差款來源' AFTER second_retention_deduction_amount,
	ADD COLUMN second_advance_payment_deduction_source ENUM('auto', 'manual') NOT NULL DEFAULT 'auto' COMMENT '第二次扣抵預收款來源' AFTER second_advance_payment_deduction_amount;
