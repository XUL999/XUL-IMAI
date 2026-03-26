-- XUL Agent SBT 数据库
-- Agent 技能表
CREATE TABLE `xul_sbt_skills` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `wallet_address` varchar(64) NOT NULL COMMENT '钱包地址',
  `skill_name` varchar(100) NOT NULL COMMENT '技能名称',
  `skill_desc` varchar(255) DEFAULT NULL COMMENT '技能描述',
  `certified` tinyint(1) DEFAULT 0 COMMENT '是否认证:0=未认证,1=已认证',
  `certifier` varchar(64) DEFAULT NULL COMMENT '认证者地址',
  `create_time` int(11) DEFAULT 0 COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `wallet_address` (`wallet_address`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='XUL SBT技能表';

-- Agent 配置表
CREATE TABLE `xul_agent_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `wallet_address` varchar(64) NOT NULL COMMENT '钱包地址',
  `agent_type` varchar(50) DEFAULT 'default' COMMENT 'Agent类型',
  `config` text COMMENT 'Agent配置JSON',
  `status` tinyint(1) DEFAULT 1 COMMENT '状态:0=停用,1=启用',
  `create_time` int(11) DEFAULT 0 COMMENT '创建时间',
  `update_time` int(11) DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `wallet_address` (`wallet_address`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='XUL Agent配置表';
