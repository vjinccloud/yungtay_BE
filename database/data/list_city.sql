/*
 Navicat Premium Data Transfer

 Source Server         : 安根
 Source Server Type    : MySQL
 Source Server Version : 50650
 Source Host           : 103.17.9.152:3306
 Source Schema         : essential_db

 Target Server Type    : MySQL
 Target Server Version : 50650
 File Encoding         : 65001

 Date: 19/08/2025 12:56:34
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for list_city
-- ----------------------------
DROP TABLE IF EXISTS `list_city`;
CREATE TABLE `list_city`  (
  `sn` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT,
  `country_sn` int(11) NOT NULL,
  `title` varchar(120) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  PRIMARY KEY (`sn`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 26 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = COMPACT;

-- ----------------------------
-- Records of list_city
-- ----------------------------
INSERT INTO `list_city` VALUES (1, 1, '臺北市');
INSERT INTO `list_city` VALUES (2, 1, '基隆市');
INSERT INTO `list_city` VALUES (3, 1, '新北市');
INSERT INTO `list_city` VALUES (5, 1, '宜蘭縣');
INSERT INTO `list_city` VALUES (7, 1, '新竹市');
INSERT INTO `list_city` VALUES (8, 1, '新竹縣');
INSERT INTO `list_city` VALUES (9, 1, '桃園市');
INSERT INTO `list_city` VALUES (10, 1, '苗栗縣');
INSERT INTO `list_city` VALUES (11, 1, '臺中市');
INSERT INTO `list_city` VALUES (12, 1, '彰化縣');
INSERT INTO `list_city` VALUES (13, 1, '南投縣');
INSERT INTO `list_city` VALUES (14, 1, '嘉義市');
INSERT INTO `list_city` VALUES (15, 1, '嘉義縣');
INSERT INTO `list_city` VALUES (16, 1, '雲林縣');
INSERT INTO `list_city` VALUES (17, 1, '臺南市');
INSERT INTO `list_city` VALUES (18, 1, '高雄市');
INSERT INTO `list_city` VALUES (20, 1, '澎湖縣');
INSERT INTO `list_city` VALUES (21, 1, '金門縣');
INSERT INTO `list_city` VALUES (22, 1, '屏東縣');
INSERT INTO `list_city` VALUES (23, 1, '臺東縣');
INSERT INTO `list_city` VALUES (24, 1, '花蓮縣');
INSERT INTO `list_city` VALUES (25, 1, '連江縣');

SET FOREIGN_KEY_CHECKS = 1;
