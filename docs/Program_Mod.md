根據修改內容，需要變更或修改資料庫的欄位，要給我sql語法手動更新


這是case_id = '24HN086' 的上包計價設定範例:
INSERT INTO `CasePricingGC_sub` (`auto_seq`, `case_id`, `delivery_days`, `first_percentage`, `fp_cutoff_month_1a`, `fp_deadline_1a`, `fp_pricing_days_1a`, `fp_payment_months_1a`, `fp_payment_date_1a`, `fp_payment_days_1a`, `fp_receiving_payment_percent_1`, `fp_cutoff_month_1b`, `fp_deadline_1b`, `fp_pricing_days_1b`, `fp_payment_months_1b`, `fp_payment_date_1b`, `fp_payment_days_1b`, `fp_cutoff_month_2a`, `fp_deadline_2a`, `fp_pricing_days_2a`, `fp_payment_months_2a`, `fp_payment_date_2a`, `fp_payment_days_2a`, `fp_receiving_payment_percent_2`, `fp_cutoff_month_2b`, `fp_deadline_2b`, `fp_pricing_days_2b`, `fp_payment_months_2b`, `fp_payment_date_2b`, `fp_payment_days_2b`, `grouting_days`, `second_percentage`, `sp_cutoff_month_1a`, `sp_deadline_1a`, `sp_pricing_days_1a`, `sp_payment_months_1a`, `sp_payment_date_1a`, `sp_payment_days_1a`, `sp_receiving_payment_percent_1`, `sp_cutoff_month_1b`, `sp_deadline_1b`, `sp_pricing_days_1b`, `sp_payment_months_1b`, `sp_payment_date_1b`, `sp_payment_days_1b`, `sp_cutoff_month_2a`, `sp_deadline_2a`, `sp_pricing_days_2a`, `sp_payment_months_2a`, `sp_payment_date_2a`, `sp_payment_days_2a`, `sp_receiving_payment_percent_2`, `sp_cutoff_month_2b`, `sp_deadline_2b`, `sp_pricing_days_2b`, `sp_payment_months_2b`, `sp_payment_date_2b`, `sp_payment_days_2b`) VALUES (28, '24HN086', 0, 100, '當月', '20日', 0, '次次月', '5日', 0, 50, '', '', 0, '', '', 0, '當月', '20日', 0, '次次次月', '30日', 0, 50, '', '', 0, '', '', 0, 0, 0, '', '', 0, '', '', 0, 0, '', '', 0, '', '', 0, '', '', 0, '', '', 0, 0, '', '', 0, '', '', 0);

1. 預計收款金額要改成樓層金額與放樣金額加總，但要根據計價設定，改成第一次之一、第一次之二、第二次之一、第二次之二，放樣欄位都不需要了。

2. 實際計價日也改成第一次之一、第一次之二、第二次之一、第二次之二，不用第一次放樣和第二次放樣

3. buildings_modify.php 的 function apply_expected_work_qty($case_id,$memberID) 也要跟著修改

