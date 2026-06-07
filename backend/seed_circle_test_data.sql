-- 插入测试建筑物数据
INSERT INTO buildings (name, community_name, total_floors, total_units, status, created_at, updated_at) VALUES
('1号楼', '阳光花园小区', 18, 72, 1, NOW(), NOW()),
('2号楼', '阳光花园小区', 24, 96, 1, NOW(), NOW()),
('3号楼', '阳光花园小区', 18, 72, 1, NOW(), NOW()),
('5号楼', '阳光花园小区', 11, 44, 1, NOW(), NOW()),
('6号楼', '阳光花园小区', 6, 24, 1, NOW(), NOW());

-- 更新现有用户，设置默认值
UPDATE users SET 
    resident_type = CASE 
        WHEN email = 'admin@forum.com' THEN 'owner'
        WHEN email LIKE 'owner%' THEN 'owner'
        WHEN email LIKE 'tenant%' THEN 'tenant'
        WHEN email LIKE 'committee%' THEN 'committee'
        ELSE 'owner'
    END,
    building_id = CASE
        WHEN email = 'admin@forum.com' THEN 1
        WHEN email LIKE '%1%' THEN 1
        WHEN email LIKE '%2%' THEN 2
        WHEN email LIKE '%3%' THEN 3
        ELSE 1
    END,
    verification_status = 'verified',
    verified_at = NOW(),
    unit_number = CONCAT(FLOOR(RAND() * 18) + 1, '0', FLOOR(RAND() * 4) + 1),
    moved_at = NULL
WHERE verification_status IS NULL;

-- 更新现有话题，设置默认值为公共圈层
UPDATE topics SET 
    circle_type = 'public',
    building_id = NULL
WHERE circle_type IS NULL;

-- 插入一些测试的内部圈层话题
INSERT INTO topics (user_id, title, content, category, circle_type, building_id, extra_fields, view_count, reply_count, is_pinned, status, created_at, updated_at) VALUES
-- 1号楼业主专属话题
(1, '关于1号楼电梯维护的通知', '各位邻居，本周六将进行电梯例行维护，请大家提前做好安排。', 'notice', 'building', 1, NULL, 45, 8, 0, 1, NOW(), NOW()),
(2, '1号楼业主大会会议纪要', '本次大会讨论了以下议题...', 'general', 'building', 1, NULL, 120, 23, 1, 1, NOW(), NOW()),
-- 物业维修话题（需要按角色显示字段）
(1, '电梯故障报修', '3单元电梯出现异响，需要紧急维修。', 'maintenance', 'building', 1, 
    '{\"description\": \"电梯运行时有异响，发生在3-5层之间\", \"status\": \"处理中\", \"reported_at\": \"2024-01-15 09:30:00\", \"unit_number\": \"1502\", \"contact_name\": \"张先生\", \"contact_phone\": \"13800138000\", \"assigned_to\": \"李师傅\", \"resolved_at\": null, \"cost\": null}'
, 34, 5, 0, 1, NOW(), NOW()),
-- 费用公示话题
(3, '2024年第一季度物业费明细', '本季度物业费收支情况如下...', 'fee', 'building', 1,
    '{\"fee_type\": \"物业管理费\", \"amount\": 2.5, \"due_date\": \"2024-04-01\", \"status\": \"已公示\", \"unit_number\": \"所有单元\", \"payment_method\": \"银行转账/微信\", \"paid_at\": null, \"receipt_number\": null, \"late_fee\": 0.05, \"total_amount\": 27000.00}'
, 156, 12, 0, 1, NOW(), NOW()),
-- 邻里矛盾话题
(4, '关于楼道堆放杂物的问题', '有邻居在楼道堆放杂物，影响通行和消防安全，请尽快清理。', 'conflict', 'building', 1,
    '{\"title\": \"楼道堆放杂物\", \"description\": \"15楼楼道堆放了纸箱和旧家具\", \"status\": \"调解中\", \"reported_at\": \"2024-01-18 14:00:00\", \"involved_parties\": \"1501业主, 1503业主\", \"unit_numbers\": \"1501, 1503\", \"contact_info\": \"13800138001, 13800138002\", \"mediator\": \"王委员\", \"resolution\": null, \"resolved_at\": null}'
, 78, 15, 0, 1, NOW(), NOW()),
-- 业委会专属话题
(1, '业委会年度工作计划', '2024年度业委会主要工作安排...', 'general', 'committee', 1, NULL, 34, 7, 0, 1, NOW(), NOW()),
-- 租户专属话题
(5, '租户装修时间规定', '为了不影响其他邻居休息，请租户装修时遵守以下时间规定...', 'notice', 'tenant', 1, NULL, 56, 4, 0, 1, NOW(), NOW()),
-- 2号楼话题
(6, '2号楼停水通知', '因水管维修，明天上午9点至下午5点停水，请提前储水。', 'notice', 'building', 2, NULL, 89, 12, 0, 1, NOW(), NOW());

-- 插入认证申请记录
INSERT INTO building_user_history (user_id, building_id, unit_number, resident_type, move_in_at, remark, created_at) VALUES
(2, 1, '502', 'owner', NOW(), '首次认证', NOW()),
(3, 1, '1203', 'owner', NOW(), '首次认证', NOW()),
(4, 1, '801', 'owner', NOW(), '首次认证', NOW()),
(5, 1, '305', 'tenant', NOW(), '租户认证', NOW()),
(6, 2, '1002', 'owner', NOW(), '首次认证', NOW());
