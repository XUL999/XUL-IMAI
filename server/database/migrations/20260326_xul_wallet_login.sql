-- XUL 链钱包登录数据库改造
-- 为 admin 表添加钱包地址字段

ALTER TABLE `admin` ADD COLUMN `wallet_address` VARCHAR(64) DEFAULT NULL COMMENT '钱包地址' AFTER `multipoint_login`;

-- 添加索引
ALTER TABLE `admin` ADD UNIQUE INDEX `wallet_address` (`wallet_address`);

-- 创建 XUL 登录日志表
CREATE TABLE `xul_login_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `wallet_address` varchar(64) NOT NULL COMMENT '钱包地址',
  `login_type` tinyint(1) DEFAULT 1 COMMENT '登录类型:1=钱包登录',
  `ip` varchar(50) DEFAULT NULL COMMENT 'IP地址',
  `user_agent` varchar(255) DEFAULT NULL COMMENT '浏览器信息',
  `create_time` int(11) DEFAULT 0 COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `wallet_address` (`wallet_address`),
  KEY `create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='XUL钱包登录日志';

-- 创建 SBT 身份记录表
CREATE TABLE `xul_sbt_identity` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `wallet_address` varchar(64) NOT NULL COMMENT '钱包地址',
  `sbt_name` varchar(100) DEFAULT NULL COMMENT 'SBT名称',
  `sbt_score` int(11) DEFAULT 500 COMMENT 'SBT评分',
  `chain_status` tinyint(1) DEFAULT 0 COMMENT '链上状态:0=未同步,1=已同步',
  `create_time` int(11) DEFAULT 0 COMMENT '创建时间',
  `update_time` int(11) DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `wallet_address` (`wallet_address`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='XUL SBT身份记录';
