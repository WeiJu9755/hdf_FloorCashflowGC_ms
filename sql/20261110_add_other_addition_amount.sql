-- 上包樓層金流：新增第一次／第二次「其他加款（未稅）」
-- 其他加款一律以未稅金額輸入及保存，計入實開發票金額與實際收款金額時乘以 1.05。
-- 請先備份 buildings_sub_detail，再於資料庫執行一次，之後部署新版程式。
ALTER TABLE buildings_sub_detail
	ADD COLUMN first_other_addition_amount DECIMAL(12,2) NULL DEFAULT NULL COMMENT '第一次其他加款(未稅)' AFTER first_other_deduction_amount,
	ADD COLUMN second_other_addition_amount DECIMAL(12,2) NULL DEFAULT NULL COMMENT '第二次其他加款(未稅)' AFTER second_other_deduction_amount;
