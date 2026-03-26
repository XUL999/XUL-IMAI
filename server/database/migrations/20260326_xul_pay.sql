-- XUL 代币支付数据库
-- 支付订单表
CREATE TABLE `xul_pay_order` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_no` varchar(64) NOT NULL COMMENT '订单号',
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `money` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '人民币金额',
  `pay_type` varchar(20) NOT NULL DEFAULT 'xul' COMMENT '支付类型:xul=原生代币,power=POWER代币',
  `amount` decimal(18,8) NOT NULL DEFAULT 0.00000000 COMMENT '代币数量',
  `from_address` varchar(64) DEFAULT NULL COMMENT '用户钱包地址',
  `tx_hash` varchar(128) DEFAULT NULL COMMENT '交易哈希',
  `pay_address` varchar(64) NOT NULL COMMENT '收款地址',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态:0=待支付,1=已支付,2=已取消,3=已过期',
  `create_time` int(11) NOT NULL DEFAULT 0 COMMENT '创建时间',
  `pay_time` int(11) DEFAULT 0 COMMENT '支付时间',
  `expire_time` int(11) NOT NULL DEFAULT 0 COMMENT '过期时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_no` (`order_no`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`),
  KEY `create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='XUL代币支付订单表';

-- 为 admin 表添加余额字段
ALTER TABLE `admin` 
ADD COLUMN `xul_balance` decimal(18,8) NOT NULL DEFAULT 0.00000000 COMMENT 'XUL平台余额' AFTER `wallet_address`,
ADD COLUMN `power_balance` decimal(18,8) NOT NULL DEFAULT 0.00000000 COMMENT 'POWER代币余额' AFTER `xul_balance`;
