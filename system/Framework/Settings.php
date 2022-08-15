<?php

/**
* @package      Settings
* @version      v1.0.0
* @author       YoYo
* @copyright    Copyright (c) 2022, script-php.ro
* @link         https://script-php.ro
*/

/*
To use this class, you need the next table in you database:

DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(128) NOT NULL,
  `key` varchar(128) NOT NULL,
  `value` text NOT NULL,
  `serialized` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
*/

/**
 * $this->settings->all('general');
 * $this->settings->get('general_setting');
 * $this->settings->add('general', []);
 * $this->settings->edit('general_setting', 'value');
 * $this->settings->delete('general');
 * $this->settings->deleteKey('general_setting');
 */

namespace System\Framework;
class Settings {

    private $registry;
    private $db;

    public function __construct($registry) {
        $this->registry = $registry;
        $this->db = $registry->get('db');
    }

    /**
     * Get all settings by code.
     * Example: 
     * $this->settings->all('general');
     * It will return all key & values of general settings
     */
    public function all($code) {
		$setting_data = array();
		$query = $this->db->query("SELECT * FROM `settings` WHERE `code` = :code", [':code'=>$code]);
        if($query) {
            foreach ($this->db->rows() as $result) {
                if (!$result['serialized']) {
                    $setting_data[$result['key']] = $result['value'];
                } else {
                    $setting_data[$result['key']] = json_decode($result['value'], true);
                }
            }
        }
		return $setting_data;
	}

    /**
     * Get value of a setting.
     * Example:
     * $this->settings->all('general_url');
     * It will return the value of 'general_url' key.
     */
    public function get($key) {
		$query = $this->db->query("SELECT value FROM `settings` WHERE `key` = :key", [':key'=>$key]);
        if($query) {
            if ($this->db->count()) {
                return $this->db->row()['value'];
            } else {
                return null;	
            }
        }
	}

    /** 
     * Add settings or replace existent settings.
     */
    public function add($code, $data) {
        $this->db->query("DELETE FROM `settings` WHERE `code` = :code", [':code'=>$code]);
        foreach($data as $key => $value) {
            // if (substr($key, 0, strlen($code)) == $code) {
            $serialized = '';
            if(is_array($value)) {
                $serialized = ", serialized = '1'";
                $value = json_encode($value, true);
            }
            $this->db->query("INSERT INTO `settings` SET `code` = :code, `key` = :key, `value` = :value{$serialized}", [':code'=>$code,':key'=>$key,':value'=>$value]);
            // }
        }
    }

    /** 
     * Edit value of setting key.
     */
    public function edit($key, $value) {
        if(!empty($key) && !empty($value)) {
            $serialized = "0";
            if (is_array($value)) {
                $serialized = "1";
                $value = json_encode($value);
            } 
            $this->db->query("UPDATE settings SET `value` = :value, serialized = :serialized  WHERE `key` = :key", [':value'=> $value,':serialized'=> $serialized,':key'=> $key]);
        }
    }

    /**
     * Delete all settings by code
     */
    public function delete($code) {
        $this->db->query("DELETE FROM settings WHERE `code` = :code", [':code'=>$code]);
    }

    /**
     * Delete a setting by key
     */
    public function deleteKey($key) {
        $this->db->query("DELETE FROM settings WHERE `key` = :key", [':key'=>$key]);
    }

}