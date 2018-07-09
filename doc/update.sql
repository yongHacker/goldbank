--2017-10-12
ALTER TABLE `gb_b_procure_storage` ADD COLUMN `procuue_settle_id`  int(11) NULL DEFAULT NULL COMMENT '采购结算单id' AFTER `procurement_id`;

ALTER TABLE `gb_b_procurement` DROP COLUMN `procuue_settle_id`;

--2017-10-12
ALTER TABLE `gb_b_employee` ADD COLUMN `employee_name`  varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '员工姓名' AFTER `job_id`;

ALTER TABLE `gb_b_sectors` MODIFY COLUMN `shop_id`  int(11) NOT NULL DEFAULT 0 COMMENT '门店id' AFTER `company_id`;

ALTER TABLE `gb_b_shop` ADD COLUMN `creator_id`  bigint(20) NOT NULL COMMENT '创建人用户id' AFTER `enable`;

ALTER TABLE `gb_b_shop` ADD COLUMN `create_time`  varchar(11) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '创建时间' AFTER `creator_id`;

--2017-10-13

ALTER TABLE `gb_b_procure_storage` ADD `material_settle` TINYINT( 3 ) NOT NULL DEFAULT '0' COMMENT '是否料结 0否 1是' AFTER `supplier_type` ;
ALTER TABLE `gb_b_procure_storage` CHANGE `price` `price` DECIMAL( 12, 2 ) NOT NULL DEFAULT '0.00' COMMENT '工费及一口价货品总值';
  
--2017-10-16
ALTER TABLE `gb_b_goods` ADD `photo_switch` TINYINT( 2 ) NOT NULL DEFAULT '0' COMMENT '显示图片 0商品规格图 1货品图片' AFTER `bank_gold_type` ;
--
-- 表的结构 `gb_b_product_pic`
--

CREATE TABLE IF NOT EXISTS `gb_b_product_pic` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT '主码id',
  `product_id` bigint(20) NOT NULL COMMENT '货品id',
  `pic` tinyint(2) NOT NULL COMMENT '图片路径',
  `deleted` tinyint(2) NOT NULL DEFAULT '0' COMMENT '是否删除 0否 1是',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='货品图片表' AUTO_INCREMENT=1 ;

ALTER TABLE `gb_a_gold_market` ADD `api_type` TINYINT( 2 ) NOT NULL DEFAULT '0' COMMENT '接口类型 0聚合接口 1 nowapi接口';
 
 --2017-10-17
  ALTER TABLE `gb_b_bank_gold` MODIFY COLUMN `add_amount`  decimal(7,2) NOT NULL DEFAULT 0.00 COMMENT '加/减的值' AFTER `shop_id`;

  ALTER TABLE `gb_b_bank_gold` ADD COLUMN `formula`  text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '计算公式' AFTER `add_amount`;

 ALTER TABLE `gb_b_bank_gold_log` ADD COLUMN `formula`  text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '计算公式' AFTER `bg_type`;
 
 ALTER TABLE `gb_b_bank_gold` MODIFY COLUMN `bg_type`  int(11) NOT NULL COMMENT '金价类型id' AFTER `formula`;
ALTER TABLE `gb_b_bank_gold_log` MODIFY COLUMN `bg_type`  int(11) NOT NULL COMMENT '金价类型id' AFTER `company_id`;

ALTER TABLE `gb_a_employee` ADD COLUMN `workid` INT(10) NOT NULL   COMMENT '工号' AFTER `id`;

