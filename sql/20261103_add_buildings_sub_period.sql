-- 樓層合併計價期數（材料買賣／租賃案件）
-- 每個棟別可設定多期，每期一個樓層；套用預計數量/金額/收款日時，
-- 上一期樓層（不含）到本期樓層（含）的預計數量與金額會加總到本期樓層。
CREATE TABLE IF NOT EXISTS buildings_sub_period (
	auto_seq INT NOT NULL AUTO_INCREMENT,
	case_id VARCHAR(50) NOT NULL COMMENT '案件編號',
	building VARCHAR(50) NOT NULL COMMENT '棟別',
	period_no INT NOT NULL COMMENT '期數（依樓層由低到高排序）',
	floor VARCHAR(20) NOT NULL COMMENT '該期合併到的樓層',
	makeby VARCHAR(50) NULL DEFAULT NULL COMMENT '最後修改人',
	last_modify DATETIME NULL DEFAULT NULL COMMENT '最後修改時間',
	PRIMARY KEY (auto_seq),
	UNIQUE KEY uk_case_building_floor (case_id, building, floor),
	KEY idx_case_building (case_id, building)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='上包樓層金流-樓層合併計價期數';
