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

 Date: 23/09/2026 14:20:33
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for wa_device
-- ----------------------------
DROP TABLE IF EXISTS `wa_device`;
CREATE TABLE `wa_device`  (
  `id` bigint(16) NOT NULL COMMENT 'ID',
  `country` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '国家代码。默认为 ISO3。也可用 ISO2 或国家车辆代码（如果可用）。',
  `county` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '州内的主要行政单位，通常负责地方治理和公共服务。例如美国的洛杉矶县或德国的慕尼黑县。',
  `state` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '国家内的主要行政区划，通常具有重要的自治权。例如美国的加利福尼亚州或德国的巴伐利亚州。',
  `postcode` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '邮政编码。',
  `city` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '城市名称。',
  `district` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '县或州内更小或更具体的行政区划。',
  `street` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '街道名称。',
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '摄像头类型代码',
  `speed` int(10) NULL DEFAULT NULL COMMENT '速度值。数字（5的倍数），?（未知/可变），/（不相关）。',
  `longitude` decimal(10, 6) NULL DEFAULT NULL COMMENT '经度，WGS84 坐标系，十进制度数格式，精确到6位小数（-180.000000 到 180.000000）。',
  `latitude` decimal(10, 6) NULL DEFAULT 0.000000 COMMENT '纬度，WGS84 坐标系，十进制度数格式，精确到6位小数（-90.000000 到 90.000000）。',
  `direction` int(10) NOT NULL DEFAULT 0 COMMENT '驾驶员的行驶方向（角度）。',
  `dirtype` int(10) NOT NULL DEFAULT 0 COMMENT '方向类型：0-单方向，1-双方向，2-全方向。',
  `lastupdate` datetime NULL DEFAULT NULL COMMENT '摄像头信息被更新或修改的最新日期。遵循 ISO 8601 标准',
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '摄像头当前操作状态。A-活跃, L-空壳, I-未激活, Z-已损坏。',
  `visualcheck` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '额外的质量特征。+表示已通过现场验证或 Google Street View 等方式成功进行视觉检查。-表示待视觉检查。',
  `visualcheckdate` datetime NULL DEFAULT NULL COMMENT '摄像头位置被视觉验证的最新日期',
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '编辑人员提供的附加信息字段。语言常在德语和英语之间切换。',
  `additionalcoordinate` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '附加坐标点的',
  `camera_id` int(10) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_lng_lat`(`longitude`, `latitude`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '文件管理' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for wa_device_event
-- ----------------------------
DROP TABLE IF EXISTS `wa_device_event`;
CREATE TABLE `wa_device_event`  (
  `id` bigint(16) NOT NULL COMMENT 'ID',
  `type` int(10) NOT NULL DEFAULT 0 COMMENT '事件类型，对应上文第2节的类型编号。',
  `speed` int(10) NOT NULL DEFAULT 0 COMMENT '仅 type=1 和 type=7 时有效。表示速度值，为5的倍数，无单位。其他类型为空。',
  `address` varchar(2048) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '对象，地址信息的父级对象',
  `location` varchar(1028) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '位置信息的父级对象。',
  `direction` int(10) NOT NULL DEFAULT 0 COMMENT '驾驶员的行驶方向（角度）。',
  `directiontype` tinyint(1) NOT NULL DEFAULT 0 COMMENT '方向类型：0-单方向，1-双方向。',
  `quality` tinyint(1) NOT NULL DEFAULT 0 COMMENT '质量评级：0-低，1-标准，2-良好，3-高质量。',
  `latitude` decimal(10, 6) NOT NULL DEFAULT 0.000000 COMMENT '纬度',
  `longitude` decimal(10, 6) NOT NULL DEFAULT 0.000000 COMMENT '经度',
  `createtime` int(11) NOT NULL DEFAULT 0,
  `lastconfirm` int(11) NOT NULL DEFAULT 0,
  `lastuse` int(11) NOT NULL DEFAULT 0,
  `city` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `street` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_lng_lat`(`longitude`, `latitude`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '事件记录' ROW_FORMAT = DYNAMIC;

SET FOREIGN_KEY_CHECKS = 1;
