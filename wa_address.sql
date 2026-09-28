/*
 Navicat Premium Data Transfer

 Source Server         : velapp_veloqor2222
 Source Server Type    : MySQL
 Source Server Version : 50744
 Source Host           : 47.87.135.97:3306
 Source Schema         : online

 Target Server Type    : MySQL
 Target Server Version : 50744
 File Encoding         : 65001

 Date: 23/09/2026 14:18:33
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for wa_address
-- ----------------------------
DROP TABLE IF EXISTS `wa_address`;
CREATE TABLE `wa_address`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `createtime` bigint(16) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `user_id` int(10) NOT NULL DEFAULT 0 COMMENT '用户',
  `lat` decimal(10, 6) NOT NULL DEFAULT 0.000000 COMMENT '纬度',
  `long` decimal(10, 6) NOT NULL DEFAULT 0.000000 COMMENT '经度',
  `address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '地址详情',
  `type` tinyint(1) NOT NULL DEFAULT 0 COMMENT '地址类型0收藏1家2公司3喜欢',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '名称',
  `place_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'place_id',
  `is_default` tinyint(1) NOT NULL DEFAULT 0 COMMENT '默认选中',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 81 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '地址设置' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of wa_address
-- ----------------------------
INSERT INTO `wa_address` VALUES (78, 1789953244, 5, 22.306854, 114.171442, '香港九龙油麻地', 3, '油麻地', 'ChIJV4T2M5UABDQRgjIlh__s9Gk', 0);
INSERT INTO `wa_address` VALUES (79, 1789953252, 5, 22.542218, 114.106514, '中国广东省深圳市罗湖区深南东路5016号 邮政编码: 518010', 3, '京基100', 'ChIJJ8Jh37f1AzQR6e61ZF3hsQg', 0);

-- ----------------------------
-- Table structure for wa_admin_roles
-- ----------------------------
DROP TABLE IF EXISTS `wa_admin_roles`;
CREATE TABLE `wa_admin_roles`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
  `role_id` int(11) NOT NULL COMMENT '角色id',
  `admin_id` int(11) NOT NULL COMMENT '管理员id',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `role_admin_id`(`role_id`, `admin_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 20 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '管理员角色表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of wa_admin_roles
-- ----------------------------
INSERT INTO `wa_admin_roles` VALUES (1, 1, 1);

-- ----------------------------
-- Table structure for wa_admins
-- ----------------------------
DROP TABLE IF EXISTS `wa_admins`;
CREATE TABLE `wa_admins`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `username` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '用户名',
  `nickname` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '昵称',
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '密码',
  `avatar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '/app/admin/avatar.png' COMMENT '头像',
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '邮箱',
  `mobile` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '手机',
  `created_at` datetime NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime NULL DEFAULT NULL COMMENT '更新时间',
  `login_at` datetime NULL DEFAULT NULL COMMENT '登录时间',
  `status` tinyint(4) NULL DEFAULT NULL COMMENT '禁用',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `username`(`username`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '管理员表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of wa_admins
-- ----------------------------
INSERT INTO `wa_admins` VALUES (1, 'admin', '管理员', '$2y$10$broWfN11ZEAtBlPJYee03eDx0vJTIbaM0Ur6PdmgVYRHrdsrpXP2m', '/app/admin/avatar.png', '', '', '2025-06-27 14:59:41', '2026-09-16 14:47:01', '2026-09-16 14:47:01', NULL);

-- ----------------------------
-- Table structure for wa_agreement
-- ----------------------------
DROP TABLE IF EXISTS `wa_agreement`;
CREATE TABLE `wa_agreement`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '标题',
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '展示图',
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '分类',
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '内容',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '文章协议' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of wa_agreement
-- ----------------------------

-- ----------------------------
-- Table structure for wa_device_book
-- ----------------------------
DROP TABLE IF EXISTS `wa_device_book`;
CREATE TABLE `wa_device_book`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '类型代码',
  `desc` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '描述',
  `content` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '说明',
  `created_at` datetime NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 92195 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '通讯录' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of wa_device_book
-- ----------------------------

-- ----------------------------
-- Table structure for wa_device_signcode
-- ----------------------------
DROP TABLE IF EXISTS `wa_device_signcode`;
CREATE TABLE `wa_device_signcode`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '注册码',
  `created_at` datetime NOT NULL COMMENT '创建时间',
  `updated_at` datetime NOT NULL COMMENT '更新时间',
  `user_id` int(10) NOT NULL DEFAULT 0 COMMENT '设备id',
  `is_delete` tinyint(1) NOT NULL DEFAULT 0 COMMENT '删除',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '设备注册码' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of wa_device_signcode
-- ----------------------------

-- ----------------------------
-- Table structure for wa_device_signed
-- ----------------------------
DROP TABLE IF EXISTS `wa_device_signed`;
CREATE TABLE `wa_device_signed`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '设备编号',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '文件名',
  `file` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '文件地址',
  `size` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '文件大小',
  `time` bigint(16) NOT NULL DEFAULT 0 COMMENT '精确时间',
  `created_at` datetime NOT NULL COMMENT '创建时间',
  `updated_at` datetime NOT NULL COMMENT '更新时间',
  `device_id` int(10) NOT NULL DEFAULT 0 COMMENT '设备id',
  `is_delete` tinyint(1) NOT NULL DEFAULT 0 COMMENT '删除',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '文件管理' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of wa_device_signed
-- ----------------------------

-- ----------------------------
-- Table structure for wa_email
-- ----------------------------
DROP TABLE IF EXISTS `wa_email`;
CREATE TABLE `wa_email`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `event` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '事件',
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '邮箱',
  `code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '验证码',
  `times` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '验证次数',
  `ip` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT 'IP',
  `createtime` bigint(16) NULL DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 288 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '邮箱验证码表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of wa_email
-- ----------------------------
INSERT INTO `wa_email` VALUES (270, 'sign', '516651741@qq.com', '1729', 0, '123.52.10.161', 1789545782);
INSERT INTO `wa_email` VALUES (271, 'sign', '836883891@qq.com', '6285', 0, '123.52.10.161', 1789553867);
INSERT INTO `wa_email` VALUES (272, 'forget', '836883891@qq.com', '6320', 0, '123.52.11.55', 1789607143);
INSERT INTO `wa_email` VALUES (273, 'forget', '836883891@qq.com', '4589', 1, '123.52.11.55', 1789607235);
INSERT INTO `wa_email` VALUES (274, 'sign', '809213962@qq.com', '2705', 0, '57.129.115.108', 1789898920);
INSERT INTO `wa_email` VALUES (275, 'sign', '857057533@qq.com', '4685', 1, '107.172.103.107', 1789898979);
INSERT INTO `wa_email` VALUES (276, 'sign', '857057533@qq.com', '7024', 0, '107.172.103.107', 1789899118);
INSERT INTO `wa_email` VALUES (277, 'sign', 'ddd@pugn.vip', '9514', 0, '57.129.115.108', 1789899127);
INSERT INTO `wa_email` VALUES (278, 'sign', '857057533@qq.com', '6849', 2, '107.172.103.107', 1789899134);
INSERT INTO `wa_email` VALUES (279, 'sign', 'ddd@pugb.vip', '1368', 0, '57.129.115.108', 1789899137);
INSERT INTO `wa_email` VALUES (280, 'sign', '857057533@qq.com', '0789', 0, '107.172.103.107', 1789901827);
INSERT INTO `wa_email` VALUES (281, 'sign', '857057533@qq.com', '4865', 0, '107.172.103.107', 1789902813);
INSERT INTO `wa_email` VALUES (282, 'sign', '857057533@qq.com', '0237', 1, '107.172.103.107', 1789902841);
INSERT INTO `wa_email` VALUES (283, 'forget', '857057533@qq.com', '3824', 2, '107.172.103.107', 1789953336);
INSERT INTO `wa_email` VALUES (284, 'forget', '857057533@qq.com', '4723', 1, '107.172.103.107', 1789953557);
INSERT INTO `wa_email` VALUES (285, 'forget', '857057533@qq.com', '5960', 0, '103.127.218.161', 1790079353);
INSERT INTO `wa_email` VALUES (286, 'forget', '857057533@qq.com', '6412', 2, '14.154.67.115', 1790126514);
INSERT INTO `wa_email` VALUES (287, 'sign', '1273210928@qq.com', '6971', 1, '191.40.5.205', 1790142917);

-- ----------------------------
-- Table structure for wa_equipment
-- ----------------------------
DROP TABLE IF EXISTS `wa_equipment`;
CREATE TABLE `wa_equipment`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '设备名称',
  `uuid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '设备id',
  `user_id` int(10) NOT NULL DEFAULT 0 COMMENT '用户',
  `created_at` datetime NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime NULL DEFAULT NULL COMMENT '更新时间',
  `createtime` int(10) NOT NULL DEFAULT 0 COMMENT '添加日期',
  `lighting` tinyint(2) NOT NULL DEFAULT 3 COMMENT '灯光',
  `brightness` tinyint(2) NOT NULL DEFAULT 3 COMMENT '亮度',
  `sound` tinyint(2) NOT NULL DEFAULT 5 COMMENT '声音',
  `alarms` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '警告',
  `mobile_speed` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1开启',
  `fixed_speed` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1开启',
  `traffic_light_camera` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1开启',
  `alarm_which` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1deivice2phone',
  `speed_unit` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1km2%',
  `speed_alarm_device` tinyint(1) NOT NULL DEFAULT 1,
  `speed_alarm_phone` tinyint(1) NOT NULL DEFAULT 1,
  `road_alarm_device` tinyint(1) NOT NULL DEFAULT 1,
  `road_alarm_phone` tinyint(1) NOT NULL DEFAULT 1,
  `is_night` tinyint(1) NOT NULL DEFAULT 0,
  `alarm_threshold` tinyint(1) NOT NULL DEFAULT 0,
  `type` int(10) NOT NULL DEFAULT 0,
  `mobile_speed_phone` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1开启',
  `fixed_speed_phone` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1开启',
  `traffic_light_camera_phone` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1开启',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1120 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '超速检测设备' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of wa_equipment
-- ----------------------------
INSERT INTO `wa_equipment` VALUES (1099, 'SmarterDriving', '0001', 2, NULL, NULL, 1789553928, 3, 3, 5, NULL, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 1, 1, 1, 1);
INSERT INTO `wa_equipment` VALUES (1104, 'SmarterDriving', '1049', 4, NULL, NULL, 1789900708, 3, 1, 7, NULL, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 2, 1, 1, 1);
INSERT INTO `wa_equipment` VALUES (1116, 'SmarterDriving', 'BBAA', 5, NULL, NULL, 1790129107, 3, 3, 5, NULL, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 2, 1, 1, 1);
INSERT INTO `wa_equipment` VALUES (1117, 'SmarterDriving', '111B', 6, NULL, NULL, 1790142964, 3, 3, 5, NULL, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 2, 1, 1, 1);
INSERT INTO `wa_equipment` VALUES (1119, '哈哈哈哈哈', '1157', 6, NULL, NULL, 1790143041, 3, 1, 5, NULL, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 2, 1, 1, 1);

-- ----------------------------
-- Table structure for wa_feedback2
-- ----------------------------
DROP TABLE IF EXISTS `wa_feedback2`;
CREATE TABLE `wa_feedback2`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` int(10) NOT NULL DEFAULT 0 COMMENT '用户id',
  `feedtype` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '反馈问题',
  `images` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '反馈图',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '联系人',
  `mobile` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '联系方式',
  `content` varchar(1024) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '反馈内容',
  `createtime` int(10) NOT NULL DEFAULT 0 COMMENT '创建时间',
  `is_deal` tinyint(1) NOT NULL DEFAULT 0 COMMENT '处理',
  `created_at` datetime NOT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime NOT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '短信验证码表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of wa_feedback2
-- ----------------------------

-- ----------------------------
-- Table structure for wa_message
-- ----------------------------
DROP TABLE IF EXISTS `wa_message`;
CREATE TABLE `wa_message`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `message` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '公告',
  `createtime` bigint(16) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `created_at` datetime NOT NULL COMMENT '创建时间',
  `updated_at` datetime NOT NULL COMMENT '更新时间',
  `is_read` int(10) NOT NULL DEFAULT 0,
  `user_id` int(10) NOT NULL DEFAULT 0 COMMENT '管理端',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 22 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '短信验证码表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of wa_message
-- ----------------------------

-- ----------------------------
-- Table structure for wa_options
-- ----------------------------
DROP TABLE IF EXISTS `wa_options`;
CREATE TABLE `wa_options`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '键',
  `value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '值',
  `created_at` datetime NOT NULL DEFAULT '2022-08-15 00:00:00' COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT '2022-08-15 00:00:00' COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `name`(`name`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 21 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '选项表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of wa_options
-- ----------------------------
INSERT INTO `wa_options` VALUES (2, 'table_form_schema_wa_users', '{\"id\":{\"field\":\"id\",\"_field_id\":\"0\",\"comment\":\"主键\",\"control\":\"inputNumber\",\"control_args\":\"\",\"list_show\":true,\"enable_sort\":true,\"searchable\":true,\"search_type\":\"normal\",\"form_show\":false},\"username\":{\"field\":\"username\",\"_field_id\":\"1\",\"comment\":\"用户名\",\"control\":\"input\",\"control_args\":\"\",\"form_show\":true,\"list_show\":true,\"searchable\":true,\"search_type\":\"normal\",\"enable_sort\":false},\"nickname\":{\"field\":\"nickname\",\"_field_id\":\"2\",\"comment\":\"昵称\",\"control\":\"input\",\"control_args\":\"\",\"form_show\":true,\"list_show\":true,\"searchable\":true,\"search_type\":\"normal\",\"enable_sort\":false},\"password\":{\"field\":\"password\",\"_field_id\":\"3\",\"comment\":\"密码\",\"control\":\"input\",\"control_args\":\"\",\"form_show\":true,\"search_type\":\"normal\",\"list_show\":false,\"enable_sort\":false,\"searchable\":false},\"sex\":{\"field\":\"sex\",\"_field_id\":\"4\",\"comment\":\"性别\",\"control\":\"select\",\"control_args\":\"url:\\/app\\/admin\\/dict\\/get\\/sex\",\"form_show\":true,\"list_show\":true,\"searchable\":true,\"search_type\":\"normal\",\"enable_sort\":false},\"avatar\":{\"field\":\"avatar\",\"_field_id\":\"5\",\"comment\":\"头像\",\"control\":\"uploadImage\",\"control_args\":\"url:\\/app\\/admin\\/upload\\/avatar\",\"form_show\":true,\"list_show\":true,\"search_type\":\"normal\",\"enable_sort\":false,\"searchable\":false},\"email\":{\"field\":\"email\",\"_field_id\":\"6\",\"comment\":\"邮箱\",\"control\":\"input\",\"control_args\":\"\",\"form_show\":true,\"list_show\":true,\"searchable\":true,\"search_type\":\"normal\",\"enable_sort\":false},\"mobile\":{\"field\":\"mobile\",\"_field_id\":\"7\",\"comment\":\"手机\",\"control\":\"input\",\"control_args\":\"\",\"form_show\":true,\"list_show\":true,\"searchable\":true,\"search_type\":\"normal\",\"enable_sort\":false},\"level\":{\"field\":\"level\",\"_field_id\":\"8\",\"comment\":\"等级\",\"control\":\"inputNumber\",\"control_args\":\"\",\"form_show\":true,\"searchable\":true,\"search_type\":\"normal\",\"list_show\":false,\"enable_sort\":false},\"birthday\":{\"field\":\"birthday\",\"_field_id\":\"9\",\"comment\":\"生日\",\"control\":\"datePicker\",\"control_args\":\"\",\"form_show\":true,\"searchable\":true,\"search_type\":\"between\",\"list_show\":false,\"enable_sort\":false},\"money\":{\"field\":\"money\",\"_field_id\":\"10\",\"comment\":\"余额(元)\",\"control\":\"inputNumber\",\"control_args\":\"\",\"form_show\":true,\"searchable\":true,\"search_type\":\"normal\",\"list_show\":false,\"enable_sort\":false},\"score\":{\"field\":\"score\",\"_field_id\":\"11\",\"comment\":\"积分\",\"control\":\"inputNumber\",\"control_args\":\"\",\"form_show\":true,\"searchable\":true,\"search_type\":\"normal\",\"list_show\":false,\"enable_sort\":false},\"last_time\":{\"field\":\"last_time\",\"_field_id\":\"12\",\"comment\":\"登录时间\",\"control\":\"dateTimePicker\",\"control_args\":\"\",\"form_show\":true,\"searchable\":true,\"search_type\":\"between\",\"list_show\":false,\"enable_sort\":false},\"last_ip\":{\"field\":\"last_ip\",\"_field_id\":\"13\",\"comment\":\"登录ip\",\"control\":\"input\",\"control_args\":\"\",\"form_show\":true,\"searchable\":true,\"search_type\":\"normal\",\"list_show\":false,\"enable_sort\":false},\"join_time\":{\"field\":\"join_time\",\"_field_id\":\"14\",\"comment\":\"注册时间\",\"control\":\"dateTimePicker\",\"control_args\":\"\",\"form_show\":true,\"searchable\":true,\"search_type\":\"between\",\"list_show\":false,\"enable_sort\":false},\"join_ip\":{\"field\":\"join_ip\",\"_field_id\":\"15\",\"comment\":\"注册ip\",\"control\":\"input\",\"control_args\":\"\",\"form_show\":true,\"searchable\":true,\"search_type\":\"normal\",\"list_show\":false,\"enable_sort\":false},\"token\":{\"field\":\"token\",\"_field_id\":\"16\",\"comment\":\"token\",\"control\":\"input\",\"control_args\":\"\",\"search_type\":\"normal\",\"form_show\":false,\"list_show\":false,\"enable_sort\":false,\"searchable\":false},\"created_at\":{\"field\":\"created_at\",\"_field_id\":\"17\",\"comment\":\"创建时间\",\"control\":\"dateTimePicker\",\"control_args\":\"\",\"form_show\":true,\"search_type\":\"between\",\"list_show\":false,\"enable_sort\":false,\"searchable\":false},\"updated_at\":{\"field\":\"updated_at\",\"_field_id\":\"18\",\"comment\":\"更新时间\",\"control\":\"dateTimePicker\",\"control_args\":\"\",\"search_type\":\"between\",\"form_show\":false,\"list_show\":false,\"enable_sort\":false,\"searchable\":false},\"role\":{\"field\":\"role\",\"_field_id\":\"19\",\"comment\":\"角色\",\"control\":\"inputNumber\",\"control_args\":\"\",\"search_type\":\"normal\",\"form_show\":false,\"list_show\":false,\"enable_sort\":false,\"searchable\":false},\"status\":{\"field\":\"status\",\"_field_id\":\"20\",\"comment\":\"禁用\",\"control\":\"switch\",\"control_args\":\"\",\"form_show\":true,\"list_show\":true,\"search_type\":\"normal\",\"enable_sort\":false,\"searchable\":false}}', '2022-08-15 00:00:00', '2022-12-23 15:28:13');
INSERT INTO `wa_options` VALUES (3, 'table_form_schema_wa_roles', '{\"id\":{\"field\":\"id\",\"_field_id\":\"0\",\"comment\":\"主键\",\"control\":\"inputNumber\",\"control_args\":\"\",\"list_show\":true,\"search_type\":\"normal\",\"form_show\":false,\"enable_sort\":false,\"searchable\":false},\"name\":{\"field\":\"name\",\"_field_id\":\"1\",\"comment\":\"角色组\",\"control\":\"input\",\"control_args\":\"\",\"form_show\":true,\"list_show\":true,\"search_type\":\"normal\",\"enable_sort\":false,\"searchable\":false},\"rules\":{\"field\":\"rules\",\"_field_id\":\"2\",\"comment\":\"权限\",\"control\":\"treeSelectMulti\",\"control_args\":\"url:\\/app\\/admin\\/rule\\/get?type=0,1,2\",\"form_show\":true,\"list_show\":true,\"search_type\":\"normal\",\"enable_sort\":false,\"searchable\":false},\"created_at\":{\"field\":\"created_at\",\"_field_id\":\"3\",\"comment\":\"创建时间\",\"control\":\"dateTimePicker\",\"control_args\":\"\",\"search_type\":\"normal\",\"form_show\":false,\"list_show\":false,\"enable_sort\":false,\"searchable\":false},\"updated_at\":{\"field\":\"updated_at\",\"_field_id\":\"4\",\"comment\":\"更新时间\",\"control\":\"dateTimePicker\",\"control_args\":\"\",\"search_type\":\"normal\",\"form_show\":false,\"list_show\":false,\"enable_sort\":false,\"searchable\":false},\"pid\":{\"field\":\"pid\",\"_field_id\":\"5\",\"comment\":\"父级\",\"control\":\"select\",\"control_args\":\"url:\\/app\\/admin\\/role\\/select?format=tree\",\"form_show\":true,\"list_show\":true,\"search_type\":\"normal\",\"enable_sort\":false,\"searchable\":false}}', '2022-08-15 00:00:00', '2022-12-19 14:24:25');
INSERT INTO `wa_options` VALUES (4, 'table_form_schema_wa_rules', '{\"id\":{\"field\":\"id\",\"_field_id\":\"0\",\"comment\":\"主键\",\"control\":\"inputNumber\",\"control_args\":\"\",\"search_type\":\"normal\",\"form_show\":false,\"list_show\":false,\"enable_sort\":false,\"searchable\":false},\"title\":{\"field\":\"title\",\"_field_id\":\"1\",\"comment\":\"标题\",\"control\":\"input\",\"control_args\":\"\",\"form_show\":true,\"list_show\":true,\"searchable\":true,\"search_type\":\"normal\",\"enable_sort\":false},\"icon\":{\"field\":\"icon\",\"_field_id\":\"2\",\"comment\":\"图标\",\"control\":\"iconPicker\",\"control_args\":\"\",\"form_show\":true,\"list_show\":true,\"search_type\":\"normal\",\"enable_sort\":false,\"searchable\":false},\"key\":{\"field\":\"key\",\"_field_id\":\"3\",\"comment\":\"标识\",\"control\":\"input\",\"control_args\":\"\",\"form_show\":true,\"list_show\":true,\"searchable\":true,\"search_type\":\"normal\",\"enable_sort\":false},\"pid\":{\"field\":\"pid\",\"_field_id\":\"4\",\"comment\":\"上级菜单\",\"control\":\"treeSelect\",\"control_args\":\"\\/app\\/admin\\/rule\\/select?format=tree&type=0,1\",\"form_show\":true,\"list_show\":true,\"search_type\":\"normal\",\"enable_sort\":false,\"searchable\":false},\"created_at\":{\"field\":\"created_at\",\"_field_id\":\"5\",\"comment\":\"创建时间\",\"control\":\"dateTimePicker\",\"control_args\":\"\",\"search_type\":\"normal\",\"form_show\":false,\"list_show\":false,\"enable_sort\":false,\"searchable\":false},\"updated_at\":{\"field\":\"updated_at\",\"_field_id\":\"6\",\"comment\":\"更新时间\",\"control\":\"dateTimePicker\",\"control_args\":\"\",\"search_type\":\"normal\",\"form_show\":false,\"list_show\":false,\"enable_sort\":false,\"searchable\":false},\"href\":{\"field\":\"href\",\"_field_id\":\"7\",\"comment\":\"url\",\"control\":\"input\",\"control_args\":\"\",\"form_show\":true,\"list_show\":true,\"search_type\":\"normal\",\"enable_sort\":false,\"searchable\":false},\"type\":{\"field\":\"type\",\"_field_id\":\"8\",\"comment\":\"类型\",\"control\":\"select\",\"control_args\":\"data:0:目录,1:菜单,2:权限\",\"form_show\":true,\"list_show\":true,\"searchable\":true,\"search_type\":\"normal\",\"enable_sort\":false},\"weight\":{\"field\":\"weight\",\"_field_id\":\"9\",\"comment\":\"排序\",\"control\":\"inputNumber\",\"control_args\":\"\",\"form_show\":true,\"list_show\":true,\"search_type\":\"normal\",\"enable_sort\":false,\"searchable\":false}}', '2022-08-15 00:00:00', '2022-12-08 11:44:45');
INSERT INTO `wa_options` VALUES (5, 'table_form_schema_wa_admins', '{\"id\":{\"field\":\"id\",\"_field_id\":\"0\",\"comment\":\"ID\",\"control\":\"inputNumber\",\"control_args\":\"\",\"list_show\":true,\"enable_sort\":true,\"search_type\":\"between\",\"form_show\":false,\"searchable\":false},\"username\":{\"field\":\"username\",\"_field_id\":\"1\",\"comment\":\"用户名\",\"control\":\"input\",\"control_args\":\"\",\"form_show\":true,\"list_show\":true,\"searchable\":true,\"search_type\":\"normal\",\"enable_sort\":false},\"nickname\":{\"field\":\"nickname\",\"_field_id\":\"2\",\"comment\":\"昵称\",\"control\":\"input\",\"control_args\":\"\",\"form_show\":true,\"list_show\":true,\"searchable\":true,\"search_type\":\"normal\",\"enable_sort\":false},\"password\":{\"field\":\"password\",\"_field_id\":\"3\",\"comment\":\"密码\",\"control\":\"input\",\"control_args\":\"\",\"form_show\":true,\"search_type\":\"normal\",\"list_show\":false,\"enable_sort\":false,\"searchable\":false},\"avatar\":{\"field\":\"avatar\",\"_field_id\":\"4\",\"comment\":\"头像\",\"control\":\"uploadImage\",\"control_args\":\"url:\\/app\\/admin\\/upload\\/avatar\",\"form_show\":true,\"list_show\":true,\"search_type\":\"normal\",\"enable_sort\":false,\"searchable\":false},\"email\":{\"field\":\"email\",\"_field_id\":\"5\",\"comment\":\"邮箱\",\"control\":\"input\",\"control_args\":\"\",\"form_show\":true,\"list_show\":true,\"searchable\":true,\"search_type\":\"normal\",\"enable_sort\":false},\"mobile\":{\"field\":\"mobile\",\"_field_id\":\"6\",\"comment\":\"手机\",\"control\":\"input\",\"control_args\":\"\",\"form_show\":true,\"list_show\":true,\"searchable\":true,\"search_type\":\"normal\",\"enable_sort\":false},\"created_at\":{\"field\":\"created_at\",\"_field_id\":\"7\",\"comment\":\"创建时间\",\"control\":\"dateTimePicker\",\"control_args\":\"\",\"form_show\":true,\"searchable\":true,\"search_type\":\"between\",\"list_show\":false,\"enable_sort\":false},\"updated_at\":{\"field\":\"updated_at\",\"_field_id\":\"8\",\"comment\":\"更新时间\",\"control\":\"dateTimePicker\",\"control_args\":\"\",\"form_show\":true,\"search_type\":\"normal\",\"list_show\":false,\"enable_sort\":false,\"searchable\":false},\"login_at\":{\"field\":\"login_at\",\"_field_id\":\"9\",\"comment\":\"登录时间\",\"control\":\"dateTimePicker\",\"control_args\":\"\",\"form_show\":true,\"list_show\":true,\"search_type\":\"between\",\"enable_sort\":false,\"searchable\":false},\"status\":{\"field\":\"status\",\"_field_id\":\"10\",\"comment\":\"禁用\",\"control\":\"switch\",\"control_args\":\"\",\"form_show\":true,\"list_show\":true,\"search_type\":\"normal\",\"enable_sort\":false,\"searchable\":false}}', '2022-08-15 00:00:00', '2022-12-23 15:36:48');
INSERT INTO `wa_options` VALUES (6, 'table_form_schema_wa_options', '{\"id\":{\"field\":\"id\",\"_field_id\":\"0\",\"comment\":\"\",\"control\":\"inputNumber\",\"control_args\":\"\",\"list_show\":true,\"search_type\":\"normal\",\"form_show\":false,\"enable_sort\":false,\"searchable\":false},\"name\":{\"field\":\"name\",\"_field_id\":\"1\",\"comment\":\"键\",\"control\":\"input\",\"control_args\":\"\",\"form_show\":true,\"list_show\":true,\"search_type\":\"normal\",\"enable_sort\":false,\"searchable\":false},\"value\":{\"field\":\"value\",\"_field_id\":\"2\",\"comment\":\"值\",\"control\":\"textArea\",\"control_args\":\"\",\"form_show\":true,\"list_show\":true,\"search_type\":\"normal\",\"enable_sort\":false,\"searchable\":false},\"created_at\":{\"field\":\"created_at\",\"_field_id\":\"3\",\"comment\":\"创建时间\",\"control\":\"dateTimePicker\",\"control_args\":\"\",\"search_type\":\"normal\",\"form_show\":false,\"list_show\":false,\"enable_sort\":false,\"searchable\":false},\"updated_at\":{\"field\":\"updated_at\",\"_field_id\":\"4\",\"comment\":\"更新时间\",\"control\":\"dateTimePicker\",\"control_args\":\"\",\"search_type\":\"normal\",\"form_show\":false,\"list_show\":false,\"enable_sort\":false,\"searchable\":false}}', '2022-08-15 00:00:00', '2022-12-08 11:36:57');
INSERT INTO `wa_options` VALUES (7, 'table_form_schema_wa_uploads', '{\"id\":{\"field\":\"id\",\"_field_id\":\"0\",\"comment\":\"主键\",\"control\":\"inputNumber\",\"control_args\":\"\",\"list_show\":true,\"enable_sort\":true,\"search_type\":\"normal\",\"form_show\":false,\"searchable\":false},\"name\":{\"field\":\"name\",\"_field_id\":\"1\",\"comment\":\"名称\",\"control\":\"input\",\"control_args\":\"\",\"list_show\":true,\"searchable\":true,\"search_type\":\"normal\",\"form_show\":false,\"enable_sort\":false},\"url\":{\"field\":\"url\",\"_field_id\":\"2\",\"comment\":\"文件\",\"control\":\"upload\",\"control_args\":\"url:\\/app\\/admin\\/upload\\/file\",\"form_show\":true,\"list_show\":true,\"search_type\":\"normal\",\"enable_sort\":false,\"searchable\":false},\"admin_id\":{\"field\":\"admin_id\",\"_field_id\":\"3\",\"comment\":\"管理员\",\"control\":\"select\",\"control_args\":\"url:\\/app\\/admin\\/admin\\/select?format=select\",\"search_type\":\"normal\",\"form_show\":false,\"list_show\":false,\"enable_sort\":false,\"searchable\":false},\"file_size\":{\"field\":\"file_size\",\"_field_id\":\"4\",\"comment\":\"文件大小\",\"control\":\"inputNumber\",\"control_args\":\"\",\"list_show\":true,\"search_type\":\"between\",\"form_show\":false,\"enable_sort\":false,\"searchable\":false},\"mime_type\":{\"field\":\"mime_type\",\"_field_id\":\"5\",\"comment\":\"mime类型\",\"control\":\"input\",\"control_args\":\"\",\"list_show\":true,\"search_type\":\"normal\",\"form_show\":false,\"enable_sort\":false,\"searchable\":false},\"image_width\":{\"field\":\"image_width\",\"_field_id\":\"6\",\"comment\":\"图片宽度\",\"control\":\"inputNumber\",\"control_args\":\"\",\"list_show\":true,\"search_type\":\"normal\",\"form_show\":false,\"enable_sort\":false,\"searchable\":false},\"image_height\":{\"field\":\"image_height\",\"_field_id\":\"7\",\"comment\":\"图片高度\",\"control\":\"inputNumber\",\"control_args\":\"\",\"list_show\":true,\"search_type\":\"normal\",\"form_show\":false,\"enable_sort\":false,\"searchable\":false},\"ext\":{\"field\":\"ext\",\"_field_id\":\"8\",\"comment\":\"扩展名\",\"control\":\"input\",\"control_args\":\"\",\"list_show\":true,\"searchable\":true,\"search_type\":\"normal\",\"form_show\":false,\"enable_sort\":false},\"storage\":{\"field\":\"storage\",\"_field_id\":\"9\",\"comment\":\"存储位置\",\"control\":\"input\",\"control_args\":\"\",\"search_type\":\"normal\",\"form_show\":false,\"list_show\":false,\"enable_sort\":false,\"searchable\":false},\"created_at\":{\"field\":\"created_at\",\"_field_id\":\"10\",\"comment\":\"上传时间\",\"control\":\"dateTimePicker\",\"control_args\":\"\",\"searchable\":true,\"search_type\":\"between\",\"form_show\":false,\"list_show\":false,\"enable_sort\":false},\"category\":{\"field\":\"category\",\"_field_id\":\"11\",\"comment\":\"类别\",\"control\":\"select\",\"control_args\":\"url:\\/app\\/admin\\/dict\\/get\\/upload\",\"form_show\":true,\"list_show\":true,\"searchable\":true,\"search_type\":\"normal\",\"enable_sort\":false},\"updated_at\":{\"field\":\"updated_at\",\"_field_id\":\"12\",\"comment\":\"更新时间\",\"control\":\"dateTimePicker\",\"control_args\":\"\",\"form_show\":true,\"list_show\":true,\"search_type\":\"normal\",\"enable_sort\":false,\"searchable\":false}}', '2022-08-15 00:00:00', '2022-12-08 11:47:45');
INSERT INTO `wa_options` VALUES (8, 'dict_upload', '[{\"value\":\"1\",\"name\":\"图片\"},{\"value\":\"2\",\"name\":\"word\"},{\"value\":\"3\",\"name\":\"pdf\"}]', '2022-12-04 16:24:13', '2025-06-28 10:11:58');
INSERT INTO `wa_options` VALUES (9, 'dict_sex', '[{\"value\":\"0\",\"name\":\"女\"},{\"value\":\"1\",\"name\":\"男\"}]', '2022-12-04 15:04:40', '2022-12-04 15:04:40');
INSERT INTO `wa_options` VALUES (10, 'dict_status', '[{\"value\":\"0\",\"name\":\"正常\"},{\"value\":\"1\",\"name\":\"禁用\"}]', '2022-12-04 15:05:09', '2022-12-04 15:05:09');
INSERT INTO `wa_options` VALUES (11, 'table_form_schema_wa_admin_roles', '{\"id\":{\"field\":\"id\",\"_field_id\":\"0\",\"comment\":\"主键\",\"control\":\"inputNumber\",\"control_args\":\"\",\"list_show\":true,\"enable_sort\":true,\"searchable\":true,\"search_type\":\"normal\",\"form_show\":false},\"role_id\":{\"field\":\"role_id\",\"_field_id\":\"1\",\"comment\":\"角色id\",\"control\":\"inputNumber\",\"control_args\":\"\",\"form_show\":true,\"list_show\":true,\"search_type\":\"normal\",\"enable_sort\":false,\"searchable\":false},\"admin_id\":{\"field\":\"admin_id\",\"_field_id\":\"2\",\"comment\":\"管理员id\",\"control\":\"inputNumber\",\"control_args\":\"\",\"form_show\":true,\"list_show\":true,\"search_type\":\"normal\",\"enable_sort\":false,\"searchable\":false}}', '2022-08-15 00:00:00', '2022-12-20 19:42:51');
INSERT INTO `wa_options` VALUES (12, 'dict_dict_name', '[{\"value\":\"dict_name\",\"name\":\"字典名称\"},{\"value\":\"status\",\"name\":\"启禁用状态\"},{\"value\":\"sex\",\"name\":\"性别\"},{\"value\":\"upload\",\"name\":\"附件分类\"}]', '2022-08-15 00:00:00', '2022-12-20 19:42:51');
INSERT INTO `wa_options` VALUES (13, 'system_config', '{\"logo\":{\"record_time\":\"15\",\"record_long\":\"2\",\"forbidden\":\"\"},\"menu\":{\"data\":\"\\/app\\/admin\\/rule\\/get\",\"method\":\"GET\",\"accordion\":true,\"collapse\":false,\"control\":false,\"controlWidth\":2000,\"select\":\"0\",\"async\":true},\"tab\":{\"enable\":true,\"keepState\":true,\"session\":true,\"preload\":false,\"max\":\"30\",\"index\":{\"id\":\"0\",\"href\":\"\\/app\\/admin\\/index\\/dashboard\",\"title\":\"\\u4eea\\u8868\\u76d8\"}},\"theme\":{\"defaultColor\":\"2\",\"defaultMenu\":\"dark-theme\",\"defaultHeader\":\"light-theme\",\"allowCustom\":true,\"banner\":false},\"colors\":[{\"id\":\"1\",\"color\":\"#36b368\",\"second\":\"#f0f9eb\"},{\"id\":\"2\",\"color\":\"#2d8cf0\",\"second\":\"#ecf5ff\"},{\"id\":\"3\",\"color\":\"#f6ad55\",\"second\":\"#fdf6ec\"},{\"id\":\"4\",\"color\":\"#f56c6c\",\"second\":\"#fef0f0\"},{\"id\":\"5\",\"color\":\"#3963bc\",\"second\":\"#ecf5ff\"}],\"other\":{\"keepLoad\":\"500\",\"autoHead\":false,\"footer\":false},\"header\":{\"message\":false}}', '2025-07-29 18:16:06', '2025-09-04 14:57:34');
INSERT INTO `wa_options` VALUES (14, 'email_setting', '{\"Host\":\"smtp.qiye.aliyun.com\",\"Username\":\"sa@vit-uni.com\",\"Password\":\"vTlgnYeRfNbvKUFu\",\"SMTPSecure\":\"ssl\",\"Port\":\"465\",\"From\":\"sa@vit-uni.com\",\"FromName\":\"DRIVONA\"}', '2025-12-12 09:26:18', '2026-09-18 16:35:14');
INSERT INTO `wa_options` VALUES (15, 'email_template_captcha', '{\"from\":\"DRIVONA\",\"subject\":\"验证您的邮箱地址\",\"content\":\"您好，<br><br>您正在使用此邮箱地址注册账号。<br><br>请使用以下验证码完成邮箱验证：<br><br>验证码：<br><b>{{verification_code}}<\\/b><br><br>该验证码将在 {{minutes}} 分钟后失效。<br><br>如果这不是您本人的操作，请忽略此邮件，无需进行任何操作。<br><br>此邮件由系统自动发送，请勿直接回复。\"}', '2025-12-12 09:26:18', '2026-09-18 16:15:08');
INSERT INTO `wa_options` VALUES (16, 'email_template_captcha_en', '{\"from\":\"DRIVONA\",\"subject\":\"Verify your email address\",\"content\":\"Verify your email address<br><br>Hello,<br><br>You\'re using this email address to create an account.<br><br>Use the verification code below to verify your email address:<br><br>Verification code:<br><b>{{verification_code}}<\\/b><br><br>This code expires in {{minutes}} minutes.<br><br>If you didn\'t request this, you can safely ignore this email. No further action is required.<br><br>This is an automated email. Please do not reply.\"}', '2026-09-16 15:33:14', '2026-09-18 16:15:21');
INSERT INTO `wa_options` VALUES (17, 'email_template_captcha_de', '{\"from\":\"sa@vit-uni.com\",\"subject\":\"Bestätigen Sie Ihre E-Mail-Adresse\",\"content\":\"Bestätigen Sie Ihre E-Mail-Adresse<br><br>Hallo,<br><br>Sie verwenden diese E-Mail-Adresse, um ein Konto zu erstellen.<br><br>Bitte verwenden Sie den folgenden Bestätigungscode, um Ihre E-Mail-Adresse zu bestätigen:<br><br>Bestätigungscode:<br><b>{{verification_code}}</b><br><br>Dieser Code ist {{minutes}} Minuten lang gültig.<br><br>Wenn Sie diese Registrierung nicht veranlasst haben, können Sie diese E-Mail ignorieren. Sie müssen nichts weiter tun.<br><br>Diese E-Mail wurde automatisch versendet. Bitte antworten Sie nicht darauf.\"}', '2026-09-16 15:33:14', '2026-09-16 15:33:14');
INSERT INTO `wa_options` VALUES (18, 'email_template_captcha_forget', '{\"from\":\"sa@vit-uni.com\",\"subject\":\"重置您的密码\",\"content\":\"您好，<br><br>我们收到了重置您账号密码的请求。<br><br>请使用以下验证码完成身份验证并重置密码：<br><br>验证码：<br><b>{{verification_code}}</b><br><br>该验证码将在 {{minutes}} 分钟后失效。<br><br>如果您没有申请重置密码，请忽略此邮件，您的密码不会被修改。<br><br>此邮件由系统自动发送，请勿直接回复。\"}', '2026-09-17 11:48:52', '2026-09-17 11:48:52');
INSERT INTO `wa_options` VALUES (19, 'email_template_captcha_forget_en', '{\"from\":\"sa@vit-uni.com\",\"subject\":\"Reset your password\",\"content\":\"Reset your password<br><br>Hello,<br><br>We received a request to reset the password for your account.<br><br>Use the verification code below to verify your identity and reset your password:<br><br>Verification code:<br><b>{{verification_code}}</b><br><br>This code expires in {{minutes}} minutes.<br><br>If you didn’t request a password reset, you can safely ignore this email. Your password will not be changed.<br><br>This is an automated email. Please do not reply.\"}', '2026-09-17 11:48:52', '2026-09-17 11:49:33');
INSERT INTO `wa_options` VALUES (20, 'email_template_captcha_forget_de', '{\"from\":\"sa@vit-uni.com\",\"subject\":\"Passwort zurücksetzen\",\"content\":\"Passwort zurücksetzen<br><br>Hallo,<br><br>wir haben eine Anfrage zum Zurücksetzen des Passworts für Ihr Konto erhalten.<br><br>Bitte verwenden Sie den folgenden Bestätigungscode, um Ihre Identität zu bestätigen und Ihr Passwort zurückzusetzen:<br><br>Bestätigungscode:<br><b>{{verification_code}}</b><br><br>Dieser Code ist {{minutes}} Minuten lang gültig.<br><br>Wenn Sie das Zurücksetzen Ihres Passworts nicht angefordert haben, können Sie diese E-Mail ignorieren. Ihr Passwort wird nicht geändert.<br><br>Diese E-Mail wurde automatisch versendet. Bitte antworten Sie nicht darauf.\"}', '2026-09-17 11:48:52', '2026-09-17 11:50:02');

-- ----------------------------
-- Table structure for wa_roles
-- ----------------------------
DROP TABLE IF EXISTS `wa_roles`;
CREATE TABLE `wa_roles`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `name` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '角色组',
  `rules` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '权限',
  `created_at` datetime NOT NULL COMMENT '创建时间',
  `updated_at` datetime NOT NULL COMMENT '更新时间',
  `pid` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '父级',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 9 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '管理员角色' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of wa_roles
-- ----------------------------
INSERT INTO `wa_roles` VALUES (1, '超级管理员', '*', '2022-08-13 16:15:01', '2022-12-23 12:05:07', NULL);

-- ----------------------------
-- Table structure for wa_rules
-- ----------------------------
DROP TABLE IF EXISTS `wa_rules`;
CREATE TABLE `wa_rules`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '标题',
  `icon` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '图标',
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '标识',
  `pid` int(10) UNSIGNED NULL DEFAULT 0 COMMENT '上级菜单',
  `created_at` datetime NOT NULL COMMENT '创建时间',
  `updated_at` datetime NOT NULL COMMENT '更新时间',
  `href` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'url',
  `type` int(11) NOT NULL DEFAULT 1 COMMENT '类型',
  `weight` int(11) NULL DEFAULT 0 COMMENT '排序',
  `is_show` int(1) NOT NULL DEFAULT 0 COMMENT '1显示',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 164 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '权限规则' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of wa_rules
-- ----------------------------
INSERT INTO `wa_rules` VALUES (1, '数据库', 'layui-icon-template-1', 'database', 0, '2025-06-27 14:59:32', '2025-06-28 10:10:58', NULL, 0, 1000, 0);
INSERT INTO `wa_rules` VALUES (2, '所有表', NULL, 'plugin\\admin\\app\\controller\\TableController', 1, '2025-06-27 14:59:32', '2025-06-27 14:59:32', '/app/admin/table/index', 1, 800, 0);
INSERT INTO `wa_rules` VALUES (3, '权限管理', 'layui-icon-vercode', 'auth', 0, '2025-06-27 14:59:32', '2025-06-28 10:10:53', NULL, 0, 900, 1);
INSERT INTO `wa_rules` VALUES (4, '账户管理', NULL, 'plugin\\admin\\app\\controller\\AdminController', 3, '2025-06-27 14:59:32', '2025-06-27 14:59:32', '/app/admin/admin/index', 1, 1000, 0);
INSERT INTO `wa_rules` VALUES (5, '角色管理', NULL, 'plugin\\admin\\app\\controller\\RoleController', 3, '2025-06-27 14:59:32', '2025-06-27 14:59:32', '/app/admin/role/index', 1, 900, 0);
INSERT INTO `wa_rules` VALUES (6, '菜单管理', NULL, 'plugin\\admin\\app\\controller\\RuleController', 0, '2025-06-27 14:59:32', '2025-06-27 14:59:32', '/app/admin/rule/index', 0, 800, 0);
INSERT INTO `wa_rules` VALUES (7, 'App管理', 'layui-icon-username', 'user', 0, '2025-06-27 14:59:32', '2025-06-28 10:10:53', NULL, 0, 800, 1);
INSERT INTO `wa_rules` VALUES (8, '用户', NULL, 'plugin\\admin\\app\\controller\\UserController', 7, '2025-06-27 14:59:32', '2025-06-27 14:59:32', '/app/admin/user/index', 1, 800, 0);
INSERT INTO `wa_rules` VALUES (9, '通用设置', 'layui-icon-set', 'common', 0, '2025-06-27 14:59:32', '2025-07-31 14:52:22', NULL, 0, 700, 1);
INSERT INTO `wa_rules` VALUES (10, '个人资料', NULL, 'plugin\\admin\\app\\controller\\AccountController', 9, '2025-06-27 14:59:32', '2025-06-27 14:59:32', '/app/admin/account/index', 1, 800, 0);
INSERT INTO `wa_rules` VALUES (11, '附件管理', NULL, 'plugin\\admin\\app\\controller\\UploadController', 9, '2025-06-27 14:59:32', '2025-06-27 14:59:32', '/app/admin/upload/index', 1, 700, 0);
INSERT INTO `wa_rules` VALUES (12, '字典设置', NULL, 'plugin\\admin\\app\\controller\\DictController', 9, '2025-06-27 14:59:32', '2025-06-27 14:59:32', '/app/admin/dict/index', 1, 600, 0);
INSERT INTO `wa_rules` VALUES (13, '系统设置', NULL, 'plugin\\admin\\app\\controller\\ConfigController', 3, '2025-06-27 14:59:32', '2025-06-27 14:59:32', '/app/admin/config/index', 0, 500, 0);
INSERT INTO `wa_rules` VALUES (14, '插件管理', 'layui-icon-app', 'plugin', 0, '2025-06-27 14:59:32', '2025-06-27 14:59:32', NULL, 0, 600, 0);
INSERT INTO `wa_rules` VALUES (15, '应用插件', NULL, 'plugin\\admin\\app\\controller\\PluginController', 14, '2025-06-27 14:59:32', '2025-06-27 14:59:32', '/app/admin/plugin/index', 1, 800, 0);
INSERT INTO `wa_rules` VALUES (16, '开发辅助', 'layui-icon-fonts-code', 'dev', 0, '2025-06-27 14:59:32', '2025-06-28 10:53:30', NULL, 0, 500, 0);
INSERT INTO `wa_rules` VALUES (17, '表单构建', NULL, 'plugin\\admin\\app\\controller\\DevController', 16, '2025-06-27 14:59:32', '2025-06-27 14:59:32', '/app/admin/dev/form-build', 1, 800, 0);
INSERT INTO `wa_rules` VALUES (18, '示例页面', 'layui-icon-templeate-1', 'demos', 0, '2025-06-27 14:59:32', '2025-06-28 11:26:02', NULL, 0, 400, 0);
INSERT INTO `wa_rules` VALUES (19, '工作空间', 'layui-icon-console', 'demo1', 18, '2025-06-27 14:59:32', '2025-06-27 14:59:32', '', 0, 0, 0);
INSERT INTO `wa_rules` VALUES (20, '控制后台', 'layui-icon-console', 'demo10', 19, '2025-06-27 14:59:32', '2025-06-27 14:59:32', '/app/admin/demos/console/console1.html', 1, 0, 0);
INSERT INTO `wa_rules` VALUES (21, '数据分析', 'layui-icon-console', 'demo13', 19, '2025-06-27 14:59:32', '2025-06-27 14:59:32', '/app/admin/demos/console/console2.html', 1, 0, 0);
INSERT INTO `wa_rules` VALUES (22, '百度一下', 'layui-icon-console', 'demo14', 19, '2025-06-27 14:59:32', '2025-06-27 14:59:32', 'http://www.baidu.com', 1, 0, 0);
INSERT INTO `wa_rules` VALUES (23, '主题预览', 'layui-icon-console', 'demo15', 19, '2025-06-27 14:59:32', '2025-06-27 14:59:32', '/app/admin/demos/system/theme.html', 1, 0, 0);
INSERT INTO `wa_rules` VALUES (24, '常用组件', 'layui-icon-component', 'demo20', 18, '2025-06-27 14:59:32', '2025-06-27 14:59:32', '', 0, 0, 0);
INSERT INTO `wa_rules` VALUES (25, '功能按钮', 'layui-icon-face-smile', 'demo2011', 24, '2025-06-27 14:59:32', '2025-06-27 14:59:32', '/app/admin/demos/document/button.html', 1, 0, 0);
INSERT INTO `wa_rules` VALUES (26, '表单集合', 'layui-icon-face-cry', 'demo2014', 24, '2025-06-27 14:59:32', '2025-06-27 14:59:32', '/app/admin/demos/document/form.html', 1, 0, 0);
INSERT INTO `wa_rules` VALUES (27, '字体图标', 'layui-icon-face-cry', 'demo2010', 24, '2025-06-27 14:59:32', '2025-06-27 14:59:32', '/app/admin/demos/document/icon.html', 1, 0, 0);
INSERT INTO `wa_rules` VALUES (28, '多选下拉', 'layui-icon-face-cry', 'demo2012', 24, '2025-06-27 14:59:32', '2025-06-27 14:59:32', '/app/admin/demos/document/select.html', 1, 0, 0);
INSERT INTO `wa_rules` VALUES (29, '动态标签', 'layui-icon-face-cry', 'demo2013', 24, '2025-06-27 14:59:32', '2025-06-27 14:59:32', '/app/admin/demos/document/tag.html', 1, 0, 0);
INSERT INTO `wa_rules` VALUES (30, '数据表格', 'layui-icon-face-cry', 'demo2031', 24, '2025-06-27 14:59:32', '2025-06-27 14:59:32', '/app/admin/demos/document/table.html', 1, 0, 0);
INSERT INTO `wa_rules` VALUES (31, '分布表单', 'layui-icon-face-cry', 'demo2032', 24, '2025-06-27 14:59:32', '2025-06-27 14:59:32', '/app/admin/demos/document/step.html', 1, 0, 0);
INSERT INTO `wa_rules` VALUES (32, '树形表格', 'layui-icon-face-cry', 'demo2033', 24, '2025-06-27 14:59:32', '2025-06-27 14:59:32', '/app/admin/demos/document/treetable.html', 1, 0, 0);
INSERT INTO `wa_rules` VALUES (33, '树状结构', 'layui-icon-face-cry', 'demo2034', 24, '2025-06-27 14:59:32', '2025-06-27 14:59:32', '/app/admin/demos/document/dtree.html', 1, 0, 0);
INSERT INTO `wa_rules` VALUES (34, '文本编辑', 'layui-icon-face-cry', 'demo2035', 24, '2025-06-27 14:59:32', '2025-06-27 14:59:32', '/app/admin/demos/document/tinymce.html', 1, 0, 0);
INSERT INTO `wa_rules` VALUES (35, '卡片组件', 'layui-icon-face-cry', 'demo2036', 24, '2025-06-27 14:59:32', '2025-06-27 14:59:32', '/app/admin/demos/document/card.html', 1, 0, 0);
INSERT INTO `wa_rules` VALUES (36, '抽屉组件', 'layui-icon-face-cry', 'demo2021', 24, '2025-06-27 14:59:32', '2025-06-27 14:59:32', '/app/admin/demos/document/drawer.html', 1, 0, 0);
INSERT INTO `wa_rules` VALUES (37, '消息通知', 'layui-icon-face-cry', 'demo2022', 24, '2025-06-27 14:59:33', '2025-06-27 14:59:33', '/app/admin/demos/document/notice.html', 1, 0, 0);
INSERT INTO `wa_rules` VALUES (38, '加载组件', 'layui-icon-face-cry', 'demo2024', 24, '2025-06-27 14:59:33', '2025-06-27 14:59:33', '/app/admin/demos/document/loading.html', 1, 0, 0);
INSERT INTO `wa_rules` VALUES (39, '弹层组件', 'layui-icon-face-cry', 'demo2023', 24, '2025-06-27 14:59:33', '2025-06-27 14:59:33', '/app/admin/demos/document/popup.html', 1, 0, 0);
INSERT INTO `wa_rules` VALUES (40, '多选项卡', 'layui-icon-face-cry', 'demo60131', 24, '2025-06-27 14:59:33', '2025-06-27 14:59:33', '/app/admin/demos/document/tab.html', 1, 0, 0);
INSERT INTO `wa_rules` VALUES (41, '数据菜单', 'layui-icon-face-cry', 'demo60132', 24, '2025-06-27 14:59:33', '2025-06-27 14:59:33', '/app/admin/demos/document/menu.html', 1, 0, 0);
INSERT INTO `wa_rules` VALUES (42, '哈希加密', 'layui-icon-face-cry', 'demo2041', 24, '2025-06-27 14:59:33', '2025-06-27 14:59:33', '/app/admin/demos/document/encrypt.html', 1, 0, 0);
INSERT INTO `wa_rules` VALUES (43, '图标选择', 'layui-icon-face-cry', 'demo2042', 24, '2025-06-27 14:59:33', '2025-06-27 14:59:33', '/app/admin/demos/document/iconPicker.html', 1, 0, 0);
INSERT INTO `wa_rules` VALUES (44, '省市级联', 'layui-icon-face-cry', 'demo2043', 24, '2025-06-27 14:59:33', '2025-06-27 14:59:33', '/app/admin/demos/document/area.html', 1, 0, 0);
INSERT INTO `wa_rules` VALUES (45, '数字滚动', 'layui-icon-face-cry', 'demo2044', 24, '2025-06-27 14:59:33', '2025-06-27 14:59:33', '/app/admin/demos/document/count.html', 1, 0, 0);
INSERT INTO `wa_rules` VALUES (46, '顶部返回', 'layui-icon-face-cry', 'demo2045', 24, '2025-06-27 14:59:33', '2025-06-27 14:59:33', '/app/admin/demos/document/topBar.html', 1, 0, 0);
INSERT INTO `wa_rules` VALUES (47, '结果页面', 'layui-icon-auz', 'demo666', 18, '2025-06-27 14:59:33', '2025-06-27 14:59:33', '', 0, 0, 0);
INSERT INTO `wa_rules` VALUES (48, '成功', 'layui-icon-face-smile', 'demo667', 47, '2025-06-27 14:59:33', '2025-06-27 14:59:33', '/app/admin/demos/result/success.html', 1, 0, 0);
INSERT INTO `wa_rules` VALUES (49, '失败', 'layui-icon-face-cry', 'demo668', 47, '2025-06-27 14:59:33', '2025-06-27 14:59:33', '/app/admin/demos/result/error.html', 1, 0, 0);
INSERT INTO `wa_rules` VALUES (50, '错误页面', 'layui-icon-face-cry', 'demo-error', 18, '2025-06-27 14:59:33', '2025-06-27 14:59:33', '', 0, 0, 0);
INSERT INTO `wa_rules` VALUES (51, '403', 'layui-icon-face-smile', 'demo403', 50, '2025-06-27 14:59:33', '2025-06-27 14:59:33', '/app/admin/demos/error/403.html', 1, 0, 0);
INSERT INTO `wa_rules` VALUES (52, '404', 'layui-icon-face-cry', 'demo404', 50, '2025-06-27 14:59:33', '2025-06-27 14:59:33', '/app/admin/demos/error/404.html', 1, 0, 0);
INSERT INTO `wa_rules` VALUES (53, '500', 'layui-icon-face-cry', 'demo500', 50, '2025-06-27 14:59:33', '2025-06-27 14:59:33', '/app/admin/demos/error/500.html', 1, 0, 0);
INSERT INTO `wa_rules` VALUES (54, '系统管理', 'layui-icon-set-fill', 'demo-system', 18, '2025-06-27 14:59:33', '2025-06-27 14:59:33', '', 0, 0, 0);
INSERT INTO `wa_rules` VALUES (55, '用户管理', 'layui-icon-face-smile', 'demo601', 54, '2025-06-27 14:59:33', '2025-06-27 14:59:33', '/app/admin/demos/system/user.html', 1, 0, 0);
INSERT INTO `wa_rules` VALUES (56, '角色管理', 'layui-icon-face-cry', 'demo602', 54, '2025-06-27 14:59:33', '2025-06-27 14:59:33', '/app/admin/demos/system/role.html', 1, 0, 0);
INSERT INTO `wa_rules` VALUES (57, '权限管理', 'layui-icon-face-cry', 'demo603', 54, '2025-06-27 14:59:33', '2025-06-27 14:59:33', '/app/admin/demos/system/power.html', 1, 0, 0);
INSERT INTO `wa_rules` VALUES (58, '部门管理', 'layui-icon-face-cry', 'demo604', 54, '2025-06-27 14:59:33', '2025-06-27 14:59:33', '/app/admin/demos/system/deptment.html', 1, 0, 0);
INSERT INTO `wa_rules` VALUES (59, '行为日志', 'layui-icon-face-cry', 'demo605', 54, '2025-06-27 14:59:33', '2025-06-27 14:59:33', '/app/admin/demos/system/log.html', 1, 0, 0);
INSERT INTO `wa_rules` VALUES (60, '数据字典', 'layui-icon-face-cry', 'demo606', 54, '2025-06-27 14:59:33', '2025-06-27 14:59:33', '/app/admin/demos/system/dict.html', 1, 0, 0);
INSERT INTO `wa_rules` VALUES (61, '常用页面', 'layui-icon-template-1', 'demo-common', 18, '2025-06-27 14:59:33', '2025-06-27 14:59:33', '', 0, 0, 0);
INSERT INTO `wa_rules` VALUES (62, '空白页面', 'layui-icon-face-smile', 'demo702', 61, '2025-06-27 14:59:33', '2025-06-27 14:59:33', '/app/admin/demos/system/space.html', 1, 0, 0);
INSERT INTO `wa_rules` VALUES (63, '查看表', NULL, 'plugin\\admin\\app\\controller\\TableController@view', 2, '2025-06-27 17:00:34', '2025-06-27 17:00:34', NULL, 2, 0, 0);
INSERT INTO `wa_rules` VALUES (64, '查询表', NULL, 'plugin\\admin\\app\\controller\\TableController@show', 2, '2025-06-27 17:00:37', '2025-06-27 17:00:37', NULL, 2, 0, 0);
INSERT INTO `wa_rules` VALUES (65, '创建表', NULL, 'plugin\\admin\\app\\controller\\TableController@create', 2, '2025-06-27 17:00:37', '2025-06-27 17:00:37', NULL, 2, 0, 0);
INSERT INTO `wa_rules` VALUES (66, '修改表', NULL, 'plugin\\admin\\app\\controller\\TableController@modify', 2, '2025-06-27 17:00:37', '2025-06-27 17:00:37', NULL, 2, 0, 0);
INSERT INTO `wa_rules` VALUES (67, '一键菜单', NULL, 'plugin\\admin\\app\\controller\\TableController@crud', 2, '2025-06-27 17:00:37', '2025-06-27 17:00:37', NULL, 2, 0, 0);
INSERT INTO `wa_rules` VALUES (68, '查询记录', NULL, 'plugin\\admin\\app\\controller\\TableController@select', 2, '2025-06-27 17:00:37', '2025-06-27 17:00:37', NULL, 2, 0, 0);
INSERT INTO `wa_rules` VALUES (69, '插入记录', NULL, 'plugin\\admin\\app\\controller\\TableController@insert', 2, '2025-06-27 17:00:37', '2025-06-27 17:00:37', NULL, 2, 0, 0);
INSERT INTO `wa_rules` VALUES (70, '更新记录', NULL, 'plugin\\admin\\app\\controller\\TableController@update', 2, '2025-06-27 17:00:37', '2025-06-27 17:00:37', NULL, 2, 0, 0);
INSERT INTO `wa_rules` VALUES (71, '删除记录', NULL, 'plugin\\admin\\app\\controller\\TableController@delete', 2, '2025-06-27 17:00:37', '2025-06-27 17:00:37', NULL, 2, 0, 0);
INSERT INTO `wa_rules` VALUES (72, '删除表', NULL, 'plugin\\admin\\app\\controller\\TableController@drop', 2, '2025-06-27 17:00:37', '2025-06-27 17:00:37', NULL, 2, 0, 0);
INSERT INTO `wa_rules` VALUES (73, '表摘要', NULL, 'plugin\\admin\\app\\controller\\TableController@schema', 2, '2025-06-27 17:00:37', '2025-06-27 17:00:37', NULL, 2, 0, 0);
INSERT INTO `wa_rules` VALUES (74, '插入', NULL, 'plugin\\admin\\app\\controller\\AdminController@insert', 4, '2025-06-27 17:00:37', '2025-06-27 17:00:37', NULL, 2, 0, 0);
INSERT INTO `wa_rules` VALUES (75, '更新', NULL, 'plugin\\admin\\app\\controller\\AdminController@update', 4, '2025-06-27 17:00:37', '2025-06-27 17:00:37', NULL, 2, 0, 0);
INSERT INTO `wa_rules` VALUES (76, '删除', NULL, 'plugin\\admin\\app\\controller\\AdminController@delete', 4, '2025-06-27 17:00:37', '2025-06-27 17:00:37', NULL, 2, 0, 0);
INSERT INTO `wa_rules` VALUES (77, '插入', NULL, 'plugin\\admin\\app\\controller\\RoleController@insert', 5, '2025-06-27 17:00:37', '2025-06-27 17:00:37', NULL, 2, 0, 0);
INSERT INTO `wa_rules` VALUES (78, '更新', NULL, 'plugin\\admin\\app\\controller\\RoleController@update', 5, '2025-06-27 17:00:37', '2025-06-27 17:00:37', NULL, 2, 0, 0);
INSERT INTO `wa_rules` VALUES (79, '删除', NULL, 'plugin\\admin\\app\\controller\\RoleController@delete', 5, '2025-06-27 17:00:37', '2025-06-27 17:00:37', NULL, 2, 0, 0);
INSERT INTO `wa_rules` VALUES (80, '获取角色权限', NULL, 'plugin\\admin\\app\\controller\\RoleController@rules', 5, '2025-06-27 17:00:37', '2025-06-27 17:00:37', NULL, 2, 0, 0);
INSERT INTO `wa_rules` VALUES (81, '查询', NULL, 'plugin\\admin\\app\\controller\\RuleController@select', 6, '2025-06-27 17:00:37', '2025-06-27 17:00:37', NULL, 2, 0, 0);
INSERT INTO `wa_rules` VALUES (82, '添加', NULL, 'plugin\\admin\\app\\controller\\RuleController@insert', 6, '2025-06-27 17:00:37', '2025-06-27 17:00:37', NULL, 2, 0, 0);
INSERT INTO `wa_rules` VALUES (83, '更新', NULL, 'plugin\\admin\\app\\controller\\RuleController@update', 6, '2025-06-27 17:00:37', '2025-06-27 17:00:37', NULL, 2, 0, 0);
INSERT INTO `wa_rules` VALUES (84, '删除', NULL, 'plugin\\admin\\app\\controller\\RuleController@delete', 6, '2025-06-27 17:00:37', '2025-06-27 17:00:37', NULL, 2, 0, 0);
INSERT INTO `wa_rules` VALUES (85, '插入', NULL, 'plugin\\admin\\app\\controller\\UserController@insert', 8, '2025-06-27 17:00:37', '2025-06-27 17:00:37', NULL, 2, 0, 0);
INSERT INTO `wa_rules` VALUES (86, '更新', NULL, 'plugin\\admin\\app\\controller\\UserController@update', 8, '2025-06-27 17:00:37', '2025-06-27 17:00:37', NULL, 2, 0, 0);
INSERT INTO `wa_rules` VALUES (87, '查询', NULL, 'plugin\\admin\\app\\controller\\UserController@select', 8, '2025-06-27 17:00:37', '2025-06-27 17:00:37', NULL, 2, 0, 0);
INSERT INTO `wa_rules` VALUES (88, '删除', NULL, 'plugin\\admin\\app\\controller\\UserController@delete', 8, '2025-06-27 17:00:37', '2025-06-27 17:00:37', NULL, 2, 0, 0);
INSERT INTO `wa_rules` VALUES (89, '更新', NULL, 'plugin\\admin\\app\\controller\\AccountController@update', 10, '2025-06-27 17:00:37', '2025-06-27 17:00:37', NULL, 2, 0, 0);
INSERT INTO `wa_rules` VALUES (90, '修改密码', NULL, 'plugin\\admin\\app\\controller\\AccountController@password', 10, '2025-06-27 17:00:37', '2025-06-27 17:00:37', NULL, 2, 0, 0);
INSERT INTO `wa_rules` VALUES (91, '查询', NULL, 'plugin\\admin\\app\\controller\\AccountController@select', 10, '2025-06-27 17:00:37', '2025-06-27 17:00:37', NULL, 2, 0, 0);
INSERT INTO `wa_rules` VALUES (92, '添加', NULL, 'plugin\\admin\\app\\controller\\AccountController@insert', 10, '2025-06-27 17:00:38', '2025-06-27 17:00:38', NULL, 2, 0, 0);
INSERT INTO `wa_rules` VALUES (93, '删除', NULL, 'plugin\\admin\\app\\controller\\AccountController@delete', 10, '2025-06-27 17:00:38', '2025-06-27 17:00:38', NULL, 2, 0, 0);
INSERT INTO `wa_rules` VALUES (94, '浏览附件', NULL, 'plugin\\admin\\app\\controller\\UploadController@attachment', 11, '2025-06-27 17:00:38', '2025-06-27 17:00:38', NULL, 2, 0, 0);
INSERT INTO `wa_rules` VALUES (95, '查询附件', NULL, 'plugin\\admin\\app\\controller\\UploadController@select', 11, '2025-06-27 17:00:38', '2025-06-27 17:00:38', NULL, 2, 0, 0);
INSERT INTO `wa_rules` VALUES (96, '更新附件', NULL, 'plugin\\admin\\app\\controller\\UploadController@update', 11, '2025-06-27 17:00:38', '2025-06-27 17:00:38', NULL, 2, 0, 0);
INSERT INTO `wa_rules` VALUES (97, '添加附件', NULL, 'plugin\\admin\\app\\controller\\UploadController@insert', 11, '2025-06-27 17:00:38', '2025-06-27 17:00:38', NULL, 2, 0, 0);
INSERT INTO `wa_rules` VALUES (98, '上传文件', NULL, 'plugin\\admin\\app\\controller\\UploadController@file', 11, '2025-06-27 17:00:38', '2025-06-27 17:00:38', NULL, 2, 0, 0);
INSERT INTO `wa_rules` VALUES (99, '上传图片', NULL, 'plugin\\admin\\app\\controller\\UploadController@image', 11, '2025-06-27 17:00:38', '2025-06-27 17:00:38', NULL, 2, 0, 0);
INSERT INTO `wa_rules` VALUES (100, '上传头像', NULL, 'plugin\\admin\\app\\controller\\UploadController@avatar', 11, '2025-06-27 17:00:38', '2025-06-27 17:00:38', NULL, 2, 0, 0);
INSERT INTO `wa_rules` VALUES (101, '删除附件', NULL, 'plugin\\admin\\app\\controller\\UploadController@delete', 11, '2025-06-27 17:00:38', '2025-06-27 17:00:38', NULL, 2, 0, 0);
INSERT INTO `wa_rules` VALUES (102, '查询', NULL, 'plugin\\admin\\app\\controller\\DictController@select', 12, '2025-06-27 17:00:38', '2025-06-27 17:00:38', NULL, 2, 0, 0);
INSERT INTO `wa_rules` VALUES (103, '插入', NULL, 'plugin\\admin\\app\\controller\\DictController@insert', 12, '2025-06-27 17:00:38', '2025-06-27 17:00:38', NULL, 2, 0, 0);
INSERT INTO `wa_rules` VALUES (104, '更新', NULL, 'plugin\\admin\\app\\controller\\DictController@update', 12, '2025-06-27 17:00:38', '2025-06-27 17:00:38', NULL, 2, 0, 0);
INSERT INTO `wa_rules` VALUES (105, '删除', NULL, 'plugin\\admin\\app\\controller\\DictController@delete', 12, '2025-06-27 17:00:38', '2025-06-27 17:00:38', NULL, 2, 0, 0);
INSERT INTO `wa_rules` VALUES (106, '更改', NULL, 'plugin\\admin\\app\\controller\\ConfigController@update', 13, '2025-06-27 17:00:38', '2025-06-27 17:00:38', NULL, 2, 0, 0);
INSERT INTO `wa_rules` VALUES (107, '列表', NULL, 'plugin\\admin\\app\\controller\\PluginController@list', 15, '2025-06-27 17:00:38', '2025-06-27 17:00:38', NULL, 2, 0, 0);
INSERT INTO `wa_rules` VALUES (108, '安装', NULL, 'plugin\\admin\\app\\controller\\PluginController@install', 15, '2025-06-27 17:00:38', '2025-06-27 17:00:38', NULL, 2, 0, 0);
INSERT INTO `wa_rules` VALUES (109, '卸载', NULL, 'plugin\\admin\\app\\controller\\PluginController@uninstall', 15, '2025-06-27 17:00:38', '2025-06-27 17:00:38', NULL, 2, 0, 0);
INSERT INTO `wa_rules` VALUES (110, '支付', NULL, 'plugin\\admin\\app\\controller\\PluginController@pay', 15, '2025-06-27 17:00:38', '2025-06-27 17:00:38', NULL, 2, 0, 0);
INSERT INTO `wa_rules` VALUES (111, '登录官网', NULL, 'plugin\\admin\\app\\controller\\PluginController@login', 15, '2025-06-27 17:00:38', '2025-06-27 17:00:38', NULL, 2, 0, 0);
INSERT INTO `wa_rules` VALUES (112, '获取已安装的插件列表', NULL, 'plugin\\admin\\app\\controller\\PluginController@getInstalledPlugins', 15, '2025-06-27 17:00:38', '2025-06-27 17:00:38', NULL, 2, 0, 0);
INSERT INTO `wa_rules` VALUES (113, '表单构建', NULL, 'plugin\\admin\\app\\controller\\DevController@formBuild', 17, '2025-06-27 17:00:38', '2025-06-27 17:00:38', NULL, 2, 0, 0);
INSERT INTO `wa_rules` VALUES (115, '信息管理', 'layui-icon-fonts-code', 'word', 0, '2025-06-27 14:59:32', '2025-06-28 11:26:02', NULL, 0, 500, 0);
INSERT INTO `wa_rules` VALUES (116, '固定摄像头类型', NULL, 'plugin\\admin\\app\\controller\\DeviceController@book', 300, '2025-06-27 14:59:32', '2025-07-31 14:56:53', '/app/admin/device/book', 1, 0, 0);
INSERT INTO `wa_rules` VALUES (120, '设备记录', NULL, 'plugin\\admin\\app\\controller\\DeviceController', 7, '2025-06-27 14:59:32', '2025-06-27 14:59:32', '/app/admin/device/index', 1, 0, 0);
INSERT INTO `wa_rules` VALUES (121, '查看', NULL, 'plugin\\admin\\app\\controller\\DeviceController@select', 120, '2025-07-07 10:51:35', '2025-07-07 10:51:35', NULL, 2, 0, 0);
INSERT INTO `wa_rules` VALUES (122, '更新', NULL, 'plugin\\admin\\app\\controller\\DeviceController@update', 120, '2025-07-07 10:51:35', '2025-07-07 10:51:35', NULL, 2, 0, 0);
INSERT INTO `wa_rules` VALUES (123, '添加', NULL, 'plugin\\admin\\app\\controller\\DeviceController@insert', 120, '2025-07-07 10:51:35', '2025-07-07 10:51:35', NULL, 2, 0, 0);
INSERT INTO `wa_rules` VALUES (124, '删除', NULL, 'plugin\\admin\\app\\controller\\DeviceController@delete', 120, '2025-07-07 10:51:35', '2025-07-07 10:51:35', NULL, 2, 0, 0);
INSERT INTO `wa_rules` VALUES (127, '查看', NULL, 'plugin\\admin\\app\\controller\\DeviceController@book_ajax', 116, '2025-07-21 16:19:42', '2025-07-21 16:19:42', NULL, 2, 0, 0);
INSERT INTO `wa_rules` VALUES (137, '道路事件', NULL, 'plugin\\admin\\app\\controller\\DeviceController@mp3', 7, '2025-06-27 17:00:37', '2025-07-31 14:56:53', '/app/admin/device/mp3', 1, 0, 0);
INSERT INTO `wa_rules` VALUES (139, '查看', NULL, 'plugin\\admin\\app\\controller\\DeviceController@mp3_ajax', 137, '2025-07-21 16:19:43', '2025-07-21 16:19:43', NULL, 2, 0, 0);
INSERT INTO `wa_rules` VALUES (141, '删除', NULL, 'plugin\\admin\\app\\controller\\DeviceController@book_delete', 116, '2025-07-21 16:19:42', '2025-07-21 16:19:42', NULL, 2, 0, 0);
INSERT INTO `wa_rules` VALUES (161, '邮件设置', NULL, 'plugin\\email\\app\\admin\\controller\\SettingController', 9, '2025-12-12 09:24:02', '2025-12-12 09:24:02', '/app/email/admin/setting', 1, 0, 0);
INSERT INTO `wa_rules` VALUES (163, '超速设备反馈', NULL, 'plugin\\admin\\app\\controller\\Feedback2Controller', 7, '2025-06-27 14:59:32', '2025-06-27 14:59:32', '/app/admin/feedback2/index', 1, 0, 0);

-- ----------------------------
-- Table structure for wa_sms
-- ----------------------------
DROP TABLE IF EXISTS `wa_sms`;
CREATE TABLE `wa_sms`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `event` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '事件',
  `mobile` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '手机号',
  `code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '验证码',
  `times` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '验证次数',
  `ip` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'IP',
  `createtime` bigint(16) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '短信验证码表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of wa_sms
-- ----------------------------

-- ----------------------------
-- Table structure for wa_transfor
-- ----------------------------
DROP TABLE IF EXISTS `wa_transfor`;
CREATE TABLE `wa_transfor`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '设备编号',
  `device_id` int(10) NOT NULL DEFAULT 0 COMMENT '设备id',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '文件名',
  `file` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '文件地址',
  `size` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '文件大小',
  `transfortime` bigint(16) NOT NULL DEFAULT 0 COMMENT '精确时间',
  `created_at` datetime NOT NULL COMMENT '创建时间',
  `updated_at` datetime NOT NULL COMMENT '更新时间',
  `is_transfor` tinyint(1) NOT NULL DEFAULT 0 COMMENT '传输状态',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '传输记录' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of wa_transfor
-- ----------------------------

-- ----------------------------
-- Table structure for wa_uploads
-- ----------------------------
DROP TABLE IF EXISTS `wa_uploads`;
CREATE TABLE `wa_uploads`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
  `name` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '名称',
  `url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '文件',
  `admin_id` int(11) NULL DEFAULT NULL COMMENT '管理员',
  `file_size` int(11) NOT NULL COMMENT '文件大小',
  `mime_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'mime类型',
  `image_width` int(11) NULL DEFAULT NULL COMMENT '图片宽度',
  `image_height` int(11) NULL DEFAULT NULL COMMENT '图片高度',
  `ext` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '扩展名',
  `storage` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'local' COMMENT '存储位置',
  `created_at` date NULL DEFAULT NULL COMMENT '上传时间',
  `category` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '类别',
  `updated_at` date NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `category`(`category`) USING BTREE,
  INDEX `admin_id`(`admin_id`) USING BTREE,
  INDEX `name`(`name`) USING BTREE,
  INDEX `ext`(`ext`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '附件' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of wa_uploads
-- ----------------------------

-- ----------------------------
-- Table structure for wa_users
-- ----------------------------
DROP TABLE IF EXISTS `wa_users`;
CREATE TABLE `wa_users`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `username` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '用户名',
  `nickname` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '昵称',
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '密码',
  `sex` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '1' COMMENT '性别',
  `avatar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '头像',
  `email` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '邮箱',
  `mobile` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '手机',
  `level` tinyint(4) NOT NULL DEFAULT 0 COMMENT '等级',
  `birthday` date NULL DEFAULT NULL COMMENT '生日',
  `money` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '余额(元)',
  `score` int(11) NOT NULL DEFAULT 0 COMMENT '积分',
  `last_time` datetime NULL DEFAULT NULL COMMENT '登录时间',
  `last_ip` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '登录ip',
  `join_time` datetime NULL DEFAULT NULL COMMENT '注册时间',
  `join_ip` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '注册ip',
  `token` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'token',
  `created_at` datetime NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime NULL DEFAULT NULL COMMENT '更新时间',
  `role` int(11) NOT NULL DEFAULT 1 COMMENT '角色',
  `status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '禁用',
  `from_time` datetime NULL DEFAULT NULL,
  `end_time` datetime NULL DEFAULT NULL,
  `distance` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '距离',
  `speed` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '速度单位',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `username`(`username`) USING BTREE,
  INDEX `join_time`(`join_time`) USING BTREE,
  INDEX `mobile`(`mobile`) USING BTREE,
  INDEX `email`(`email`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 7 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '用户表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of wa_users
-- ----------------------------
INSERT INTO `wa_users` VALUES (1, 'gkQdmuyX', 'gkQdmuyX', '$2y$10$Ukaca3HjjlgBSZB.UrrnL.1ya56iYtcgdGTj/fD7R0HITYe.1Mogu', '1', '/images/avatar.png', '516651741@qq.com', NULL, 0, NULL, 0.00, 0, NULL, NULL, NULL, NULL, '787a03acb3b26f97aae6efac108ef9d5', '2026-09-16 16:03:54', NULL, 1, 1, NULL, NULL, '', '');
INSERT INTO `wa_users` VALUES (2, 'DRcMOjGm', 'DRcMOjGm', '$2y$10$0/XsAHimgDzVGD385Vg/f.q0IOlmEHlPiNKO7V2lJ87zP/fTAHxJ6', '1', '/images/avatar.png', '836883891@qq.com', NULL, 0, NULL, 0.00, 0, NULL, NULL, NULL, NULL, '5aacf7af85e59aecac1559307327682b', '2026-09-16 18:18:33', NULL, 1, 1, NULL, NULL, '', '');
INSERT INTO `wa_users` VALUES (3, 'NLVQriUy', 'NLVQriUy', '$2y$10$i/ApglTfTklhTmwW/qWUuu9DSZldypvs4z8EZ3nNWYruTSi.LA1lO', '1', '/upload/2026092018091296.jpg', '809213962@qq.com', NULL, 0, NULL, 0.00, 0, NULL, NULL, NULL, NULL, '907856b902fee4e5eba24e3fb7f18b76', '2026-09-20 18:09:07', NULL, 1, 1, NULL, NULL, '', '');
INSERT INTO `wa_users` VALUES (4, 'cFlesruf', 'cFlesruf', '$2y$10$gfrm8uUIHjO4MSIWO2itwO05ilppoXrGDbCRrKrJba.901Bd9gtTW', '1', '/upload/202609201812400.jpg', 'ddd@pugb.vip', NULL, 0, NULL, 0.00, 0, NULL, NULL, NULL, NULL, 'c786c7d479a8c1ab35b5b624ef99f8c8', '2026-09-20 18:12:36', NULL, 1, 1, NULL, NULL, '', '');
INSERT INTO `wa_users` VALUES (5, 'wUohJzpR', 'wUohJzpR', '$2y$10$nisEldz7BHi0gi3vKnnTyOdFfwHWAF4J2wpr8aa5Dgvag3yS1nddq', '1', '/upload/2026092309291883.jpg', '857057533@qq.com', NULL, 0, NULL, 0.00, 0, NULL, NULL, NULL, NULL, '3cf146cd406c3cdb9d692e90248cb1a2', '2026-09-20 19:16:06', NULL, 1, 1, NULL, NULL, 'km', 'mph');
INSERT INTO `wa_users` VALUES (6, 'qUvXSxDE', 'qUvXSxDE', '$2y$10$0Jt5EpE3.UmJZ6OhbGkre.so4.RZJjpqq6YmZmRXSRylMxx5/bWEC', '1', '/images/avatar.png', '1273210928@qq.com', NULL, 0, NULL, 0.00, 0, NULL, NULL, NULL, NULL, 'b84cd490cb1ed0adc46587892aa3056e', '2026-09-23 13:55:47', NULL, 1, 1, NULL, NULL, 'km', 'km/h');

-- ----------------------------
-- Table structure for wa_users_logs
-- ----------------------------
DROP TABLE IF EXISTS `wa_users_logs`;
CREATE TABLE `wa_users_logs`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `user_id` int(10) NULL DEFAULT NULL,
  `created_at` datetime NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime NULL DEFAULT NULL COMMENT '更新时间',
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `event` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '用户表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of wa_users_logs
-- ----------------------------

SET FOREIGN_KEY_CHECKS = 1;