--2017-10-18
ALTER TABLE `gb_b_supplier` ADD `c_id` INT( 11 ) NOT NULL DEFAULT '0' COMMENT '供应商所关联的商户id' AFTER `contact_member` ,
ADD `credit_code` VARCHAR( 100 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT NULL COMMENT '供应商社会信用代码' AFTER `c_id` ;

ALTER TABLE `gb_b_supplier` DROP `weight`, DROP `price`;

ALTER TABLE `gb_b_procurement` ADD `procuue_settle_id` INT( 11 ) NOT NULL COMMENT '采购结算单id' AFTER `company_id` ;

ALTER TABLE `gb_b_procurement` ADD `supplier_id` INT( 11 ) NOT NULL COMMENT '供应商id' AFTER `procure_time` ,
ADD `material_settle` TINYINT( 3 ) NOT NULL DEFAULT '0' COMMENT '是否料结 0否 1是' AFTER `supplier_id` ,
ADD `pricemode` TINYINT( 3 ) NOT NULL DEFAULT '0' COMMENT '计价方式0计件 1计重' AFTER `material_settle` ;

ALTER TABLE `gb_b_procure_storage` ADD `fee` DECIMAL( 12, 2 ) NOT NULL DEFAULT '0.00' COMMENT '这批货的克工费' AFTER `procurement_id` ;

ALTER TABLE `gb_b_procure_storage` ADD `storager_id` BIGINT( 20 ) NOT NULL DEFAULT '0' COMMENT '入库人id' AFTER `check_memo` ,
ADD `storage_memo` TEXT NOT NULL DEFAULT '' COMMENT '入库备注' AFTER `storager_id` ,
ADD `storage_status` TINYINT( 3 ) NOT NULL DEFAULT '0' COMMENT '入库状态 0未完成入库 1已完成入库' AFTER `storage_memo` ;

ALTER TABLE `gb_b_procure_storage` DROP `procuue_settle_id` ,DROP `supplier_id` ,DROP `supplier_type` ,DROP `material_settle` ;

ALTER TABLE `gb_b_supplier` ADD `email` VARCHAR( 255 ) NOT NULL COMMENT '供应商邮箱' AFTER `phone` ;



CREATE TABLE `gb_b_company_account` (
`id`  int UNSIGNED NOT NULL AUTO_INCREMENT ,
`f_cid`  int NULL DEFAULT 0 COMMENT '商户id' ,
`t_cid`  int NULL DEFAULT 0 COMMENT '供应商id' ,
`price`  decimal(12,2) NULL DEFAULT 0.00 COMMENT '欠款' ,
`total_price`  decimal(15,2) NULL DEFAULT 0.00 COMMENT '进货总钱数' ,
`settle_price`  decimal(15,2) NULL DEFAULT 0.00 COMMENT '已结算钱数' ,
`weight`  decimal(12,2) NULL DEFAULT 0.00 COMMENT '欠克数' ,
`total_weight`  decimal(15,2) NULL DEFAULT 0.00 COMMENT '进货总克重' ,
`settle_weight`  decimal(15,2) NULL DEFAULT 0.00 COMMENT '已结算总克重' ,
`deleted`  tinyint(3) NULL DEFAULT 0 COMMENT '是否删除 0否 1是' ,
PRIMARY KEY (`id`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='商户记账账户表' AUTO_INCREMENT=1;

ALTER TABLE `gb_b_procurement`
CHANGE COLUMN `procuue_settle_id` `procure_settle_id`  int(11) NOT NULL COMMENT '采购结算单id' AFTER `company_id`;

--2017-10-19

ALTER TABLE `gb_b_procurement` CHANGE `status` `status` TINYINT( 3 ) NOT NULL DEFAULT '0' COMMENT '状态 0待审核 1审核用过 2审核不通过 3已撤销';

--2017-10-20

ALTER TABLE `gb_b_procurement` ADD `bill_pic` TEXT NULL DEFAULT NULL COMMENT '发票图片' AFTER `price`;

ALTER TABLE `gb_b_procurement` CHANGE `status` `status` TINYINT( 3 ) NOT NULL DEFAULT '0' COMMENT '状态 0待审核 1审核用过 2审核不通过 3已撤销 4已结算';

ALTER TABLE `gb_b_procure_settle` ADD `material_weight` DECIMAL( 12, 2 ) NOT NULL DEFAULT '0.00' COMMENT '买金料克重' AFTER `price` ,ADD `material_g_price` DECIMAL( 12, 2 ) NOT NULL DEFAULT '0.00' COMMENT '金料克单价' AFTER `material_weight` ;

ALTER TABLE `gb_b_procurement` DROP `material_settle`;

--2017-10-23
CREATE TABLE `gb_b_metal_price` ( `id`  int(11) UNSIGNED NOT NULL AUTO_INCREMENT , `price`  decimal(7,2) NOT NULL , `b_metal_type_id`  int(11) NOT NULL , `user_id`  bigint(20) NOT NULL COMMENT '用户id' , `create_time`  varchar(12) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '创建时间' , `deleted`  tinyint(2) NOT NULL COMMENT '是否删除 0否 1是' , PRIMARY KEY (`id`) ) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=Compact ;

CREATE TABLE `gb_b_metal_type` ( `id`  int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'b端金属类型id' , `company_id`  int(11) NOT NULL COMMENT '商户id' , `pid`  int(11) NOT NULL COMMENT '父类金属类型id' , `level`  tinyint(2) NOT NULL DEFAULT 0 COMMENT '级数' , `name`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '金属类型名称' , `agc_id`  int(11) NOT NULL COMMENT '关联a端s_gold_category贵金属类型id' , `is_relation`  tinyint(2) NOT NULL DEFAULT 0 COMMENT '是否关联 1是，0否' , `update_time`  varchar(12) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '修改时间' , `status`  tinyint(2) NOT NULL DEFAULT 0 COMMENT '开启状态(1：开启，0：关闭)' , `deleted`  tinyint(2) NOT NULL DEFAULT 0 COMMENT '是否删除 0否 1是' , PRIMARY KEY (`id`) ) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=Compact ;

ALTER TABLE `gb_b_employee` ADD COLUMN `company_id`  int(11) NOT NULL COMMENT '商户id' AFTER `id`;

ALTER TABLE `gb_b_employee` ADD COLUMN `shop_id`  int(11) NOT NULL COMMENT '门店id(0代表商户总部 其他代表门店）' AFTER `company_id`;

-- 2017-10-25
ALTER TABLE `gb_b_company_account`
DROP COLUMN `f_cid`,
ADD COLUMN `c_id`  int NULL DEFAULT 0 COMMENT '商户id' AFTER `id`;

CREATE TABLE `gb_b_caccount_record` (
`id`  bigint UNSIGNED NOT NULL AUTO_INCREMENT ,
`company_id`  int NULL DEFAULT 0 COMMENT '商户id' ,
`account_id`  int NULL DEFAULT 0 COMMENT '账户id' ,
`flow_id`  varchar(50) NULL COMMENT '方式拼接外部订单id(price_14,gold_15,bean_12)...' ,
`price`  decimal(12,2) NULL DEFAULT 0.00 COMMENT '收款金额' ,
`creator_id`  bigint NULL DEFAULT 0 COMMENT '创建人id' ,
`create_time`  varchar(12) NULL COMMENT '制单时间' ,
`memo`  text NULL COMMENT '备注' ,
`check_id`  bigint NULL DEFAULT 0 COMMENT '审核人id' ,
`check_time`  varchar(12) NULL COMMENT '审核时间' ,
`check_memo`  text NULL COMMENT '审核备注' ,
`status`  tinyint NULL DEFAULT 0 COMMENT '状态默认0 未审核,1 已审核, 2审核不通过' ,
`deleted`  tinyint NULL DEFAULT 0 COMMENT '是否删除 默认0否, 1是' ,
PRIMARY KEY (`id`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='商户记账流水表（钱）' AUTO_INCREMENT=1;

CREATE TABLE `gb_b_material_record` (
`id`  bigint NOT NULL AUTO_INCREMENT COMMENT 'id' ,
`c_id`  int NULL DEFAULT 0 COMMENT '商户id' ,
`t_cid`  int NULL DEFAULT 0 COMMENT '关系商户id' ,
`flow_id`  varchar(50) NULL COMMENT '方式拼接外部订单id(price_14, gold_15, bean_12)...' ,
`weight`  decimal(12,2) NULL DEFAULT 0.00 COMMENT '来料克重' ,
`creator_id`  bigint NULL DEFAULT 0 COMMENT '开单人id' ,
`create_time`  varchar(12) NULL COMMENT '制单时间' ,
`memo`  text NULL COMMENT '备注' ,
`check_id`  bigint NULL DEFAULT 0 COMMENT '审核人id' ,
`check_time`  varchar(12) NULL COMMENT '审核时间' ,
`check_memo`  text NULL COMMENT '审核备注' ,
`status`  tinyint NULL DEFAULT 0 COMMENT '状态默认0 未审核, 1已审核, 2审核不通过' ,
`deleted`  tinyint NULL DEFAULT 0 COMMENT '是否删除 默认0否 1是' ,
PRIMARY KEY (`id`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='来往金料记录表' AUTO_INCREMENT=1;

CREATE TABLE `gb_b_material_order` (
`id`  bigint NOT NULL AUTO_INCREMENT COMMENT 'id' ,
`c_id`  int NULL DEFAULT 0 COMMENT '商户id' ,
`t_cid`  int NULL DEFAULT 0 COMMENT '关系商户id' ,
`flow_id`  varchar(50) NULL COMMENT '方式拼接外部订单id(price_14, gold_15, bean_12)...' ,
`weight`  decimal(12,2) NULL DEFAULT 0.00 COMMENT '买料克重' ,
`mgold_price` DECIMAL(12,2)  NULL DEFAULT 0.00 COMMENT '料金金价',
`creator_id`  bigint NULL DEFAULT 0 COMMENT '开单人id' ,
`create_time`  varchar(12) NULL COMMENT '制单时间' ,
`memo`  text NULL COMMENT '备注' ,
`check_id`  bigint NULL DEFAULT 0 COMMENT '审核人id' ,
`check_time`  varchar(12) NULL COMMENT '审核时间' ,
`check_memo`  text NULL COMMENT '审核备注' ,
`status`  tinyint NULL DEFAULT 0 COMMENT '状态默认0 未审核, 1已审核, 2审核不通过' ,
`deleted`  tinyint NULL DEFAULT 0 COMMENT '是否删除 默认0否 1是' ,
PRIMARY KEY (`id`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='买料记录表' AUTO_INCREMENT=1;

ALTER TABLE `gb_b_procure_settle`
CHANGE COLUMN `storage_time` `settle_time`  varchar(12) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '结算时间' AFTER `material_g_price`,
ADD COLUMN `mrecord_id`  bigint NULL DEFAULT 0 COMMENT '来往金料表id' AFTER `material_g_price`,
ADD COLUMN `morder_id`  bigint NULL DEFAULT 0 COMMENT '买料记录表id' AFTER `mrecord_id`;

ALTER TABLE `gb_b_product`
MODIFY COLUMN `sell_time`  varchar(12) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT 0 COMMENT '销售日期' AFTER `actual_price`;

ALTER TABLE `gb_b_caccount_record`
MODIFY COLUMN `price`  decimal(12,2) NULL DEFAULT 0.00 COMMENT '付款金额（工费及一口价金额）' AFTER `flow_id`;

CREATE TABLE `gb_b_procure_settle_relation` (
`id`  bigint NOT NULL AUTO_INCREMENT ,
`procure_settle_id`  int NULL COMMENT '采购结算id' ,
`procure_id`  int NULL COMMENT '采购单id' ,
`create_time`  varchar(12) NULL ,
PRIMARY KEY (`id`)
)COMMENT='采购结算与采购关系列表' AUTO_INCREMENT=1;

ALTER TABLE `gb_b_company` ADD COLUMN `check_remark` VARCHAR(200) NULL   COMMENT '审核备注' AFTER `check_time`;

ALTER TABLE `gb_b_product`
ADD COLUMN `real_pick_price`  decimal(10,2) NULL DEFAULT 0.00 COMMENT '实际配货价' AFTER `pick_price`;

ALTER TABLE `gb_b_company`
ADD COLUMN `is_active`  tinyint(3) NULL DEFAULT 0 COMMENT '是否激活默认0否，1是' AFTER `parent_id`;

ALTER TABLE `goldbank_test`.`gb_b_company_contribute`   
  DROP COLUMN `type`, 
  DROP COLUMN `num`, 
  ADD COLUMN `service_year` INT(5) DEFAULT 0  NULL   COMMENT '增加服务年限' AFTER `price`,
  ADD COLUMN `company_num` INT(5) DEFAULT 0  NULL   COMMENT '增加加盟商数量' AFTER `service_year`,
  ADD COLUMN `shop_num` INT(5) DEFAULT 0  NULL   COMMENT '增加门店数量' AFTER `company_num`;
  
ALTER TABLE `gb_b_procurement`   CHANGE `pricemode` `pricemode` TINYINT(3) DEFAULT 0  NOT NULL   COMMENT '计价方式0计件 1计重 2金料采购';


-- 2018-03-22
ALTER TABLE `gb_b_product`
ADD COLUMN `unpass`  tinyint(2) NULL DEFAULT 0 COMMENT '审核不通过标识，默认0正常，1分称审核不通过的货品' AFTER `type`;

-- 2018-03-23 batch varchar(20) 改为 varchar(50)
ALTER TABLE `gb_b_procurement`
MODIFY COLUMN `batch`  varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '采购单批次' AFTER `id`;

ALTER TABLE `gb_b_procure_storage`
MODIFY COLUMN `batch`  varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '采购入库单批次' AFTER `company_id`;

ALTER TABLE `gb_b_procure_settle`
MODIFY COLUMN `batch`  varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '结算单批次' AFTER `supplier_id`;

ALTER TABLE `gb_b_allot`
MODIFY COLUMN `batch`  varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '调拨批次' AFTER `company_id`;

ALTER TABLE `gb_b_wallot`
MODIFY COLUMN `batch`  varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '调拨批次' AFTER `company_id`;

ALTER TABLE `gb_b_outbound_order`
MODIFY COLUMN `batch`  varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '出库批次' AFTER `id`;

ALTER TABLE `gb_b_wholesale_procure`
MODIFY COLUMN `batch`  varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '采购单批次' AFTER `id`;

--2018-03-12-23 chenzy
ALTER TABLE  `gb_b_auth_access` ADD  `company_id` INT( 11 ) NOT NULL DEFAULT  '0' COMMENT  '商户id';
ALTER TABLE  `gb_b_role_user` ADD  `company_id` INT( 11 ) NOT NULL DEFAULT  '0' COMMENT  '商户id';
ALTER TABLE  `gb_b_goods` ADD  `company_id` INT( 11 ) NOT NULL DEFAULT  '0' COMMENT  '商户id';
ALTER TABLE  `gb_b_wgoods` ADD  `company_id` INT( 11 ) NOT NULL DEFAULT  '0' COMMENT  '商户id';
ALTER TABLE  `gb_b_wgoods_stock` ADD  `company_id` INT( 11 ) NOT NULL DEFAULT  '0' COMMENT  '商户id';
ALTER TABLE  `gb_b_goldgoods_detail` ADD  `company_id` INT( 11 ) NOT NULL DEFAULT  '0' COMMENT  '商户id';
ALTER TABLE  `gb_b_goldgoods_wholesale` ADD  `company_id` INT( 11 ) NOT NULL DEFAULT  '0' COMMENT  '商户id';
ALTER TABLE  `gb_b_goods_pic` ADD  `company_id` INT( 11 ) NOT NULL DEFAULT  '0' COMMENT  '商户id';
ALTER TABLE  `gb_b_product_gold` ADD  `company_id` INT( 11 ) NOT NULL DEFAULT  '0' COMMENT  '商户id';
ALTER TABLE  `gb_b_product_goldm` ADD  `company_id` INT( 11 ) NOT NULL DEFAULT  '0' COMMENT  '商户id';
ALTER TABLE  `gb_b_product_diamond` ADD  `company_id` INT( 11 ) NOT NULL DEFAULT  '0' COMMENT  '商户id';
ALTER TABLE  `gb_b_product_inlay` ADD  `company_id` INT( 11 ) NOT NULL DEFAULT  '0' COMMENT  '商户id';
ALTER TABLE  `gb_b_product_jade` ADD  `company_id` INT( 11 ) NOT NULL DEFAULT  '0' COMMENT  '商户id';
ALTER TABLE  `gb_b_article` ADD  `company_id` INT( 11 ) NOT NULL DEFAULT  '0' COMMENT  '商户id';
ALTER TABLE  `gb_b_allot_detail` ADD  `company_id` INT( 11 ) NOT NULL DEFAULT  '0' COMMENT  '商户id';
ALTER TABLE  `gb_b_sell_detail` ADD  `company_id` INT( 11 ) NOT NULL DEFAULT  '0' COMMENT  '商户id';
ALTER TABLE  `gb_b_company_account` CHANGE  `c_id`  `company_id` INT( 11 ) NULL DEFAULT  '0' COMMENT  '商户id';

--2018-03-23 lzy
ALTER TABLE `gb_m_users` ADD `id_no` VARCHAR( 50 ) NOT NULL DEFAULT '0' COMMENT '身份证号码' AFTER `realname` ;
ALTER TABLE `gb_m_users` DROP `is_real_name`, DROP `is_bank`;

--2018-03-26 chenzy
CREATE TABLE `gb_b_recovery` (
`id`  bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '回购单id' ,
`batch`  varchar(50) NOT NULL COMMENT '回购单批次' ,
`company_id`  int(11) NOT NULL DEFAULT 0 COMMENT '商户id' ,
`shop_id`  int(11) NOT NULL DEFAULT 0 COMMENT '门店id为0时代表商户总部' ,
`recovery_time`  varchar(12) NULL COMMENT '回购时间' ,
`num`  int(11) NOT NULL DEFAULT 0 COMMENT '回购数量' ,
`price`  decimal(16,2) NOT NULL DEFAULT 0.00 COMMENT '回购总价' ,
`creator_id`  bigint(20) NOT NULL DEFAULT 0 COMMENT '回购人id' ,
`create_time`  varchar(12) NULL COMMENT '创建时间' ,
`memo`  varchar(255) NULL COMMENT '制单员备注' ,
`f_uid`  bigint(20) NOT NULL COMMENT '售卖人id' ,
`name`  varchar(50) NULL COMMENT '售卖人姓名' ,
`id_no`  varchar(50) NULL COMMENT '售卖人身份证' ,
`check_id`  bigint(20) NOT NULL COMMENT '审核人id' ,
`check_time`  varchar(12) NULL COMMENT '审核时间' ,
`check_memo`  varchar(255) NULL COMMENT '审核备注' ,
`status`  tinyint(3) NOT NULL DEFAULT 0 COMMENT '状态 0待审核 1审核用过 2审核不通过3已撤销' ,
`deleted`  tinyint(3) NOT NULL DEFAULT 0 COMMENT '是否删除0否1是' ,
PRIMARY KEY (`id`)
)
COMMENT='旧金回收单据表';

CREATE TABLE `gb_b_recovery_product` (
`id`  bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '旧金货品id' ,
`recovery_id`  bigint(20) NOT NULL COMMENT '回购单id' ,
`company_id`  int(11) NOT NULL DEFAULT 0 COMMENT '商户id' ,
`recovery_price`  decimal(7,2) NOT NULL COMMENT '回购金价' ,
`total_weight`  decimal(15,3) NOT NULL DEFAULT 0.000 COMMENT '总重' ,
`gold_weight`  decimal(15,3) NOT NULL DEFAULT 0.000 COMMENT '金重' ,
`purity`  decimal(6,6) NOT NULL DEFAULT 0.000000 COMMENT '纯度' ,
`attrition`  decimal(6,6) NOT NULL DEFAULT 0.000000 COMMENT '损耗率' ,
`cost_price`  decimal(15,2) NOT NULL ,
`type`  tinyint(3) NOT NULL DEFAULT 0 COMMENT '类型0回购2截金回购' ,
`sn_id`  bigint(20) NULL COMMENT '如果是截金销售，关联的货品id' ,
`status`  tinyint(3) NOT NULL DEFAULT 0 COMMENT '状态0不可用 1可用' ,
`deleted`  tinyint(3) NOT NULL DEFAULT 0 COMMENT '是否删除0否1是' ,
PRIMARY KEY (`id`),
INDEX `recovery_id` (`recovery_id`) USING BTREE 
)
COMMENT='旧金货品表';

--2018-03-26 lzy
ALTER TABLE `gb_b_article_class` CHANGE `company_id` `company_id` INT( 11 ) NOT NULL DEFAULT '0' COMMENT '登录商户id';

--2018-03-27 lzy
ALTER TABLE `gb_b_product_goldm` DROP `meterage_sml`, DROP `meterage_ans`;
ALTER TABLE `gb_b_product` DROP `qc_name`,DROP `pick_order_id`, DROP `pick_time`, DROP `pick_price`, DROP `real_pick_price`, DROP `procure_settle_id`;

--2018-03-27 mjk 
ALTER TABLE `gb_b_supplier`
DROP COLUMN `company_id`,
DROP COLUMN `contact_member`,
DROP COLUMN `c_id`,
DROP COLUMN `contact_phone`,
DROP COLUMN `email`,
DROP COLUMN `status`,
DROP COLUMN `deleted`;

ALTER TABLE `gb_b_company_account`
CHANGE COLUMN `t_cid` `supplier_id`  int(11) NULL DEFAULT 0 COMMENT '供应商id' AFTER `company_id`,
ADD COLUMN `contact_member`  varchar(20) NULL COMMENT '联系人姓名' AFTER `supplier_id`,
ADD COLUMN `contact_phone`  varchar(20) NULL COMMENT '联系人电话' AFTER `contact_member`,
ADD COLUMN `status`  tinyint(3) NULL DEFAULT 1 COMMENT '状态0锁定1正常' AFTER `settle_weight`;

ALTER TABLE `gb_b_procurement`
DROP COLUMN `procure_settle_id`;

ALTER TABLE `gb_b_procure_storage`
DROP COLUMN `all_price`,
DROP COLUMN `goods_class_id`,
ADD COLUMN `type`  tinyint(2) NULL DEFAULT 0 COMMENT '类型: 1素金2金料3钻石4镶嵌5玉石6摆件' AFTER `real_weight`;

ALTER TABLE `gb_b_product_gold`
DROP COLUMN `pick_g_price`,
DROP COLUMN `pick_m_fee`;

ALTER TABLE `gb_b_goldgoods_detail`
DROP COLUMN `pick_pricemode`,
DROP COLUMN `pick_fee`;

ALTER TABLE `gb_b_goods`
DROP COLUMN `pick_pricemode`,
DROP COLUMN `pick_price`;

ALTER TABLE `gb_b_wgoods`
DROP COLUMN `pick_price`;

CREATE TABLE `gb_b_material_record_detail` (
`id`  int NOT NULL AUTO_INCREMENT ,
`company_id`  int NOT NULL COMMENT '商户id' ,
`mr_id`  bigint(20) NOT NULL COMMENT '来往料记录material_record.id' ,
`product_mid`  bigint(20) NULL COMMENT '金料货品id' ,
`deleted`  tinyint(3) NULL DEFAULT 0 COMMENT '是否删除 0否 1是' ,
PRIMARY KEY (`id`)
)
COMMENT='来往料记录明细表';

--20180329 chenzy
ALTER TABLE `gb_b_bank_gold` CHANGE COLUMN `bg_type` `bgt_id`  int(11) NOT NULL COMMENT '金价类型id' AFTER `formula`;
ALTER TABLE `gb_b_bank_gold_log` CHANGE COLUMN `bg_type` `bgt_id`  int(11) NOT NULL COMMENT '金价类型id' AFTER `company_id`;

--2018-03-30 mjk
ALTER TABLE `gb_b_procure_settle`
DROP COLUMN `weight`,
DROP COLUMN `price`,
DROP COLUMN `material_weight`,
DROP COLUMN `material_g_price`,
DROP COLUMN `sell_weight`,
DROP COLUMN `sell_price`,
DROP COLUMN `mrecord_weihgt`,
ADD COLUMN `ca_record_id`  bigint(20) NULL DEFAULT 0 COMMENT '来往钱记录id' AFTER `batch`,
ADD COLUMN `mrecord_id`  bigint(20) NULL DEFAULT 0 COMMENT '来往金料表id' AFTER `ca_record_id`;

ALTER TABLE `gb_b_caccount_record`
DROP COLUMN `flow_id`,
DROP COLUMN `procure_settle_id`,
DROP COLUMN `weight`,
DROP COLUMN `type`;

ALTER TABLE `gb_b_material_record`
DROP COLUMN `flow_id`,
CHANGE COLUMN `c_id` `company_id`  int(11) NULL DEFAULT 0 COMMENT '商户id' AFTER `id`,
CHANGE COLUMN `t_cid` `account_id`  bigint(20) NULL DEFAULT 0 COMMENT '商户结欠账户id' AFTER `company_id`;

ALTER TABLE `gb_b_material_order`
DROP COLUMN `flow_id`,
DROP COLUMN `type`,
CHANGE COLUMN `c_id` `company_id`  int(11) NULL DEFAULT 0 COMMENT '商户id' AFTER `id`,
CHANGE COLUMN `t_cid` `account_id`  bigint(20) NULL DEFAULT 0 COMMENT '商户结欠账户id' AFTER `company_id`;

ALTER TABLE `gb_b_procure_settle`
MODIFY COLUMN `status`  tinyint(3) NOT NULL DEFAULT 0 COMMENT '状态 -1保存 0待审核 1审核用过 2审核不通过 3已撤销 4已结算' AFTER `type`;

ALTER TABLE `gb_b_material_order`
MODIFY COLUMN `status`  tinyint(4) NULL DEFAULT 0 COMMENT '状态默认0 未审核, 1已审核, 2审核不通过 3撤销' AFTER `check_memo`;

ALTER TABLE `gb_b_material_record`
MODIFY COLUMN `status`  tinyint(4) NULL DEFAULT 0 COMMENT '状态默认0 未审核, 1已审核, 2审核不通过 3撤销' AFTER `check_memo`;

--2018-04-02 mjk
ALTER TABLE `gb_b_company_contribute`
ADD COLUMN `contribute_no`  varchar(50) NOT NULL COMMENT '授权单号' AFTER `company_id`,
ADD COLUMN `contract_sn`  varchar(50) NOT NULL COMMENT '合同编号' AFTER `contribute_no`;

--2018-04-02 lzy
ALTER TABLE `gb_b_product_gold` DROP `meterage_sml`, DROP `meterage_ans`;
ALTER TABLE `gb_b_product_gold` ADD `is_cut` TINYINT( 2 ) NOT NULL DEFAULT '0' COMMENT '是否截金 0否 1是' AFTER `weight` ,
ADD `cut_weight` DECIMAL( 10, 2 ) NOT NULL DEFAULT '0.00' COMMENT '截金后的重量' AFTER `is_cut` 
ALTER TABLE `gb_b_recovery_product` ADD `service_fee` DECIMAL( 15, 2 ) NOT NULL DEFAULT '0.00' COMMENT '服务克工费' AFTER `gold_weight` 

--20180402 chenzy
CREATE TABLE `gb_b_product_pic` (
`id`  int(11) NOT NULL ,
`product_id`  bigint(20) NOT NULL COMMENT '货品id' ,
`pic`  varchar(50) NOT NULL COMMENT '图片路径' ,
`company_id`  int(11) NOT NULL DEFAULT 0 COMMENT '商户id' ,
`deleted`  tinyint(3) NOT NULL DEFAULT 0 COMMENT '是否删除 0否 1是' ,
PRIMARY KEY (`id`),
INDEX `product_id` (`product_id`) 
)
COMMENT='货品图片表';

--20180403 mjk
CREATE TABLE `gb_b_company_account_flow` (
`id`  bigint NOT NULL AUTO_INCREMENT ,
`company_id`  int NOT NULL DEFAULT 0 COMMENT '商户id' ,
`account_id`  int NOT NULL DEFAULT 0 COMMENT '结欠账户id' ,
`sn_id`  int NULL COMMENT '关联其他表的id（采购单id，结算单id，来往钱id，来往料id，买卖料id...）' ,
`weight`  double(15,2) NULL DEFAULT 0.00 COMMENT '克重（有正负）' ,
`price`  double(15,2) NULL DEFAULT 0.00 COMMENT '金额（有正负）' ,
`type`  tinyint NULL DEFAULT 0 COMMENT '类型1采购2结算3来往钱4来往料5买卖料' ,
PRIMARY KEY (`id`)
)
COMMENT='商户结欠账户流水记录'
;
--20180403 chenzy
ALTER TABLE `gb_b_recovery` ADD COLUMN `type`  tinyint(3) NOT NULL DEFAULT 0 COMMENT '类型 0旧金回购1 截金回购' AFTER `status`;
ALTER TABLE `gb_b_recovery_product` ADD COLUMN `gold_price`  decimal(7,2) NOT NULL DEFAULT 0.00 COMMENT '当前金价' AFTER `gold_weight`;
ALTER TABLE `gb_b_recovery_product` DROP COLUMN `type`;
ALTER TABLE `gb_b_recovery_product` CHANGE COLUMN `recovery_id` `order_id`  bigint(20) NOT NULL COMMENT '回购单id' AFTER `id`;
ALTER TABLE `gb_b_recovery_product` CHANGE COLUMN `sn_id` `product_id`  bigint(20) NULL DEFAULT NULL COMMENT '如果是截金销售，关联的货品id' AFTER `cost_price`;

CREATE TABLE `gb_b_sell_cut_product` (
`id`  bigint(20) NOT NULL AUTO_INCREMENT COMMENT '主码id' ,
`sell_id`  bigint(20) NOT NULL COMMENT '销售单id' ,
`company_id`  int(11) NOT NULL COMMENT '商户id' ,
`product_id`  bigint(20) NOT NULL COMMENT '货品id' ,
`cut_gold_price`  decimal(7,2) NOT NULL DEFAULT 0.00 COMMENT '截金金价' ,
`gold_weight`  decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT '金重' ,
`service_fee`  decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT '服务克工费' ,
`cost_price`  decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT '抵扣费用' ,
`deleted`  tinyint(3) NOT NULL DEFAULT 0 COMMENT '是否删除0否1是' ,
PRIMARY KEY (`id`),
INDEX `sell_id` (`sell_id`) 
)
COMMENT='销售截金详情表';

ALTER TABLE `gb_b_product_gold` ADD COLUMN `sell_weight`  decimal(10,2) NULL DEFAULT 0.00 COMMENT '实称克重' AFTER `weight`;


--20180404 mjk
ALTER TABLE `gb_b_company_account_flow`
MODIFY COLUMN `type`  tinyint(4) NULL DEFAULT 0 COMMENT '类型1采购2来往钱3来往料4买卖料' AFTER `price`;

--20180404
ALTER TABLE `gb_b_bank_gold`
ADD COLUMN `parent_id`  int(11) NOT NULL DEFAULT 0 COMMENT '父类id' AFTER `updater`;

--20180409 mjk
ALTER TABLE `gb_b_menu`
ADD COLUMN `is_shop`  tinyint(3) NULL DEFAULT 0 COMMENT '门店标识0否 1是' AFTER `type`;

ALTER TABLE `gb_b_menu`
DROP COLUMN `company_id`;

--20180411 mjk
ALTER TABLE `gb_b_bank_gold`
ADD COLUMN `parent_id`  int NULL DEFAULT 0 COMMENT '上级id' AFTER `shop_id`;

ALTER TABLE `gb_b_recovery_product`
ADD COLUMN `service_fee`  decimal(15,2) NULL DEFAULT 0.00 COMMENT '服务克工费' AFTER `gold_price`;

ALTER TABLE `gb_b_sell_cut_product`
ADD COLUMN `purity`  decimal(6,6) NULL DEFAULT 0.00 COMMENT '纯度' AFTER `gold_weight`;

--20180413 mjk
ALTER TABLE `gb_b_procure_settle`
ADD COLUMN `before_weight`  decimal(15,2) NULL DEFAULT 0.00 COMMENT '结算通过前欠克重' AFTER `check_memo`,
ADD COLUMN `before_price`  decimal(15,2) NULL DEFAULT 0.00 COMMENT '结算通过前欠金额' AFTER `before_weight`,
ADD COLUMN `after_weight`  decimal(15,2) NULL DEFAULT 0.00 COMMENT '结算通过后还欠克重' AFTER `before_price`,
ADD COLUMN `after_price`  decimal(15,2) NULL DEFAULT 0.00 COMMENT '结算通过后还欠金额' AFTER `after_weight`;
--2018-04-13 chenzy
ALTER TABLE `gb_b_metal_type` DROP COLUMN `price`;
ALTER TABLE `gb_b_outbound_order` ADD COLUMN `warehouse_id`  int(11) NOT NULL DEFAULT  COMMENT '仓库id' AFTER `company_id`;
ALTER TABLE `gb_a_options` ADD COLUMN `memo`  varchar(255) NULL COMMENT '备注' AFTER `option_value`;

--20180417 mjk
ALTER TABLE `gb_b_recovery_product`
ADD COLUMN `rproduct_code`  varchar(20) NULL COMMENT '货品编码' AFTER `company_id`;
ALTER TABLE `gb_b_recovery_product`
ADD COLUMN `storage_id`  int NULL DEFAULT 0 COMMENT '如果为旧金采购得来，采购包裹单id' AFTER `order_id`;
ALTER TABLE `gb_b_recovery_product`
ADD COLUMN `recovery_name`  varchar(255) NULL COMMENT '金料名称' AFTER `company_id`;
ALTER TABLE `gb_b_recovery_product`
ADD COLUMN `type`  tinyint(3) NULL DEFAULT 0 COMMENT '类型 0回购 1销售截金 2金料' AFTER `cost_price`;
ALTER TABLE `gb_b_recovery_product`
MODIFY COLUMN `status`  tinyint(3) NOT NULL DEFAULT 0 COMMENT '状态 0待审核 1审核通过 2审核不通过 3已撤销 4结算中 5结算完成' AFTER `product_id`;


--20180418 mjk
ALTER TABLE `gb_b_recovery_product`
MODIFY COLUMN `order_id`  bigint(20) NOT NULL COMMENT '回购单id 结算单id' AFTER `id`,
MODIFY COLUMN `type`  tinyint(3) NULL DEFAULT 0 COMMENT '类型 0回购 1销售截金 2金料 3结算来料' AFTER `cost_price`,
ADD COLUMN `f_rproduct_code`  varchar(20) NULL COMMENT '来自货品编码' AFTER `recovery_name`;

ALTER TABLE `gb_b_procure_settle`
MODIFY COLUMN `mrecord_id`  bigint(20) NULL DEFAULT 0 COMMENT '去料金料表id' AFTER `ca_record_id`,
ADD COLUMN `mrecord_id_2`  bigint(20) NULL DEFAULT 0 COMMENT '来料金料表id' AFTER `ca_record_id`;

-- 20180419 mjk
ALTER TABLE `gb_b_recovery_product`
MODIFY COLUMN `purity`  decimal(7,6) NOT NULL DEFAULT 0.000000 COMMENT '纯度' AFTER `service_fee`,
MODIFY COLUMN `attrition`  decimal(7,6) NOT NULL DEFAULT 0.000000 COMMENT '损耗率' AFTER `purity`;

--20180420 mjk
ALTER TABLE `gb_b_sell_cut_product`
ADD COLUMN `rproduct_code`  varchar(20) NULL COMMENT '金料编号' AFTER `product_id`;

--201804-23
ALTER TABLE `gb_b_client`
ADD COLUMN `client_name`  varchar(50) NULL COMMENT '客户姓名' AFTER `user_id`,
ADD COLUMN `client_moblie`  varchar(20) NULL COMMENT '客户电话' AFTER `client_name`;
ALTER TABLE `gb_a_employee`
ADD COLUMN `employee_mobile`  varchar(20) NULL COMMENT '员工手机号' AFTER `id`,
ADD COLUMN `employee_name`  varchar(50) NULL COMMENT '员工姓名' AFTER `employee_mobile`;
ALTER TABLE `gb_b_employee`
ADD COLUMN `emloyee_mobile`  varchar(20) NULL COMMENT '员工手机号' AFTER `job_id`;
ALTER TABLE `gb_b_employee`
CHANGE COLUMN `emloyee_mobile` `employee_mobile`  varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '员工手机号' AFTER `job_id`;

update gb_a_employee a,gb_m_users b set a.employee_name=b.user_nicename,a.employee_mobile=b.mobile where  a.user_id=b.id;
update gb_b_employee a,gb_m_users b set a.employee_name=b.user_nicename,a.employee_mobile=b.mobile where  a.user_id=b.id;
CREATE TABLE `gb_m_user_source` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) NOT NULL COMMENT '用户id',
  `add_source` varchar(11) NOT NULL COMMENT '添加来源',
  `add_rule_name` varchar(50) NOT NULL COMMENT '添加来源的路由地址',
  `belong` varchar(11) NOT NULL COMMENT '归属',
  `belog_rule_name` varchar(50) NOT NULL COMMENT '归属的路由地址',
  `add_type` tinyint(3) NOT NULL DEFAULT '0' COMMENT '添加来源类型 0 云掌柜',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COMMENT='用户来源归属表';


--20180423  lzy
ALTER TABLE `gb_a_auth_access` ADD `id` INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY COMMENT '主码id' FIRST ;
ALTER TABLE `gb_a_role_user` ADD `id` INT( 11 ) NOT NULL AUTO_INCREMENT COMMENT '主码id' FIRST ,
ADD PRIMARY KEY ( `id` ) ;
ALTER TABLE `gb_b_auth_access` ADD `id` BIGINT( 20 ) NOT NULL AUTO_INCREMENT PRIMARY KEY COMMENT '主码id' FIRST ;

--20180424   lzy
ALTER TABLE `gb_b_role_user` ADD `id` INT( 11 ) NOT NULL AUTO_INCREMENT COMMENT '主码id' FIRST ,
ADD PRIMARY KEY ( `id` ) ;
ALTER TABLE `gb_b_shop_employee` ADD `id` INT( 11 ) NOT NULL AUTO_INCREMENT COMMENT '主码id' FIRST ,
ADD PRIMARY KEY ( `id` ) ;

--20180425 mjk
ALTER TABLE `gb_b_recovery_product`
DROP COLUMN `f_rproduct_code`;
ALTER TABLE `gb_b_recovery_product`
MODIFY COLUMN `product_id`  bigint(20) NULL DEFAULT NULL COMMENT '如果是截金销售，关联的货品id；如果是结算来料，关联金料id' AFTER `type`;

ALTER TABLE `gb_b_company_account`
ADD COLUMN `supplier_code`  varchar(20) NULL COMMENT '供应商编码' AFTER `supplier_id`;
ALTER TABLE `gb_b_supplier`
DROP COLUMN `supplier_code`;

--2018-04-25
ALTER TABLE `gb_m_user_source`
ADD UNIQUE INDEX `user_id` (`user_id`) USING BTREE ;
insert into gb_m_user_source(`user_id`,`add_source`) (SELECT u.id,CONCAT(u.company_id,',0') from gb_m_users u where u.id not in (SELECT user_id FROM gb_m_user_source));
ALTER TABLE `gb_m_user_source`
CHANGE COLUMN `belog_rule_name` `belong_rule_name`  varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '归属的路由地址' AFTER `belong`;

--20180426 mjk
ALTER TABLE `gb_b_saccount_record`
MODIFY COLUMN `status`  tinyint(3) NOT NULL DEFAULT 0 COMMENT '财务是否确认 -1销售待审核 0未确认 1已确认 2确认不通过 3撤销' AFTER `check_memo`;
ALTER TABLE `gb_b_role`
ADD COLUMN `type`  tinyint(3) NOT NULL DEFAULT 0 COMMENT '是否门店角色 0否 1是' AFTER `listorder`;
UPDATE gb_b_role set type=1 where shop_id>0;

update gb_b_role a,gb_b_company b set a.company_id=b.company_id,a.shop_id=-1 where  a.name=b.company_name;
update gb_b_role a,gb_b_company b set a.company_id=b.company_id,a.shop_id=-1,type=1 where  a.name=CONCAT(b.company_name,'店长');
--2018-05-02
ALTER TABLE `gb_b_caccount_record`
ADD COLUMN `extra_price`  decimal(12,2) NOT NULL DEFAULT 0.00 AFTER `price`;
ALTER TABLE `gb_b_caccount_record`
MODIFY COLUMN `extra_price`  decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '抹零金额，额外费用' AFTER `price`;


--20180503 lzy
ALTER TABLE `gb_b_recovery_product` CHANGE `rproduct_code` `rproduct_code` VARCHAR( 50 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '货品编码'

--2018-05-04
ALTER TABLE `gb_b_procure_settle`
ADD COLUMN `payment_pic`  text NULL AFTER `after_price`,
ADD COLUMN `payment_time`  varchar(12) NULL COMMENT '付款时间' AFTER `payment_pic`;

--20180507
ALTER TABLE `gb_b_allot`
ADD COLUMN `allot_num`  int(11) NULL DEFAULT 0 COMMENT '调拨数量' AFTER `to_id`;
UPDATE gb_b_allot a set allot_num=(SELECT COUNT(*) from gb_b_allot_detail  where a.id=gb_b_allot_detail.allot_id and gb_b_allot_detail.deleted=0)

-- 20180508 lzy
ALTER TABLE `gb_b_product_gold` ADD PRIMARY KEY ( `id` ) ;
ALTER TABLE `gb_m_users` CHANGE `last_login_time` `last_login_time` VARCHAR( 12 ) NOT NULL COMMENT '最后登录时间(后台)',
CHANGE `last_login_time_pc` `last_login_time_pc` VARCHAR( 12 ) NULL DEFAULT NULL COMMENT '最后登录时间(pc)',
CHANGE `last_login_time_pad` `last_login_time_pad` VARCHAR( 12 ) NULL DEFAULT NULL COMMENT '最后登录时间(pad)';

--20180509
ALTER TABLE `gb_b_sell`
ADD COLUMN `client_id`  bigint(20) NULL COMMENT '客户id' AFTER `buyer_id`;
ALTER TABLE `gb_b_recovery`
ADD COLUMN `client_id`  bigint(20) NULL COMMENT '客户id' AFTER `f_uid`;
UPDATE gb_b_sell a set client_id=(SELECT id from gb_b_client  where gb_b_client.user_id=a.buyer_id and gb_b_client.shop_id=a.shop_id and gb_b_client.company_id=a.company_id)
where a.client_id is null
UPDATE gb_b_recovery a set client_id=(SELECT id from gb_b_client  where gb_b_client.user_id=a.f_uid and gb_b_client.shop_id=a.shop_id and gb_b_client.company_id=a.company_id)
where a.client_id is null
ALTER TABLE `gb_b_saccount_record`
ADD COLUMN `currency_rate`  varchar(12) NULL AFTER `currency_id`,
ADD COLUMN `main_currency_rate`  varchar(12) NULL AFTER `currency_rate`,
ADD COLUMN `main_currency_id`  int(11) NULL AFTER `main_currency_rate`;
ALTER TABLE `gb_b_currency`
ADD COLUMN `is_user`  tinyint(3) NOT NULL DEFAULT 0 COMMENT '是否被使用 0 否 1 是  使用的不能删除和编辑名称' AFTER `status`;


-- 2018.5.9 lzy
ALTER TABLE `gb_b_goods_common` DROP `is_standard`;

--2018-05-18
ALTER TABLE `gb_b_supplier`
ADD COLUMN `supplier_phone`  varchar(20) NULL AFTER `fax`,
ADD COLUMN `supplier_email`  varchar(50) NULL AFTER `supplier_phone`,
ADD COLUMN `supplier_licence`  varchar(50) NULL AFTER `supplier_email`,
ADD COLUMN `attachment`  text NULL AFTER `supplier_licence`;
ALTER TABLE `gb_b_supplier`
MODIFY COLUMN `supplier_phone`  varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '公司电话' AFTER `fax`,
MODIFY COLUMN `supplier_email`  varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '联系人邮箱' AFTER `supplier_phone`,
MODIFY COLUMN `supplier_licence`  varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '营业执照' AFTER `supplier_email`,
MODIFY COLUMN `attachment`  text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '附件' AFTER `supplier_licence`;

--2018-05-21 alam
ALTER TABLE gb_b_procure_storage ADD COLUMN agc_id int(11) NOT NULL DEFAULT 0 COMMENT '关联的A端商品分类id' AFTER `type`;

-- 2018.5.21 lzy
ALTER TABLE `gb_m_users` CHANGE `operate_ip` `operate_ip` VARCHAR( 16 ) NULL DEFAULT NULL COMMENT '操作人ip';
--20180521
ALTER TABLE `gb_b_sell`
MODIFY COLUMN `order_id`  varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '销售单号' AFTER `id`;
ALTER TABLE `gb_b_wsell`
MODIFY COLUMN `order_id`  varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '销售单号' AFTER `procure_settle_id`;

ALTER TABLE `gb_m_flowrecord`
MODIFY COLUMN `order_id`  varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '订单号' AFTER `t_uid`;

ALTER TABLE `gb_m_gold_flow`
MODIFY COLUMN `order_id`  varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '订单号' AFTER `t_uid`;

ALTER TABLE `gb_m_bean_flow`
MODIFY COLUMN `flow_id`  varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '流水号' AFTER `bl_id`;

ALTER TABLE `gb_c_order`
MODIFY COLUMN `order_id`  varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '订单号' AFTER `company_id`;
ALTER TABLE `gb_a_withdraw`
MODIFY COLUMN `record_id`  varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '提现单号' AFTER `id`;

ALTER TABLE `gb_a_gold_withdraw`
MODIFY COLUMN `order_id`  varchar(50) CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT NULL COMMENT '订单号' AFTER `id`;

ALTER TABLE `gb_a_gold_order`
MODIFY COLUMN `order_id`  varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '订单号' AFTER `id`;

ALTER TABLE `gb_a_gold_recharge`
MODIFY COLUMN `order_id`  varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '订单号' AFTER `id`;

ALTER TABLE `gb_a_bean_recharge`
MODIFY COLUMN `order_id`  varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '订单号' AFTER `id`;

--20180524
ALTER TABLE `gb_b_client`
ADD COLUMN `sex`  tinyint(3) NOT NULL DEFAULT 0 COMMENT '性别 1 男 2 女 0 保密' AFTER `creator_id`;
UPDATE gb_b_client c set c.sex=(SELECT sex from gb_m_users u where u.id=c.user_id);

ALTER TABLE `gb_b_goods_pic`
ENGINE=InnoDB;
ALTER TABLE `gb_b_goods`
ENGINE=InnoDB;
ALTER TABLE `gb_b_goods_common`
ENGINE=InnoDB;

-- 2018-05-24 lzy
ALTER TABLE `gb_b_product_gold` ADD `sell_fee` DECIMAL(10,2) NOT NULL DEFAULT '0.00' COMMENT '既定的销售工费，可以为克工费也可以为件工费，于商品规格中的sell_feemode字段取决' AFTER `buy_m_fee`; 
ALTER TABLE `gb_b_goods` DROP `sell_feemode`;
ALTER TABLE `gb_b_goldgoods_detail` ADD `sell_feemode` TINYINT(3) NOT NULL DEFAULT '1' COMMENT '销售工费方式 0计件 1计重' AFTER `buy_fee` ;

--2018-05-25 lzy
ALTER TABLE `gb_b_employee` CHANGE `status` `status` TINYINT(1) NOT NULL DEFAULT '1' COMMENT '状态 0离职 1在职 2试用';
--2018-5-26  lzy
CREATE TABLE IF NOT EXISTS `gb_b_bill_op_record` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT '主码id',
  `company_id` int(11) NOT NULL COMMENT '商户id',
  `type` smallint(6) NOT NULL COMMENT '表单类型',
  `sn_id` bigint(20) NOT NULL COMMENT '表单id',
  `operate_type` tinyint(3) NOT NULL COMMENT '操作类型',
  `operate_id` bigint(20) NOT NULL COMMENT '操作人id',
  `operate_time` varchar(12) NOT NULL COMMENT '操作时间',
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  KEY `type` (`type`),
  KEY `sn_id` (`sn_id`),
  KEY `operate_type` (`operate_type`),
  KEY `operate_id` (`operate_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='表单操作记录表' AUTO_INCREMENT=1 ;
--20180526
ALTER TABLE `gb_b_sell_detail`
ADD COLUMN `detail_sell_feemode`  tinyint(3) NOT NULL DEFAULT 1 COMMENT '销售工费方式 0计件 1计重' AFTER `discount_price`;

--2018.5.28 alam
ALTER TABLE gb_b_procurement ADD COLUMN `gold_weight` DECIMAL(12, 2) NOT NULL DEFAULT '0.00' COMMENT '折纯金重' AFTER `weight`;
ALTER TABLE gb_b_procure_storage ADD COLUMN `gold_weight` DECIMAL(12, 2) NOT NULL DEFAULT '0.00' COMMENT '折纯克重' AFTER `weight`;

-- 2018.5.28 lzy
ALTER TABLE `gb_b_procure_settle` CHANGE `status` `status` TINYINT(3) NOT NULL DEFAULT '0' COMMENT '状态 0待审核 1审核用过 2审核不通过 3已撤销4已上传凭证';
ALTER TABLE gb_b_procure_settle ADD COLUMN `payop_time` VARCHAR(12) DEFAULT NULL COMMENT '上传凭证时间' AFTER `payment_time`;
ALTER TABLE `gb_b_procure_settle` ADD `upimg_id` BIGINT(20) NULL DEFAULT '0' COMMENT '上传图片的用户id' AFTER `payment_pic`;

--2018.5.29 alam
ALTER TABLE gb_m_users ADD COLUMN `mobile_area` TINYINT(3) NOT NULL DEFAULT '1' COMMENT '手机号码区号 1-中国大陆86 2-中国香港852 3-中国澳门853' AFTER `mobile`;
ALTER TABLE gb_a_employee ADD COLUMN `mobile_area` TINYINT(3) NOT NULL DEFAULT '1' COMMENT '手机号码区号 1-中国大陆86 2-中国香港852 3-中国澳门853' AFTER `employee_mobile`;
ALTER TABLE gb_b_employee ADD COLUMN `mobile_area` TINYINT(3) NOT NULL DEFAULT '1' COMMENT '手机号码区号 1-中国大陆86 2-中国香港852 3-中国澳门853' AFTER `employee_mobile`;

--20180531 chenzy
ALTER TABLE `gb_b_allot`
MODIFY COLUMN `status`  tinyint(3) NOT NULL COMMENT '状态-2已驳回 -1 保存 0未审核 1已审核（出库确认中） 2审核不通过 3已撤销 4出库不通过 5入库确认中 6入库不通过 7已完成' AFTER `receipt_memo`;

ALTER TABLE `gb_b_procurement`
MODIFY COLUMN `status`  tinyint(3) NOT NULL DEFAULT 0 COMMENT '状态 -2已驳回 -1保存 0待审核 1审核通过 2审核不通过 3已撤销 4已结算' AFTER `check_memo`;

ALTER TABLE `gb_b_procure_storage`
MODIFY COLUMN `status`  tinyint(3) NOT NULL DEFAULT 0 COMMENT '状态 -2已驳回 0待审核 1审核通过 2审核不通过 3已撤销4已结算' AFTER `storage_status`;

ALTER TABLE `gb_b_recovery`
MODIFY COLUMN `status`  tinyint(3) NOT NULL DEFAULT 0 COMMENT '状态 -2已驳回-1保存 0待审核 1审核用过 2审核不通过3已撤销' AFTER `check_memo`;

ALTER TABLE `gb_b_procure_settle`
MODIFY COLUMN `status`  tinyint(3) NOT NULL DEFAULT 0 COMMENT '状态 -2已驳回 -1保存 0待审核 1审核用过 2审核不通过 3已撤销4已上传凭证' AFTER `type`;

--20180601
ALTER TABLE `gb_b_payment`
ADD COLUMN `shop_id`  int(11) NOT NULL DEFAULT 0 COMMENT '门店 0 总部  其他是门店id' AFTER `company_id`;

--20180604 lzy
ALTER TABLE `gb_b_product` CHANGE `status` `status` TINYINT(2) NOT NULL COMMENT '-1 采购已退货0采购退货中1采购入库（待确认状态）， 2正常在库，3调拨中， 4销售中,5已销售待出库，6销售出库 7损坏出库中 8损坏出库 9退货中';
ALTER TABLE `gb_b_procure_return` ADD `batch` VARCHAR(50) NOT NULL COMMENT '退款批次' AFTER `shop_id`;

--20180604
ALTER TABLE `gb_b_shop`
ADD COLUMN `show_common_payment`  tinyint(3) NOT NULL DEFAULT 1 COMMENT '是否显示总部的收款方式' AFTER `currency_id`;
ALTER TABLE `gb_b_employee`
ADD COLUMN `employee_login_time`  varchar(12) NULL COMMENT '员工最近登录时间' AFTER `creator_id`;
UPDATE gb_b_employee a set a.employee_login_time=(SELECT last_login_time from gb_m_users u WHERE a.user_id=u.id) where a.deleted=0;

--20180606
ALTER TABLE `gb_b_client`
ADD COLUMN `client_idno`  varchar(50) NULL COMMENT '身份证号' AFTER `client_name`;
ALTER TABLE `gb_b_sell`
ADD COLUMN `sell_type`  tinyint(3) NOT NULL DEFAULT 1 COMMENT '销售类型 1零售 2 以旧换新' AFTER `extra_price`;
ALTER TABLE `gb_b_recovery_product`
MODIFY COLUMN `type`  tinyint(3) NULL DEFAULT 0 COMMENT '类型 0回购 1销售截金 2金料 3结算来料 4以旧换新' AFTER `cost_price`;
ALTER TABLE `gb_b_recovery`
MODIFY COLUMN `type`  tinyint(3) NOT NULL DEFAULT 0 COMMENT '类型 0旧金回购1 截金回购 2已旧换新' AFTER `status`;

CREATE TABLE `gb_b_sell_recovery_product` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT '主码id',
  `sell_id` bigint(20) NOT NULL COMMENT '销售单id',
  `company_id` int(11) NOT NULL COMMENT '商户id',
  `product_code` bigint(20) DEFAULT NULL  COMMENT '货品编号',
`recovery_name` varchar(255) DEFAULT NULL COMMENT '金料名称',
  `rproduct_code` varchar(20) DEFAULT NULL COMMENT '金料编号',
`gold_price` decimal(7,2) NOT NULL DEFAULT '0.00' COMMENT '当前金价',
  `recovery_gold_price` decimal(7,2) NOT NULL DEFAULT '0.00' COMMENT '回购金价',
`total_weight` decimal(15,3) NOT NULL DEFAULT '0.000' COMMENT '总重',
  `gold_weight` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT '金重',
  `purity` decimal(6,6) DEFAULT '0.000000' COMMENT '纯度',
  `service_fee` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT '服务克工费',
  `cost_price` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT '抵扣费用',
  `deleted` tinyint(3) NOT NULL DEFAULT '0' COMMENT '是否删除0否1是',
  PRIMARY KEY (`id`),
  KEY `sell_id` (`sell_id`),
  KEY `company_id` (`company_id`),
  KEY `deleted` (`deleted`)
) ENGINE=InnoDB AUTO_INCREMENT=60 DEFAULT CHARSET=utf8mb4 COMMENT='销售回购详情表';

ALTER TABLE `gb_b_sell`
ADD COLUMN `client_idno`  varchar(50) NULL COMMENT '客户身份证号' AFTER `buyer_id`;

-- 20180608 alam
ALTER TABLE gb_b_company_account_flow ADD COLUMN sn_type tinyint(4) NULL DEFAULT 0 COMMENT '单据类型 1-采购 1-结算 2-采购退货' AFTER type;
ALTER TABLE gb_b_procure_return ADD COLUMN supplier_id int(11) NOT NULL DEFAULT 0 COMMENT '供应商id' AFTER wh_id;

-- 20180610 alam 采购退货上传凭证
ALTER TABLE gb_b_procure_return MODIFY COLUMN `status` tinyint(3) NOT NULL DEFAULT '-1' COMMENT '状态 -2已驳回 -1 待提交 0待审核 1审核通过 2审核不通过 3已撤销 4已上传凭证' AFTER `check_memo`;
ALTER TABLE gb_b_procure_return ADD COLUMN `payment_pic` text COMMENT '凭证图片地址' AFTER `status`;
ALTER TABLE gb_b_procure_return ADD COLUMN `upimg_id` bigint(20) DEFAULT '0' COMMENT '上传凭证的用户id' AFTER `payment_pic`;
ALTER TABLE gb_b_procure_return ADD COLUMN `payment_time` varchar(12) DEFAULT NULL COMMENT '付款时间' AFTER `upimg_id`;
ALTER TABLE gb_b_procure_return ADD COLUMN `payop_time` varchar(12) DEFAULT NULL COMMENT '上传凭证时间' AFTER `payment_time`;

-- 20180611 alam 供应商结欠流水表类型字段完善
ALTER TABLE gb_b_company_account_flow MODIFY COLUMN `type` tinyint(4) DEFAULT '0' COMMENT '类型 1-采购 2-来往钱 3-来往料 4-买卖料 5-采购退货' AFTER `price`;

-- 20180611 alam 之前小麦的sql，没运行到，现在买卖料表还残留flow_id、type以及t_cid字段，并且没有数据
ALTER TABLE `gb_b_material_order`
DROP COLUMN `flow_id`,
DROP COLUMN `type`,
CHANGE COLUMN `t_cid` `account_id`  bigint(20) NULL DEFAULT 0 COMMENT '商户结欠账户id' AFTER `company_id`;

-- 20180611 alam 将结欠与来往料、买卖料、来往钱等三表关联改为一对多形式，为将来其它业务使用该三表做铺垫
-- 买卖料
ALTER TABLE gb_b_material_order ADD COLUMN `sn_id` int(11) NULL COMMENT '单据id' AFTER `account_id`;
ALTER TABLE gb_b_material_order ADD COLUMN `type` tinyint(4) NULL DEFAULT '1' COMMENT '单据类型 1-结算单' AFTER `sn_id`;
-- 来往料
ALTER TABLE gb_b_material_record ADD COLUMN `sn_id` int(11) NULL COMMENT '单据id' AFTER `account_id`;
ALTER TABLE gb_b_material_record ADD COLUMN `type` tinyint(4) NULL DEFAULT '1' COMMENT '单据类型 1-结算单' AFTER `sn_id`;
-- 来往钱
ALTER TABLE gb_b_caccount_record ADD COLUMN `sn_id` int(11) NULL COMMENT '单据id' AFTER `account_id`;
ALTER TABLE gb_b_caccount_record ADD COLUMN `type` tinyint(4) NULL DEFAULT '1' COMMENT '单据类型 1-结算单' AFTER `sn_id`;
-- 删除结算表中的四个字段
ALTER TABLE `gb_b_procure_settle` 
DROP COLUMN `ca_record_id`,
DROP COLUMN `mrecord_id_2`,
DROP COLUMN `mrecord_id`,
DROP COLUMN `morder_id`;

-- 20180612 alam 三表添加索引
ALTER TABLE gb_b_material_record ADD INDEX sn_id (sn_id, type) ;
ALTER TABLE gb_b_caccount_record ADD INDEX sn_id (sn_id, type) ;
ALTER TABLE gb_b_material_order ADD INDEX sn_id (sn_id, type) ;

-- 20180612 chenzy
CREATE TABLE `gb_b_out_recovery_product` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT '主码id',
  `out_id` bigint(20) NOT NULL COMMENT '出库单id',
  `company_id` int(11) NOT NULL COMMENT '商户id',
  `product_id` bigint(20) NOT NULL COMMENT '货品id',
  `rproduct_code` varchar(20) DEFAULT NULL COMMENT '金料编号',
  `recovery_price` decimal(7,2) NOT NULL DEFAULT '0.00' COMMENT '旧金金价',
  `gold_price` decimal(7,2) NOT NULL DEFAULT '0.00' COMMENT '当前金价',
  `total_weight` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT '总重',
  `gold_weight` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT '金重',
  `purity` decimal(6,6) DEFAULT '0.000000' COMMENT '纯度',
  `service_fee` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT '服务克工费',
  `cost_price` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT '抵扣费用',
  `deleted` tinyint(3) NOT NULL DEFAULT '0' COMMENT '是否删除0否1是',
  PRIMARY KEY (`id`),
  KEY `out_id` (`out_id`),
  KEY `company_id` (`company_id`),
  KEY `deleted` (`deleted`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=67 DEFAULT CHARSET=utf8mb4 COMMENT='出库转料详情表';
ALTER TABLE `gb_b_recovery_product`
DROP COLUMN `storage_id`;

ALTER TABLE `gb_b_outbound_order`
MODIFY COLUMN `type`  tinyint(3) NOT NULL DEFAULT 1 COMMENT '出库类型 1 销售 2异常 4赠送 5转料' AFTER `memo`;
ALTER TABLE `gb_b_outbound_order`
ADD COLUMN `recovery_wh_id`  int(11) NULL DEFAULT NULL COMMENT '金料仓库id' AFTER `warehouse_id`;
ALTER TABLE `gb_b_outbound_order`
ADD COLUMN `client_id`  int(11) NULL DEFAULT NULL COMMENT '客户id' AFTER `user_id`;

-- 20180613

ALTER TABLE `gb_b_recovery_product`
MODIFY COLUMN `order_id`  bigint(20) NOT NULL COMMENT '采购单id 回购单id 结算单id 销售单id 出库单id 合并单id' AFTER `id`,
MODIFY COLUMN `type`  tinyint(3) NULL DEFAULT 0 COMMENT '类型 0回购 1销售截金 2采购金料 3结算来料 4以旧换新 5出库转料' AFTER `cost_price`;
ALTER TABLE `gb_b_recovery_product`
ADD COLUMN `create_time`  varchar(12) NULL COMMENT '创建时间' AFTER `product_id`;
ALTER TABLE `gb_b_recovery_product`
MODIFY COLUMN `status`  tinyint(3) NOT NULL DEFAULT 0 COMMENT '状态 0未入库（用于审核不通过等消亡金料，便于判断编号是否重复） 1入库中 2正常在库 3调拨中 4结算中 5已结算 6合并中 7已合并' AFTER `create_time`;

ALTER TABLE `gb_b_recovery_product`
DROP INDEX `rproduct_code` ,
ADD INDEX `rproduct_code` (`rproduct_code`) USING BTREE ;
ALTER TABLE `gb_b_recovery`
ADD COLUMN `wh_id`  int(11) NULL COMMENT '仓库id' AFTER `shop_id`;
ALTER TABLE `gb_b_sell`
ADD COLUMN `recovery_wh_id`  int(11) NULL COMMENT '金料仓库id' AFTER `shop_id`;
ALTER TABLE `gb_b_recovery_product`
ADD COLUMN `wh_id`  int(11) NULL COMMENT '金料仓库id' AFTER `company_id`;


-- 20180613 alam 采购退货表增加抹零金额字段
ALTER TABLE gb_b_procure_return ADD COLUMN extra_price decimal(6,2) NULL DEFAULT '0.00' COMMENT '抹零金额' AFTER price;
ALTER TABLE gb_b_product MODIFY `status` tinyint(2) NOT NULL COMMENT '-1采购已退货 0采购退货中 1采购入库(待确认状态) 2正常在库 3调拨中 4销售中 6销售出库 7损坏出库中 8损坏出库 9采购退货中 10采购已退货 11赠送出库 12转料出库';

-- 20180614 alam 两个站点数据结构比对结果 自行选择是否运行
-- ALTER TABLE `gb_b_company_account_flow` CHANGE `type` `type` tinyint(4) NULL DEFAULT 0 COMMENT '类型 1-采购 2-来往钱 3-来往料 4-买卖料 5-采购退货' after `price` ;
-- ALTER TABLE `gb_b_procure_return` ADD COLUMN `batch` varchar(50) COLLATE utf8mb4_general_ci NOT NULL COMMENT '退款批次' after `shop_id`;
-- ALTER TABLE `gb_b_procure_settle` CHANGE `status` `status` tinyint(3) NOT NULL DEFAULT 0 COMMENT '状态 -2已驳回 -1保存 0待审核 1审核通过 2审核不通过 3已撤销 4已上传凭证' after `type` ;
-- ALTER TABLE `gb_b_procure_storage` CHANGE `status` `status` tinyint(3) NOT NULL DEFAULT 0 COMMENT '状态 -2已驳回 -1保存 0待审核 1审核通过 2审核不通过 3已撤销 4已结算' after `storage_status`;
-- ALTER TABLE `gb_b_procurement` CHANGE `status` `status` tinyint(3) NOT NULL DEFAULT 0 COMMENT '状态 -2已驳回 -1保存 0待审核 1审核通过 2审核不通过 3已撤销 4已结算' after `check_memo` ;
-- ALTER TABLE `gb_b_product` ADD COLUMN `product_age` int(10) NULL DEFAULT 0 COMMENT '库龄' after `product_code` , CHANGE `status` `status` tinyint(2)   NOT NULL COMMENT '-1 采购已退货0采购退货中1采购入库（待确认状态）， 2正常在库，3调拨中， 4销售中,5已销售待出库，6销售出库 7损坏出库中 8损坏出库 9退货中 10赠送出库 11转料出库' after `unpass` ;
-- ALTER TABLE `gb_b_recovery` CHANGE `status` `status` tinyint(3)   NOT NULL DEFAULT 0 COMMENT '状态 -2已驳回 -1保存 0待审核 1审核通过 2审核不通过 3已撤销' after `check_memo` ;
-- ALTER TABLE `gb_b_sell` CHANGE `status` `status` tinyint(3) NOT NULL DEFAULT 0 COMMENT '状态 -1待提交 0未付款 1已付款 2已撤销 3审核不通过' after `sell_type`;
-- ALTER TABLE `gb_m_users` CHANGE `operate_ip` `operate_ip` varchar(16) NULL COMMENT '操作人ip' after `operate_user_id` ;

-- 20180620 alam 销售退货
CREATE TABLE `gb_b_sell_redetail` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主码id',
  `sr_id` int(11) NOT NULL DEFAULT '0' COMMENT '退货单id',
  `sd_id` int(11) NOT NULL DEFAULT '0' COMMENT '销售详情id',
  `product_id` int(11) NOT NULL DEFAULT '0' COMMENT '货品id',
  `return_price` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '退货价',
  `update_time` varchar(12) NOT NULL DEFAULT '0' COMMENT '修改时间',
  `deleted` tinyint(3) NOT NULL DEFAULT '0' COMMENT '是否删除 0否 1是',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='销售退货详情表';

CREATE TABLE `gb_b_sell_return` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主码id',
  `order_id` varchar(50) NOT NULL COMMENT '退货单号',
  `company_id` int(11) NOT NULL DEFAULT '0' COMMENT '商户id',
  `shop_id` int(11) NOT NULL COMMENT '门店id',
  `return_time` varchar(12) NOT NULL COMMENT '退货时间',
  `buyer_id` bigint(20) NOT NULL COMMENT '退货人id',
  `client_idno` varchar(50) DEFAULT NULL COMMENT '客户身份证号',
  `client_id` bigint(20) DEFAULT NULL COMMENT '客户id',
  `sell_price` decimal(12,2) NOT NULL COMMENT '销售价格',
  `return_price` decimal(12,2) NOT NULL COMMENT '退货价格',
  `count` int(11) DEFAULT NULL COMMENT '销售数量',
  `extra_price` double(6,2) NOT NULL DEFAULT '0.00' COMMENT '抹零金额',
  `creator_id` bigint(20) NOT NULL COMMENT '开单人id',
  `create_time` varchar(12) NOT NULL COMMENT '制单时间',
  `memo` text COMMENT '备注',
  `check_id` bigint(20) DEFAULT NULL COMMENT '审核人id',
  `check_time` varchar(12) DEFAULT NULL COMMENT '审核时间',
  `check_memo` text COMMENT '审核备注',
  `status` tinyint(3) NOT NULL DEFAULT '0' COMMENT '状态 -2已驳回 -1 待提交 0待审核 1审核通过 2审核不通过 3已撤销',
  `deleted` tinyint(3) NOT NULL DEFAULT '0' COMMENT '是否删除 0否 1是',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='销售退货表';

ALTER TABLE gb_b_saccount_record CHANGE `status` `status` tinyint(3) NOT NULL DEFAULT '0' COMMENT '财务是否确认 -1待审核 0未确认 1已确认 2确认不通过 3撤销';
ALTER TABLE gb_b_saccount_record CHANGE `type` `type` tinyint(3) NOT NULL COMMENT '类型 1销售收款 2批发收款 3销售退款';

-- 20180620 alam 销售详情状态回溯
update gb_b_sell_detail set status = 1 where sell_id in (select id from gb_b_sell where status = 1);

-- 20160619
CREATE TABLE `gb_b_allot_rproduct_detail` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `allot_id` int(11) NOT NULL COMMENT '调拨单号id',
  `p_id` int(11) NOT NULL COMMENT '金料id',
  `deleted` tinyint(3) NOT NULL DEFAULT '0' COMMENT '是否删除 0否 1是',
  `company_id` int(11) NOT NULL DEFAULT '0' COMMENT '商户id',
  PRIMARY KEY (`id`),
  KEY `allot_id` (`allot_id`) USING BTREE,
  KEY `p_id` (`p_id`) USING BTREE,
  KEY `company_id` (`company_id`),
  KEY `deleted` (`deleted`),
  KEY `id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='金料调拨单明细';

CREATE TABLE `gb_b_allot_rproduct` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL DEFAULT '0' COMMENT '商户id',
  `batch` varchar(50) NOT NULL COMMENT '调拨批次',
  `from_sid` int(11) NOT NULL COMMENT '调出门店id',
  `to_sid` int(11) NOT NULL COMMENT '调入门店id',
  `from_id` int(11) NOT NULL COMMENT '调出仓库id',
  `to_id` int(11) NOT NULL COMMENT '调入仓库id',
  `allot_num` int(11) DEFAULT '0' COMMENT '调拨数量',
  `type` tinyint(3) NOT NULL DEFAULT '0' COMMENT '调拨类型 0商户仓库调拨 1商户门店调配 2门店之间调配',
  `create_time` varchar(12) NOT NULL COMMENT '制单时间',
  `creator_id` bigint(20) NOT NULL COMMENT '制单人id',
  `allot_time` varchar(12) NOT NULL COMMENT '调拨时间',
  `memo` text NOT NULL COMMENT '备注',
  `check_id` bigint(20) DEFAULT NULL COMMENT '审核人id',
  `check_time` varchar(12) DEFAULT NULL COMMENT '审核时间',
  `check_memo` text COMMENT '审核备注',
  `outbound_id` bigint(20) DEFAULT NULL COMMENT '出库人id',
  `outbound_time` bigint(20) DEFAULT NULL COMMENT '出库时间',
  `outbound_memo` text COMMENT '出库备注',
  `receipt_id` bigint(20) DEFAULT NULL,
  `receipt_time` varchar(12) DEFAULT NULL COMMENT '入库时间',
  `receipt_memo` text NOT NULL COMMENT '入库备注',
  `status` tinyint(3) NOT NULL COMMENT '状态-2已驳回 -1 保存 0未审核 1审核通过（出库确认中） 2审核不通过 3已撤销 4出库不通过 5入库确认中 6入库不通过 7已完成',
  `deleted` tinyint(3) NOT NULL COMMENT '是否删除 0否 1是',
  PRIMARY KEY (`id`),
  KEY `creator_id` (`creator_id`) USING BTREE,
  KEY `outbound_id` (`outbound_id`) USING BTREE,
  KEY `id` (`id`) USING BTREE,
  KEY `company_id` (`company_id`),
  KEY `from_id` (`from_id`),
  KEY `to_id` (`to_id`),
  KEY `check_id` (`check_id`),
  KEY `receipt_id` (`receipt_id`),
  KEY `batch` (`batch`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='金料调拨单';

DROP TABLE `gb_b_out_recovery_product`;
DROP TABLE `gb_b_sell_recovery_product`;

--  合并单
CREATE TABLE `gb_b_merge_order` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '合并单id',
  `batch` varchar(50) NOT NULL COMMENT '合并单批次',
  `company_id` int(11) NOT NULL DEFAULT '0' COMMENT '商户id',
  `shop_id` int(11) NOT NULL DEFAULT '0' COMMENT '门店id为0时代表商户总部',
  `warehouse_id` int(11) NOT NULL DEFAULT '0' COMMENT '仓库id',
  `merge_time` varchar(12) DEFAULT NULL COMMENT '合并时间',
  `num` int(11) NOT NULL DEFAULT '0' COMMENT '合并数量',
  `creator_id` bigint(20) NOT NULL DEFAULT '0' COMMENT '制单人id',
  `create_time` varchar(12) DEFAULT NULL COMMENT '创建时间',
  `memo` varchar(255) DEFAULT NULL COMMENT '制单员备注',
  `check_id` bigint(20) NOT NULL COMMENT '审核人id',
  `check_time` varchar(12) DEFAULT NULL COMMENT '审核时间',
  `check_memo` varchar(255) DEFAULT NULL COMMENT '审核备注',
  `status` tinyint(3) NOT NULL DEFAULT '0' COMMENT '状态 -2驳回 -1保存 0待审核 1审核用过 2审核不通过3已撤销',
  `deleted` tinyint(3) NOT NULL DEFAULT '0' COMMENT '是否删除0否1是',
  PRIMARY KEY (`id`),
  KEY `batch` (`batch`),
  KEY `company_id` (`company_id`),
  KEY `shop_id` (`shop_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='金料合并单据表';

-- 合并单明细
CREATE TABLE `gb_b_merge_detail` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `merge_id` int(11) NOT NULL COMMENT '合并单号id',
  `p_id` int(11) NOT NULL COMMENT '金料id',
  `deleted` tinyint(3) NOT NULL DEFAULT '0' COMMENT '是否删除 0否 1是',
  `company_id` int(11) NOT NULL DEFAULT '0' COMMENT '商户id',
  PRIMARY KEY (`id`),
  KEY `merge_id` (`merge_id`) USING BTREE,
  KEY `p_id` (`p_id`) USING BTREE,
  KEY `company_id` (`company_id`),
  KEY `deleted` (`deleted`),
  KEY `id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='金料合并单明细';

ALTER TABLE `gb_b_recovery_product`
MODIFY COLUMN `type`  tinyint(3) NULL DEFAULT 0 COMMENT '类型 0回购 1销售截金 2采购金料 3结算来料 4以旧换新 5出库转料 6合并金料' AFTER `cost_price`;

-- 20180621 alam 修改供应商结欠信息表sn_id字段注释
ALTER TABLE `gb_b_company_account_flow` MODIFY COLUMN `sn_id` int(11) DEFAULT NULL COMMENT '关联其他表的id,表类型由type决定';

-- 20180621 alam 货品体系增加备注字段
ALTER TABLE gb_b_product ADD COLUMN `memo` TEXT NULL DEFAULT '' COMMENT '备注';
ALTER TABLE gb_b_recovery_product ADD COLUMN `memo` TEXT NULL DEFAULT '' COMMENT '备注';

-- 20180622 alam 采购表增加工费字段
ALTER TABLE gb_b_procurement ADD COLUMN `fee` DECIMAL(12,2) NOT NULL DEFAULT '0.00' COMMENT '采购工费' AFTER gold_weight;
update gb_b_procurement set fee = price where pricemode = 1;
-- 另外还需要运行计重price回溯的程序

-- 20180628 alam 修订几个记录表的注释
ALTER TABLE gb_b_caccount_record MODIFY COLUMN `status` tinyint(4) DEFAULT '0' COMMENT '状态默认 0未审核 1已审核 2审核不通过 3已撤销';
ALTER TABLE gb_b_material_order MODIFY COLUMN `status` tinyint(4) DEFAULT '0' COMMENT '状态默认 0未审核 1已审核 2审核不通过 3已撤销';
ALTER TABLE gb_b_material_record MODIFY COLUMN `status` tinyint(4) DEFAULT '0' COMMENT '状态默认 0未审核 1已审核 2审核不通过 3已撤销';
ALTER TABLE gb_b_company_account_flow MODIFY COLUMN `sn_type` tinyint(4) DEFAULT '0' COMMENT '单据类型 1-采购 2-结算 3-采购退货';

-- 20180628 alam 添加货品挂起状态
ALTER TABLE gb_b_product MODIFY COLUMN `status` tinyint(2) NOT NULL COMMENT '1采购入库(待确认状态) 2正常在库 3调拨中 4销售中 6销售出库 7出库中 8丢弃出库 9采购退货中 10采购已退货 11赠送出库 12转料出库 13调拨挂起 14销售挂起' AFTER memo;
ALTER TABLE gb_b_product MODIFY COLUMN `deleted` tinyint(2) NOT NULL DEFAULT '0' COMMENT '是否删除 0 未删除 1已删除' AFTER status;

-- 20180703 alam 完善comment
ALTER TABLE gb_b_procurement MODIFY COLUMN `fee` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '采购工费 计重采购结算依据';
ALTER TABLE gb_b_procurement MODIFY COLUMN `price` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '采购金额 计件采购结算依据';
ALTER TABLE gb_b_expence_sub MODIFY COLUMN `ticket_type` tinyint(3) NOT NULL DEFAULT '1' COMMENT '单据类型 1-销售单 2-采购单 3-采购退货单 4-销售退货单';

-- 20180703  金料增加材质和颜色
ALTER TABLE `gb_b_recovery_product`
ADD COLUMN `color`  varchar(50) NULL COMMENT '颜色' AFTER `type`,
ADD COLUMN `material`  varchar(50) NULL COMMENT '材质' AFTER `color`;

-- 20180703 alam 修改旧金货品表克重数据字段类型
ALTER TABLE `gb_b_recovery_product` MODIFY COLUMN `total_weight` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT '总重';
ALTER TABLE `gb_b_recovery_product` MODIFY COLUMN `gold_weight` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT '总重';
-- 20180703 alam 外部货品编号
ALTER TABLE `gb_b_product` ADD COLUMN `sub_product_code` varchar(30) NULL COMMENT '外部货品编号' AFTER `product_code`;
-- 20180705 alam 来往钱 来往料 买卖料 标识
ALTER TABLE `gb_b_material_record` ADD COLUMN `sn_type` tinyint(4) DEFAULT '0' COMMENT '交易类型 0-无来往 1-来料 2-往料' AFTER `type`;
ALTER TABLE `gb_b_caccount_record` ADD COLUMN `sn_type` tinyint(4) DEFAULT '0' COMMENT '交易类型 0-无来往 1-来钱 2-往钱' AFTER `type`;
ALTER TABLE `gb_b_material_order` ADD COLUMN `sn_type` tinyint(4) DEFAULT '0' COMMENT '交易类型 0-无买卖 1-买料 2-卖料' AFTER `type`;
ALTER TABLE `gb_b_material_record` MODIFY COLUMN `weight` decimal(12,2) DEFAULT '0.00' COMMENT '克重';

update `gb_b_material_order` set sn_type = 1 where weight > 0;
update `gb_b_material_order` set sn_type = 2 where weight < 0;
update `gb_b_material_order` set sn_type = 0 where weight = 0;

update `gb_b_material_record` set sn_type = 1 where weight > 0;
update `gb_b_material_record` set sn_type = 2 where weight < 0;
update `gb_b_material_record` set sn_type = 0 where weight = 0;

update `gb_b_caccount_record` set sn_type = 1 where price < 0;
update `gb_b_caccount_record` set sn_type = 2 where price > 0;
update `gb_b_caccount_record` set sn_type = 0 where price = 0;
-- 20180705 alam 供应商结欠信息流水中新增创建时间
ALTER TABLE `gb_b_company_account_flow` ADD COLUMN `create_time` varchar(12) NOT NULL COMMENT '创建时间';

-- 20180709 chenzy
ALTER TABLE `gb_b_goods_common` ADD COLUMN `tag_name`  varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '标签名' AFTER `goods_code`;
ALTER TABLE `gb_b_sell` ADD COLUMN `sn_id`  varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '外部订单号' AFTER `order_id`;
ALTER TABLE `gb_b_recovery_product` ADD COLUMN `sub_rproduct_code`  varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '外部金料编号' AFTER `rproduct_code`;
ALTER TABLE `gb_b_recovery_product` ADD COLUMN `end_gold_price`  decimal(7,2) NULL DEFAULT NULL COMMENT '结束金价' AFTER `gold_price`;
ALTER TABLE `gb_b_goods_common`
ADD COLUMN `belong_type`  varchar(50) NULL COMMENT '所属套系' AFTER `class_id`;

