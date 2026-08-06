ALTER TABLE buildings_sub_detail
	ADD COLUMN first_other_deduction_amount DECIMAL(12,2) NULL DEFAULT NULL COMMENT '第一次其他扣抵款' AFTER advance_payment_deduction_amount,
	ADD COLUMN second_other_deduction_amount DECIMAL(12,2) NULL DEFAULT NULL COMMENT '第二次其他扣抵款' AFTER second_advance_payment_deduction_amount;
