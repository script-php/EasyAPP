<?php

/**
* @package      Library preference
* @version      v1.0.0
* @author       YoYo
* @copyright    Copyright (c) 2022, script-php.ro
* @link         https://script-php.ro
*/

/**
 * $this->library_preference->all('general');
 * $this->library_preference->get('general_setting');
 * $this->library_preference->add('general', []);
 * $this->library_preference->edit('general_setting', 'value');
 * $this->library_preference->delete('general');
 * $this->library_preference->deleteKey('general_setting');
 */

class LibraryPreference extends Library {

    /**
     * Get all preference by code.
     * Example: 
     * $this->library_preference->all('general');
     * It will return all key & values of general preference
     */
    public function all($code) {
		$setting_data = array();
		$query = $this->db->query("SELECT * FROM `preference` WHERE `code` = :code", [':code'=>$code]);
        if($query->num_rows) {
            foreach ($query->rows as $result) {
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
     * $this->library_preference->get('general_url');
     * It will return the value of 'general_url' key.
     */
    public function get($key) {
		$query = $this->db->query("SELECT value FROM `preference` WHERE `key` = :key", [':key'=>$key]);
        if ($query->num_rows) {
            return $query->row['value'];
        } else {
            return [];	
        }
	}

    /** 
     * Add preference or replace existent preference.
     */
    public function add($code, $data) {
        $this->db->query("DELETE FROM `preference` WHERE `code` = :code", [':code'=>$code]);
        foreach($data as $key => $value) {
            
            if (substr($key, 0, strlen($code)) == $code) {
                $serialized = '';
                if(is_array($value)) {
                    $serialized = ", serialized = '1'";
                    $value = json_encode($value, true);
                }
                else { 
                    $serialized = ", serialized = '0'"; 
                }
                $this->db->query("INSERT INTO `preference` SET `code` = :code, `key` = :key, `value` = :value{$serialized}", [':code'=>$code,':key'=>$key,':value'=>$value]);
            }
        }
    }

    /**
     * Delete all preference by code
     */
    public function delete($code) {
        $this->db->query("DELETE FROM preference WHERE `code` = :code", [':code'=>$code]);
    }

    /**
     * Delete a setting by key
     */
    public function deleteKey($key) {
        $this->db->query("DELETE FROM preference WHERE `key` = :key", [':key'=>$key]);
    }

}