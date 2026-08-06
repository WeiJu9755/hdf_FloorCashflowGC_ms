$Qry="SELECT * FROM CasePricingGC_sub WHERE case_id = '24HM028'";的結果是:

INSERT INTO `<table_name>` (`auto_seq`, `case_id`, `delivery_days`, `first_percentage`, `fp_cutoff_month_1a`, `fp_deadline_1a`, `fp_payment_months_1a`, `fp_payment_date_1a`, `fp_payment_days_1a`, `fp_receiving_payment_percent_1`, `fp_cutoff_month_1b`, `fp_deadline_1b`, `fp_payment_months_1b`, `fp_payment_date_1b`, `fp_payment_days_1b`, `fp_cutoff_month_2a`, `fp_deadline_2a`, `fp_payment_months_2a`, `fp_payment_date_2a`, `fp_payment_days_2a`, `fp_receiving_payment_percent_2`, `fp_cutoff_month_2b`, `fp_deadline_2b`, `fp_payment_months_2b`, `fp_payment_date_2b`, `fp_payment_days_2b`, `grouting_days`, `second_percentage`, `sp_cutoff_month_1a`, `sp_deadline_1a`, `sp_payment_months_1a`, `sp_payment_date_1a`, `sp_payment_days_1a`, `sp_receiving_payment_percent_1`, `sp_cutoff_month_1b`, `sp_deadline_1b`, `sp_payment_months_1b`, `sp_payment_date_1b`, `sp_payment_days_1b`, `sp_cutoff_month_2a`, `sp_deadline_2a`, `sp_payment_months_2a`, `sp_payment_date_2a`, `sp_payment_days_2a`, `sp_receiving_payment_percent_2`, `sp_cutoff_month_2b`, `sp_deadline_2b`, `sp_payment_months_2b`, `sp_payment_date_2b`, `sp_payment_days_2b`) VALUES (18, '24HM028', 0, 100, '當月', '30日', '次月', '月底', '0', 100, '', '', '', '', '0', '', '', '', '', '', 0, '', '', '', '', '', 0, 0, '', '', '', '', '', 0, '', '', '', '', '', '', '', '', '', '', 0, '', '', '', '', '');

$Qry="SELECT std_floor_u_price,roof_protrusion_u_price,layout_std_floor_u_price,square_roof_protrusion_u_price,gc_price_base_date,tax_excluded FROM CaseManagement WHERE case_id = '24HM028'";的結果是:

INSERT INTO `<table_name>` (`std_floor_u_price`, `roof_protrusion_u_price`, `layout_std_floor_u_price`, `square_roof_protrusion_u_price`, `gc_price_base_date`, `tax_excluded`) VALUES (1520, 1800, 0, 0, '灌漿日', 0);

$Qry= "SELECT * FROM buildings_sub WHERE case_id = '24HM028' ORDER BY auto_seq";的結果是:

INSERT INTO `<table_name>` (`auto_seq`, `case_id`, `building`, `scheduled_entry_date`, `actual_entry_date`, `construction_days_first_floor`, `construction_days_per_floor`, `std_layer_floor`, `std_layer_qty`, `roof_protrusion_floor`, `roof_protrusion_qty`, `layout_std_layer_qty`, `layout_roof_protrusion_qty`, `outsourcing`, `makeby`, `last_modify`) VALUES (96, '24HM028', '1', '2025-06-15', '2025-06-25', 30, 18, '3F-15F', 27904.00, 'R1F-R3F', 1314.00, 0.00, 0.00, 0, '0912630372', '2026-08-03 17:26:09');

$Qry="SELECT auto_seq,floor,expected_actual_delivery_date,expected_actual_grouting_date FROM buildings_sub_detail WHERE case_id = '24HM028' AND building = '1'"; 的結果是:

INSERT INTO `<table_name>` (`auto_seq`, `floor`, `expected_actual_delivery_date`, `expected_actual_grouting_date`) VALUES (728, '10F', '2025-12-14', '2025-12-22');
INSERT INTO `<table_name>` (`auto_seq`, `floor`, `expected_actual_delivery_date`, `expected_actual_grouting_date`) VALUES (729, '11F', '2025-12-31', '2026-01-08');
INSERT INTO `<table_name>` (`auto_seq`, `floor`, `expected_actual_delivery_date`, `expected_actual_grouting_date`) VALUES (730, '12F', '2026-01-17', '2026-01-26');
INSERT INTO `<table_name>` (`auto_seq`, `floor`, `expected_actual_delivery_date`, `expected_actual_grouting_date`) VALUES (731, '13F', '2026-02-04', '2026-02-12');
INSERT INTO `<table_name>` (`auto_seq`, `floor`, `expected_actual_delivery_date`, `expected_actual_grouting_date`) VALUES (732, '14F', '2026-02-28', '2026-03-09');
INSERT INTO `<table_name>` (`auto_seq`, `floor`, `expected_actual_delivery_date`, `expected_actual_grouting_date`) VALUES (733, '15F', '2026-03-20', '2026-03-30');
INSERT INTO `<table_name>` (`auto_seq`, `floor`, `expected_actual_delivery_date`, `expected_actual_grouting_date`) VALUES (721, '3F', '2025-07-20', '2025-07-31');
INSERT INTO `<table_name>` (`auto_seq`, `floor`, `expected_actual_delivery_date`, `expected_actual_grouting_date`) VALUES (722, '4F', '2025-08-15', '2025-08-25');
INSERT INTO `<table_name>` (`auto_seq`, `floor`, `expected_actual_delivery_date`, `expected_actual_grouting_date`) VALUES (723, '5F', '2025-09-06', '2025-09-16');
INSERT INTO `<table_name>` (`auto_seq`, `floor`, `expected_actual_delivery_date`, `expected_actual_grouting_date`) VALUES (724, '6F', '2025-09-27', '2025-10-07');
INSERT INTO `<table_name>` (`auto_seq`, `floor`, `expected_actual_delivery_date`, `expected_actual_grouting_date`) VALUES (725, '7F', '2025-10-18', '2025-10-28');
INSERT INTO `<table_name>` (`auto_seq`, `floor`, `expected_actual_delivery_date`, `expected_actual_grouting_date`) VALUES (726, '8F', '2025-11-08', '2025-11-18');
INSERT INTO `<table_name>` (`auto_seq`, `floor`, `expected_actual_delivery_date`, `expected_actual_grouting_date`) VALUES (727, '9F', '2025-11-27', '2025-12-05');
INSERT INTO `<table_name>` (`auto_seq`, `floor`, `expected_actual_delivery_date`, `expected_actual_grouting_date`) VALUES (734, 'R1F', '2026-04-23', '2026-04-28');
INSERT INTO `<table_name>` (`auto_seq`, `floor`, `expected_actual_delivery_date`, `expected_actual_grouting_date`) VALUES (735, 'R2F', '2026-05-16', '2026-05-22');
INSERT INTO `<table_name>` (`auto_seq`, `floor`, `expected_actual_delivery_date`, `expected_actual_grouting_date`) VALUES (736, 'R3F', '2026-06-01', '2026-06-05');
