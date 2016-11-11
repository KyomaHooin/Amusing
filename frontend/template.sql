-- MySQL dump 10.13  Distrib 5.5.49, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: dbname
-- ------------------------------------------------------
-- Server version	5.5.49-0+deb8u1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `alarm`
--

DROP TABLE IF EXISTS `alarm`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `alarm` (
  `a_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `a_desc` varchar(200) DEFAULT NULL,
  `a_email` text,
  `a_vid` int(10) unsigned DEFAULT NULL,
  `a_mid` int(10) unsigned DEFAULT NULL,
  `a_uid` int(10) unsigned DEFAULT NULL,
  `a_preset` int(10) unsigned DEFAULT '0',
  `a_class` varchar(200) DEFAULT NULL,
  `a_alarmed` char(1) DEFAULT 'N',
  `a_mailed` datetime DEFAULT NULL,
  `a_ackid` varchar(255) DEFAULT NULL,
  `a_data` mediumtext,
  `a_crit` char(1) DEFAULT 'N',
  PRIMARY KEY (`a_id`),
  KEY `a_vid` (`a_vid`,`a_mid`)
) ENGINE=MyISAM AUTO_INCREMENT=401 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `alarm_preset`
--

DROP TABLE IF EXISTS `alarm_preset`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `alarm_preset` (
  `ap_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ap_desc` varchar(200) DEFAULT NULL,
  `ap_class` varchar(200) DEFAULT NULL,
  `ap_email` text,
  `ap_data` mediumtext,
  PRIMARY KEY (`ap_id`)
) ENGINE=MyISAM AUTO_INCREMENT=14 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `alarmack`
--

DROP TABLE IF EXISTS `alarmack`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `alarmack` (
  `ac_id` varchar(255) NOT NULL,
  `ac_aid` int(10) unsigned DEFAULT NULL,
  `ac_uid` int(10) unsigned DEFAULT NULL,
  `ac_vid` int(10) unsigned DEFAULT NULL,
  `ac_mid` int(10) unsigned DEFAULT NULL,
  `ac_state` char(1) DEFAULT 'N',
  `ac_atext` text,
  `ac_text` text,
  `ac_dategen` datetime DEFAULT NULL,
  `ac_dateack` datetime DEFAULT NULL,
  PRIMARY KEY (`ac_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `alarmlog`
--

DROP TABLE IF EXISTS `alarmlog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `alarmlog` (
  `al_date` bigint(20) DEFAULT NULL,
  `al_vid` int(10) unsigned DEFAULT NULL,
  `al_mid` int(10) unsigned DEFAULT NULL,
  `al_uid` int(10) unsigned DEFAULT NULL,
  `al_edge` char(1) DEFAULT NULL,
  `al_value` double DEFAULT NULL,
  `al_class` varchar(200) DEFAULT NULL,
  `al_data` text,
  `al_crit` char(1) DEFAULT 'N',
  KEY `al_vid` (`al_vid`,`al_mid`,`al_uid`),
  KEY `al_date` (`al_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `building`
--

DROP TABLE IF EXISTS `building`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `building` (
  `b_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `b_name` varchar(256) DEFAULT NULL,
  `b_desc` text,
  `b_street` varchar(255) DEFAULT NULL,
  `b_city` varchar(255) DEFAULT NULL,
  `b_gps` varchar(255) DEFAULT NULL,
  `b_url` varchar(255) DEFAULT NULL,
  `b_img` int(10) unsigned DEFAULT '0',
  PRIMARY KEY (`b_id`)
) ENGINE=MyISAM AUTO_INCREMENT=37 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `comment`
--

DROP TABLE IF EXISTS `comment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `comment` (
  `cm_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cm_mid` int(10) unsigned DEFAULT NULL,
  `cm_date` bigint(20) DEFAULT NULL,
  `cm_uid` int(10) unsigned DEFAULT NULL,
  `cm_text` text,
  PRIMARY KEY (`cm_id`),
  KEY `cm_mid` (`cm_mid`,`cm_date`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cronlock`
--

DROP TABLE IF EXISTS `cronlock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cronlock` (
  `cl_lock` char(1) DEFAULT 'N',
  `cl_date` datetime DEFAULT NULL,
  `cl_pid` int(11) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `image`
--

DROP TABLE IF EXISTS `image`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `image` (
  `img_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `img_w` int(11) DEFAULT NULL,
  `img_h` int(11) DEFAULT NULL,
  `img_data` mediumblob,
  PRIMARY KEY (`img_id`)
) ENGINE=MyISAM AUTO_INCREMENT=79 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `locker`
--

DROP TABLE IF EXISTS `locker`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `locker` (
  `lk_id` varchar(200) NOT NULL,
  `lk_cnt` int(11) DEFAULT NULL,
  PRIMARY KEY (`lk_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `log`
--

DROP TABLE IF EXISTS `log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `log` (
  `l_date` datetime DEFAULT NULL,
  `l_uid` int(10) unsigned DEFAULT NULL,
  `l_text` text
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mapping`
--

DROP TABLE IF EXISTS `mapping`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mapping` (
  `s_lloc` varchar(32) NOT NULL,
  `s_lname` varchar(128) NOT NULL,
  `s_lid` int(10) unsigned NOT NULL,
  PRIMARY KEY (`s_lloc`,`s_lname`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `material`
--

DROP TABLE IF EXISTS `material`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `material` (
  `ma_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ma_desc` text,
  PRIMARY KEY (`ma_id`)
) ENGINE=MyISAM AUTO_INCREMENT=16 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `measuring`
--

DROP TABLE IF EXISTS `measuring`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `measuring` (
  `m_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `m_rid` int(10) unsigned DEFAULT NULL,
  `m_desc` varchar(200) DEFAULT NULL,
  `m_depart` varchar(255) DEFAULT NULL,
  `m_active` char(1) DEFAULT 'N',
  `m_img` int(10) unsigned DEFAULT NULL,
  `m_validfrom` bigint(20) DEFAULT NULL,
  `m_validto` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`m_id`)
) ENGINE=MyISAM AUTO_INCREMENT=745 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `permission`
--

DROP TABLE IF EXISTS `permission`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permission` (
  `pe_uid` int(10) unsigned NOT NULL DEFAULT '0',
  `pe_mid` int(10) unsigned NOT NULL DEFAULT '0',
  `pe_type` char(1) NOT NULL DEFAULT '',
  PRIMARY KEY (`pe_uid`,`pe_mid`,`pe_type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `plotcache`
--

DROP TABLE IF EXISTS `plotcache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `plotcache` (
  `pc_hash` varchar(32) DEFAULT NULL,
  `pc_date` datetime DEFAULT NULL,
  `pc_args` mediumtext,
  `pc_data` mediumblob,
  KEY `pc_hash` (`pc_hash`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rawvalues_2004`
--

DROP TABLE IF EXISTS `rawvalues_2004`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rawvalues_2004` (
  `rv_mid` int(10) unsigned NOT NULL DEFAULT '0',
  `rv_varid` int(10) unsigned NOT NULL DEFAULT '0',
  `rv_sid` int(10) unsigned DEFAULT NULL,
  `rv_date` bigint(20) NOT NULL DEFAULT '0',
  `rv_value` double DEFAULT NULL,
  PRIMARY KEY (`rv_mid`,`rv_varid`,`rv_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rawvalues_2005`
--

DROP TABLE IF EXISTS `rawvalues_2005`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rawvalues_2005` (
  `rv_mid` int(10) unsigned NOT NULL DEFAULT '0',
  `rv_varid` int(10) unsigned NOT NULL DEFAULT '0',
  `rv_sid` int(10) unsigned DEFAULT NULL,
  `rv_date` bigint(20) NOT NULL DEFAULT '0',
  `rv_value` double DEFAULT NULL,
  PRIMARY KEY (`rv_mid`,`rv_varid`,`rv_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rawvalues_2006`
--

DROP TABLE IF EXISTS `rawvalues_2006`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rawvalues_2006` (
  `rv_mid` int(10) unsigned NOT NULL DEFAULT '0',
  `rv_varid` int(10) unsigned NOT NULL DEFAULT '0',
  `rv_sid` int(10) unsigned DEFAULT NULL,
  `rv_date` bigint(20) NOT NULL DEFAULT '0',
  `rv_value` double DEFAULT NULL,
  PRIMARY KEY (`rv_mid`,`rv_varid`,`rv_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rawvalues_2007`
--

DROP TABLE IF EXISTS `rawvalues_2007`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rawvalues_2007` (
  `rv_mid` int(10) unsigned NOT NULL DEFAULT '0',
  `rv_varid` int(10) unsigned NOT NULL DEFAULT '0',
  `rv_sid` int(10) unsigned DEFAULT NULL,
  `rv_date` bigint(20) NOT NULL DEFAULT '0',
  `rv_value` double DEFAULT NULL,
  PRIMARY KEY (`rv_mid`,`rv_varid`,`rv_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rawvalues_2008`
--

DROP TABLE IF EXISTS `rawvalues_2008`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rawvalues_2008` (
  `rv_mid` int(10) unsigned NOT NULL DEFAULT '0',
  `rv_varid` int(10) unsigned NOT NULL DEFAULT '0',
  `rv_sid` int(10) unsigned DEFAULT NULL,
  `rv_date` bigint(20) NOT NULL DEFAULT '0',
  `rv_value` double DEFAULT NULL,
  PRIMARY KEY (`rv_mid`,`rv_varid`,`rv_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rawvalues_2009`
--

DROP TABLE IF EXISTS `rawvalues_2009`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rawvalues_2009` (
  `rv_mid` int(10) unsigned NOT NULL DEFAULT '0',
  `rv_varid` int(10) unsigned NOT NULL DEFAULT '0',
  `rv_sid` int(10) unsigned DEFAULT NULL,
  `rv_date` bigint(20) NOT NULL DEFAULT '0',
  `rv_value` double DEFAULT NULL,
  PRIMARY KEY (`rv_mid`,`rv_varid`,`rv_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rawvalues_2010`
--

DROP TABLE IF EXISTS `rawvalues_2010`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rawvalues_2010` (
  `rv_mid` int(10) unsigned NOT NULL DEFAULT '0',
  `rv_varid` int(10) unsigned NOT NULL DEFAULT '0',
  `rv_sid` int(10) unsigned DEFAULT NULL,
  `rv_date` bigint(20) NOT NULL DEFAULT '0',
  `rv_value` double DEFAULT NULL,
  PRIMARY KEY (`rv_mid`,`rv_varid`,`rv_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rawvalues_2011`
--

DROP TABLE IF EXISTS `rawvalues_2011`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rawvalues_2011` (
  `rv_mid` int(10) unsigned NOT NULL DEFAULT '0',
  `rv_varid` int(10) unsigned NOT NULL DEFAULT '0',
  `rv_sid` int(10) unsigned DEFAULT NULL,
  `rv_date` bigint(20) NOT NULL DEFAULT '0',
  `rv_value` double DEFAULT NULL,
  PRIMARY KEY (`rv_mid`,`rv_varid`,`rv_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rawvalues_2012`
--

DROP TABLE IF EXISTS `rawvalues_2012`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rawvalues_2012` (
  `rv_mid` int(10) unsigned NOT NULL DEFAULT '0',
  `rv_varid` int(10) unsigned NOT NULL DEFAULT '0',
  `rv_sid` int(10) unsigned DEFAULT NULL,
  `rv_date` bigint(20) NOT NULL DEFAULT '0',
  `rv_value` double DEFAULT NULL,
  PRIMARY KEY (`rv_mid`,`rv_varid`,`rv_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rawvalues_2013`
--

DROP TABLE IF EXISTS `rawvalues_2013`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rawvalues_2013` (
  `rv_mid` int(10) unsigned NOT NULL DEFAULT '0',
  `rv_varid` int(10) unsigned NOT NULL DEFAULT '0',
  `rv_sid` int(10) unsigned DEFAULT NULL,
  `rv_date` bigint(20) NOT NULL DEFAULT '0',
  `rv_value` double DEFAULT NULL,
  PRIMARY KEY (`rv_mid`,`rv_varid`,`rv_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rawvalues_2014`
--

DROP TABLE IF EXISTS `rawvalues_2014`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rawvalues_2014` (
  `rv_mid` int(10) unsigned NOT NULL DEFAULT '0',
  `rv_varid` int(10) unsigned NOT NULL DEFAULT '0',
  `rv_sid` int(10) unsigned DEFAULT NULL,
  `rv_date` bigint(20) NOT NULL DEFAULT '0',
  `rv_value` double DEFAULT NULL,
  PRIMARY KEY (`rv_mid`,`rv_varid`,`rv_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rawvalues_2015`
--

DROP TABLE IF EXISTS `rawvalues_2015`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rawvalues_2015` (
  `rv_mid` int(10) unsigned NOT NULL DEFAULT '0',
  `rv_varid` int(10) unsigned NOT NULL DEFAULT '0',
  `rv_sid` int(10) unsigned DEFAULT NULL,
  `rv_date` bigint(20) NOT NULL DEFAULT '0',
  `rv_value` double DEFAULT NULL,
  PRIMARY KEY (`rv_mid`,`rv_varid`,`rv_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rawvalues_2016`
--

DROP TABLE IF EXISTS `rawvalues_2016`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rawvalues_2016` (
  `rv_mid` int(10) unsigned NOT NULL DEFAULT '0',
  `rv_varid` int(10) unsigned NOT NULL DEFAULT '0',
  `rv_sid` int(10) unsigned DEFAULT NULL,
  `rv_date` bigint(20) NOT NULL DEFAULT '0',
  `rv_value` double DEFAULT NULL,
  PRIMARY KEY (`rv_mid`,`rv_varid`,`rv_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rawvalues_2017`
--

DROP TABLE IF EXISTS `rawvalues_2017`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rawvalues_2017` (
  `rv_mid` int(10) unsigned NOT NULL DEFAULT '0',
  `rv_varid` int(10) unsigned NOT NULL DEFAULT '0',
  `rv_sid` int(10) unsigned DEFAULT NULL,
  `rv_date` bigint(20) NOT NULL DEFAULT '0',
  `rv_value` double DEFAULT NULL,
  PRIMARY KEY (`rv_mid`,`rv_varid`,`rv_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `room`
--

DROP TABLE IF EXISTS `room`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `room` (
  `r_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `r_bid` int(10) unsigned DEFAULT NULL,
  `r_desc` varchar(255) DEFAULT NULL,
  `r_floor` varchar(32) DEFAULT NULL,
  `r_img` int(10) unsigned DEFAULT '0',
  `r_note` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`r_id`)
) ENGINE=MyISAM AUTO_INCREMENT=679 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `roommat`
--

DROP TABLE IF EXISTS `roommat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roommat` (
  `rm_rid` int(10) unsigned NOT NULL DEFAULT '0',
  `rm_mid` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`rm_rid`,`rm_mid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sensor`
--

DROP TABLE IF EXISTS `sensor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sensor` (
  `s_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `s_mid` int(10) unsigned DEFAULT '0',
  `s_desc` text,
  `s_serial` varchar(255) DEFAULT NULL,
  `s_timezone` varchar(100) DEFAULT 'Europe/Prague',
  `s_model` int(10) unsigned DEFAULT NULL,
  `s_type` int(10) unsigned DEFAULT NULL,
  `s_active` char(1) DEFAULT 'N',
  `s_ignoredst` char(1) DEFAULT 'N',
  `s_data` mediumtext,
  PRIMARY KEY (`s_id`),
  UNIQUE KEY `s_serial` (`s_serial`,`s_model`)
) ENGINE=MyISAM AUTO_INCREMENT=767 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sensormodel`
--

DROP TABLE IF EXISTS `sensormodel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sensormodel` (
  `sm_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sm_name` varchar(255) DEFAULT NULL,
  `sm_vendor` varchar(255) DEFAULT NULL,
  `sm_note` text,
  PRIMARY KEY (`sm_id`)
) ENGINE=MyISAM AUTO_INCREMENT=15 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sensortype`
--

DROP TABLE IF EXISTS `sensortype`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sensortype` (
  `st_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `st_desc` varchar(255) DEFAULT NULL,
  `st_class` varchar(255) DEFAULT NULL,
  `st_data` mediumtext,
  PRIMARY KEY (`st_id`),
  UNIQUE KEY `st_class` (`st_class`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `setup`
--

DROP TABLE IF EXISTS `setup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `setup` (
  `set_variable` varchar(128) NOT NULL,
  `set_value` varchar(128) DEFAULT NULL,
  PRIMARY KEY (`set_variable`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user` (
  `u_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `u_uname` varchar(32) DEFAULT NULL,
  `u_pass` varchar(64) DEFAULT NULL,
  `u_fullname` varchar(128) DEFAULT NULL,
  `u_email` varchar(128) DEFAULT NULL,
  `u_role` char(1) DEFAULT 'U',
  `u_state` char(1) DEFAULT 'Y',
  `u_pref` mediumtext,
  PRIMARY KEY (`u_id`),
  UNIQUE KEY `u_uname` (`u_uname`)
) ENGINE=MyISAM AUTO_INCREMENT=23 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `values_2004`
--

DROP TABLE IF EXISTS `values_2004`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `values_2004` (
  `v_mid` int(10) unsigned NOT NULL DEFAULT '0',
  `v_varid` int(10) unsigned NOT NULL DEFAULT '0',
  `v_date` bigint(20) NOT NULL DEFAULT '0',
  `v_value` double DEFAULT NULL,
  PRIMARY KEY (`v_mid`,`v_varid`,`v_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `values_2005`
--

DROP TABLE IF EXISTS `values_2005`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `values_2005` (
  `v_mid` int(10) unsigned NOT NULL DEFAULT '0',
  `v_varid` int(10) unsigned NOT NULL DEFAULT '0',
  `v_date` bigint(20) NOT NULL DEFAULT '0',
  `v_value` double DEFAULT NULL,
  PRIMARY KEY (`v_mid`,`v_varid`,`v_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `values_2006`
--

DROP TABLE IF EXISTS `values_2006`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `values_2006` (
  `v_mid` int(10) unsigned NOT NULL DEFAULT '0',
  `v_varid` int(10) unsigned NOT NULL DEFAULT '0',
  `v_date` bigint(20) NOT NULL DEFAULT '0',
  `v_value` double DEFAULT NULL,
  PRIMARY KEY (`v_mid`,`v_varid`,`v_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `values_2007`
--

DROP TABLE IF EXISTS `values_2007`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `values_2007` (
  `v_mid` int(10) unsigned NOT NULL DEFAULT '0',
  `v_varid` int(10) unsigned NOT NULL DEFAULT '0',
  `v_date` bigint(20) NOT NULL DEFAULT '0',
  `v_value` double DEFAULT NULL,
  PRIMARY KEY (`v_mid`,`v_varid`,`v_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `values_2008`
--

DROP TABLE IF EXISTS `values_2008`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `values_2008` (
  `v_mid` int(10) unsigned NOT NULL DEFAULT '0',
  `v_varid` int(10) unsigned NOT NULL DEFAULT '0',
  `v_date` bigint(20) NOT NULL DEFAULT '0',
  `v_value` double DEFAULT NULL,
  PRIMARY KEY (`v_mid`,`v_varid`,`v_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `values_2009`
--

DROP TABLE IF EXISTS `values_2009`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `values_2009` (
  `v_mid` int(10) unsigned NOT NULL DEFAULT '0',
  `v_varid` int(10) unsigned NOT NULL DEFAULT '0',
  `v_date` bigint(20) NOT NULL DEFAULT '0',
  `v_value` double DEFAULT NULL,
  PRIMARY KEY (`v_mid`,`v_varid`,`v_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `values_2010`
--

DROP TABLE IF EXISTS `values_2010`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `values_2010` (
  `v_mid` int(10) unsigned NOT NULL DEFAULT '0',
  `v_varid` int(10) unsigned NOT NULL DEFAULT '0',
  `v_date` bigint(20) NOT NULL DEFAULT '0',
  `v_value` double DEFAULT NULL,
  PRIMARY KEY (`v_mid`,`v_varid`,`v_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `values_2011`
--

DROP TABLE IF EXISTS `values_2011`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `values_2011` (
  `v_mid` int(10) unsigned NOT NULL DEFAULT '0',
  `v_varid` int(10) unsigned NOT NULL DEFAULT '0',
  `v_date` bigint(20) NOT NULL DEFAULT '0',
  `v_value` double DEFAULT NULL,
  PRIMARY KEY (`v_mid`,`v_varid`,`v_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `values_2012`
--

DROP TABLE IF EXISTS `values_2012`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `values_2012` (
  `v_mid` int(10) unsigned NOT NULL DEFAULT '0',
  `v_varid` int(10) unsigned NOT NULL DEFAULT '0',
  `v_date` bigint(20) NOT NULL DEFAULT '0',
  `v_value` double DEFAULT NULL,
  PRIMARY KEY (`v_mid`,`v_varid`,`v_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `values_2013`
--

DROP TABLE IF EXISTS `values_2013`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `values_2013` (
  `v_mid` int(10) unsigned NOT NULL DEFAULT '0',
  `v_varid` int(10) unsigned NOT NULL DEFAULT '0',
  `v_date` bigint(20) NOT NULL DEFAULT '0',
  `v_value` double DEFAULT NULL,
  PRIMARY KEY (`v_mid`,`v_varid`,`v_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `values_2014`
--

DROP TABLE IF EXISTS `values_2014`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `values_2014` (
  `v_mid` int(10) unsigned NOT NULL DEFAULT '0',
  `v_varid` int(10) unsigned NOT NULL DEFAULT '0',
  `v_date` bigint(20) NOT NULL DEFAULT '0',
  `v_value` double DEFAULT NULL,
  PRIMARY KEY (`v_mid`,`v_varid`,`v_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `values_2015`
--

DROP TABLE IF EXISTS `values_2015`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `values_2015` (
  `v_mid` int(10) unsigned NOT NULL DEFAULT '0',
  `v_varid` int(10) unsigned NOT NULL DEFAULT '0',
  `v_date` bigint(20) NOT NULL DEFAULT '0',
  `v_value` double DEFAULT NULL,
  PRIMARY KEY (`v_mid`,`v_varid`,`v_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `values_2016`
--

DROP TABLE IF EXISTS `values_2016`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `values_2016` (
  `v_mid` int(10) unsigned NOT NULL DEFAULT '0',
  `v_varid` int(10) unsigned NOT NULL DEFAULT '0',
  `v_date` bigint(20) NOT NULL DEFAULT '0',
  `v_value` double DEFAULT NULL,
  PRIMARY KEY (`v_mid`,`v_varid`,`v_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `values_2017`
--

DROP TABLE IF EXISTS `values_2017`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `values_2017` (
  `v_mid` int(10) unsigned NOT NULL DEFAULT '0',
  `v_varid` int(10) unsigned NOT NULL DEFAULT '0',
  `v_date` bigint(20) NOT NULL DEFAULT '0',
  `v_value` double DEFAULT NULL,
  PRIMARY KEY (`v_mid`,`v_varid`,`v_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `valuesblob_2004`
--

DROP TABLE IF EXISTS `valuesblob_2004`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `valuesblob_2004` (
  `vb_mid` int(10) unsigned NOT NULL DEFAULT '0',
  `vb_varid` int(10) unsigned NOT NULL DEFAULT '0',
  `vb_date` bigint(20) NOT NULL DEFAULT '0',
  `vb_value` mediumblob,
  PRIMARY KEY (`vb_mid`,`vb_varid`,`vb_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `valuesblob_2005`
--

DROP TABLE IF EXISTS `valuesblob_2005`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `valuesblob_2005` (
  `vb_mid` int(10) unsigned NOT NULL DEFAULT '0',
  `vb_varid` int(10) unsigned NOT NULL DEFAULT '0',
  `vb_date` bigint(20) NOT NULL DEFAULT '0',
  `vb_value` mediumblob,
  PRIMARY KEY (`vb_mid`,`vb_varid`,`vb_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `valuesblob_2006`
--

DROP TABLE IF EXISTS `valuesblob_2006`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `valuesblob_2006` (
  `vb_mid` int(10) unsigned NOT NULL DEFAULT '0',
  `vb_varid` int(10) unsigned NOT NULL DEFAULT '0',
  `vb_date` bigint(20) NOT NULL DEFAULT '0',
  `vb_value` mediumblob,
  PRIMARY KEY (`vb_mid`,`vb_varid`,`vb_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `valuesblob_2007`
--

DROP TABLE IF EXISTS `valuesblob_2007`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `valuesblob_2007` (
  `vb_mid` int(10) unsigned NOT NULL DEFAULT '0',
  `vb_varid` int(10) unsigned NOT NULL DEFAULT '0',
  `vb_date` bigint(20) NOT NULL DEFAULT '0',
  `vb_value` mediumblob,
  PRIMARY KEY (`vb_mid`,`vb_varid`,`vb_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `valuesblob_2008`
--

DROP TABLE IF EXISTS `valuesblob_2008`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `valuesblob_2008` (
  `vb_mid` int(10) unsigned NOT NULL DEFAULT '0',
  `vb_varid` int(10) unsigned NOT NULL DEFAULT '0',
  `vb_date` bigint(20) NOT NULL DEFAULT '0',
  `vb_value` mediumblob,
  PRIMARY KEY (`vb_mid`,`vb_varid`,`vb_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `valuesblob_2009`
--

DROP TABLE IF EXISTS `valuesblob_2009`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `valuesblob_2009` (
  `vb_mid` int(10) unsigned NOT NULL DEFAULT '0',
  `vb_varid` int(10) unsigned NOT NULL DEFAULT '0',
  `vb_date` bigint(20) NOT NULL DEFAULT '0',
  `vb_value` mediumblob,
  PRIMARY KEY (`vb_mid`,`vb_varid`,`vb_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `valuesblob_2010`
--

DROP TABLE IF EXISTS `valuesblob_2010`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `valuesblob_2010` (
  `vb_mid` int(10) unsigned NOT NULL DEFAULT '0',
  `vb_varid` int(10) unsigned NOT NULL DEFAULT '0',
  `vb_date` bigint(20) NOT NULL DEFAULT '0',
  `vb_value` mediumblob,
  PRIMARY KEY (`vb_mid`,`vb_varid`,`vb_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `valuesblob_2011`
--

DROP TABLE IF EXISTS `valuesblob_2011`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `valuesblob_2011` (
  `vb_mid` int(10) unsigned NOT NULL DEFAULT '0',
  `vb_varid` int(10) unsigned NOT NULL DEFAULT '0',
  `vb_date` bigint(20) NOT NULL DEFAULT '0',
  `vb_value` mediumblob,
  PRIMARY KEY (`vb_mid`,`vb_varid`,`vb_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `valuesblob_2012`
--

DROP TABLE IF EXISTS `valuesblob_2012`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `valuesblob_2012` (
  `vb_mid` int(10) unsigned NOT NULL DEFAULT '0',
  `vb_varid` int(10) unsigned NOT NULL DEFAULT '0',
  `vb_date` bigint(20) NOT NULL DEFAULT '0',
  `vb_value` mediumblob,
  PRIMARY KEY (`vb_mid`,`vb_varid`,`vb_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `valuesblob_2013`
--

DROP TABLE IF EXISTS `valuesblob_2013`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `valuesblob_2013` (
  `vb_mid` int(10) unsigned NOT NULL DEFAULT '0',
  `vb_varid` int(10) unsigned NOT NULL DEFAULT '0',
  `vb_date` bigint(20) NOT NULL DEFAULT '0',
  `vb_value` mediumblob,
  PRIMARY KEY (`vb_mid`,`vb_varid`,`vb_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `valuesblob_2014`
--

DROP TABLE IF EXISTS `valuesblob_2014`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `valuesblob_2014` (
  `vb_mid` int(10) unsigned NOT NULL DEFAULT '0',
  `vb_varid` int(10) unsigned NOT NULL DEFAULT '0',
  `vb_date` bigint(20) NOT NULL DEFAULT '0',
  `vb_value` mediumblob,
  PRIMARY KEY (`vb_mid`,`vb_varid`,`vb_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `valuesblob_2015`
--

DROP TABLE IF EXISTS `valuesblob_2015`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `valuesblob_2015` (
  `vb_mid` int(10) unsigned NOT NULL DEFAULT '0',
  `vb_varid` int(10) unsigned NOT NULL DEFAULT '0',
  `vb_date` bigint(20) NOT NULL DEFAULT '0',
  `vb_value` mediumblob,
  PRIMARY KEY (`vb_mid`,`vb_varid`,`vb_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `valuesblob_2016`
--

DROP TABLE IF EXISTS `valuesblob_2016`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `valuesblob_2016` (
  `vb_mid` int(10) unsigned NOT NULL DEFAULT '0',
  `vb_varid` int(10) unsigned NOT NULL DEFAULT '0',
  `vb_date` bigint(20) NOT NULL DEFAULT '0',
  `vb_value` mediumblob,
  PRIMARY KEY (`vb_mid`,`vb_varid`,`vb_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `valuesblob_2017`
--

DROP TABLE IF EXISTS `valuesblob_2017`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `valuesblob_2017` (
  `vb_mid` int(10) unsigned NOT NULL DEFAULT '0',
  `vb_varid` int(10) unsigned NOT NULL DEFAULT '0',
  `vb_date` bigint(20) NOT NULL DEFAULT '0',
  `vb_value` mediumblob,
  PRIMARY KEY (`vb_mid`,`vb_varid`,`vb_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `varcodes`
--

DROP TABLE IF EXISTS `varcodes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `varcodes` (
  `vc_text` varchar(255) NOT NULL,
  `vc_expperiod` int(11) DEFAULT '3600',
  `vc_bin` char(1) DEFAULT 'N',
  PRIMARY KEY (`vc_text`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `variable`
--

DROP TABLE IF EXISTS `variable`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `variable` (
  `var_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `var_unit` varchar(32) DEFAULT NULL,
  `var_desc` varchar(255) DEFAULT NULL,
  `var_code` varchar(255) DEFAULT NULL,
  `var_default` char(1) DEFAULT 'N',
  `var_plotdata` text,
  `var_left` char(1) DEFAULT '0',
  PRIMARY KEY (`var_id`),
  UNIQUE KEY `var_code` (`var_code`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `varmeascache`
--

DROP TABLE IF EXISTS `varmeascache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `varmeascache` (
  `vmc_mid` int(10) unsigned NOT NULL DEFAULT '0',
  `vmc_varid` int(10) unsigned NOT NULL DEFAULT '0',
  `vmc_mintime` bigint(20) DEFAULT '0',
  `vmc_maxtime` bigint(20) DEFAULT '0',
  `vmc_lastrawtime` bigint(20) DEFAULT '0',
  `vmc_lastrawvalue` double DEFAULT NULL,
  PRIMARY KEY (`vmc_mid`,`vmc_varid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2016-11-01  3:25:01
