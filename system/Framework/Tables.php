<?php 

/**
* @package      DB - Tables
* @author       YoYo
* @copyright    Copyright (c) 2022, script-php.ro
* @link         https://script-php.ro
*/

namespace System\Framework;

class Tables {

    public $tables = [];
    private $db;
    private $diff = true;

    protected $registry;

	function __construct($registry) {
		$this->registry = $registry;
        $this->db = $this->registry->get('db');
        $this->util = $this->registry->get('util');
	}


    function db_schema($tables) {
        
        $debug_backtrace = debug_backtrace();
        $file = $this->util->hash($debug_backtrace[0]['file']);
        $hash = $this->util->hash(json_encode($tables));

        if (is_file(CONFIG_DIR_STORAGE . 'logs/tables-' . $file)) {
            $check = file_get_contents(CONFIG_DIR_STORAGE . 'logs/tables-' . $file);
            if($hash === $check) {
                $this->diff = false;
            } 
        }

        if($this->diff) {
            $this->actions($tables);
            file_put_contents(CONFIG_DIR_STORAGE . 'logs/tables-' . $file, $hash);
        }

	}


    function actions($tables) {

        try {
            // Structure
            foreach ($tables as $table) {
                $foreign_query = $this->db->query("SELECT * FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = '" . CONFIG_DB_DATABASE . "' AND TABLE_NAME = '" . CONFIG_DB_PREFIX . $table['name'] . "' AND CONSTRAINT_TYPE = 'FOREIGN KEY'");
                foreach ($foreign_query->rows as $foreign) {
                    $this->db->query("ALTER TABLE `" . CONFIG_DB_PREFIX . $table['name'] . "` DROP FOREIGN KEY `" . $foreign['CONSTRAINT_NAME'] . "`");
                }
            }

            foreach ($tables as $table) {
                $table_query = $this->db->query("SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = '" . CONFIG_DB_DATABASE . "' AND TABLE_NAME = '" . CONFIG_DB_PREFIX . $table['name'] . "'");
                if (!$table_query->num_rows) {
                    $this->createTable($table);
                } else {
                    $this->alterTable($table);
                }
            }

            foreach ($tables as $table) {
                if (isset($table['foreign'])) {
                    foreach ($table['foreign'] as $foreign) {
                        $this->db->query("ALTER TABLE `" . CONFIG_DB_PREFIX . $table['name'] . "` ADD FOREIGN KEY (`" . $foreign['key'] . "`) REFERENCES `" . CONFIG_DB_PREFIX . $foreign['table'] . "` (`" . $foreign['field'] . "`)");
                    }
                }
            }

        } catch (\ErrorException $exception) {
            exit($exception);
        }
    }












    # CREATE TABLE
    function createTable($table) {
        $sql = "CREATE TABLE `" . CONFIG_DB_PREFIX . $table['name'] . "` (" . "\n";

        foreach ($table['field'] as $field) {
            $sql .= "  `" . $field['name'] . "` " . $field['type'] . (!empty($field['not_null']) ? " NOT NULL" : "") . (isset($field['default']) ? " DEFAULT '" . $field['default'] . "'" : "") . (!empty($field['auto_increment']) ? " AUTO_INCREMENT" : "") . ",\n";
        }

        if (isset($table['primary'])) {
            $primary_data = [];

            foreach ($table['primary'] as $primary) {
                $primary_data[] = "`" . $primary . "`";
            }

            $sql .= " PRIMARY KEY (" . implode(",", $primary_data) . "),\n";
        }

        if (isset($table['index'])) {
            foreach ($table['index'] as $index) {
                $index_data = [];

                foreach ($index['key'] as $key) {
                    $index_data[] = "`" . $key . "`";
                }

                $sql .= " KEY `" . $index['name'] . "` (" . implode(",", $index_data) . "),\n";
            }
        }

        $sql = rtrim($sql, ",\n") . "\n";
        $sql .= ") ENGINE=" . $table['engine'] . " CHARSET=" . $table['charset'] . " COLLATE=" . $table['collate'] . ";\n";

        // pre($sql);
        $this->db->query($sql);
    }


    # ALTER TABLE
    function alterTable($table) {
        for ($i = 0; $i < count($table['field']); $i++) {
            $sql = "ALTER TABLE `" . CONFIG_DB_PREFIX . $table['name'] . "`";

            $field_query = $this->db->query("SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = '" . CONFIG_DB_DATABASE . "' AND TABLE_NAME = '" . CONFIG_DB_PREFIX . $table['name'] . "' AND COLUMN_NAME = '" . $table['field'][$i]['name'] . "'");

            

            # TODO: add CHANGE and DROP to action for columns
            #ALTER TABLE `user` CHANGE `user_idd` `user_iddd` BIGINT(22) NOT NULL;
            #ALTER TABLE `user` DROP `user_idd`;

            if (!$field_query->num_rows) {
                $sql .= " ADD";
            } else {
                $sql .= " MODIFY";
            }

            $sql .= " `" . $table['field'][$i]['name'] . "` " . $table['field'][$i]['type'];

            if (!empty($table['field'][$i]['not_null'])) {
                $sql .= " NOT NULL";
            }

            if (isset($table['field'][$i]['default'])) {
                $sql .= " DEFAULT '" . $table['field'][$i]['default'] . "'";
            }

            if (!isset($table['field'][$i - 1])) {
                $sql .= " FIRST";
            } else {
                $sql .= " AFTER `" . $table['field'][$i - 1]['name'] . "`";
            }

            $this->db->query($sql);
        }

        $keys = [];

        // Remove all primary keys and indexes
        $query = $this->db->query("SHOW INDEXES FROM `" . CONFIG_DB_PREFIX . $table['name'] . "`");

        foreach ($query->rows as $result) {
            if ($result['Key_name'] == 'PRIMARY') {
                // We need to remove the AUTO_INCREMENT
                $field_query = $this->db->query("SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = '" . CONFIG_DB_DATABASE . "' AND TABLE_NAME = '" . CONFIG_DB_PREFIX . $table['name'] . "' AND COLUMN_NAME = '" . $result['Column_name'] . "'");

                $this->db->query("ALTER TABLE `" . CONFIG_DB_PREFIX . $table['name'] . "` MODIFY `" . $result['Column_name'] . "` " . $field_query->row['COLUMN_TYPE'] . " NOT NULL");
            }

            if (!in_array($result['Key_name'], $keys)) {
                // Remove indexes below
                $keys[] = $result['Key_name'];
            }
        }

        foreach ($keys as $key) {
            if ($key == 'PRIMARY') {
                $this->db->query("ALTER TABLE `" . CONFIG_DB_PREFIX . $table['name'] . "` DROP PRIMARY KEY");
            } else {
                $this->db->query("ALTER TABLE `" . CONFIG_DB_PREFIX . $table['name'] . "` DROP INDEX `" . $key . "`");
            }
        }

        // Primary Key
        if (isset($table['primary'])) {
            $primary_data = [];

            foreach ($table['primary'] as $primary) {
                $primary_data[] = "`" . $primary . "`";
            }

            $this->db->query("ALTER TABLE `" . CONFIG_DB_PREFIX . $table['name'] . "` ADD PRIMARY KEY(" . implode(",", $primary_data) . ")");
        }

        for ($i = 0; $i < count($table['field']); $i++) {
            if (isset($table['field'][$i]['auto_increment'])) {
                $this->db->query("ALTER TABLE `" . CONFIG_DB_PREFIX . $table['name'] . "` MODIFY `" . $table['field'][$i]['name'] . "` " . $table['field'][$i]['type'] . " AUTO_INCREMENT");
            }
        }

        // Indexes
        if (isset($table['index'])) {
            foreach ($table['index'] as $index) {
                $index_data = [];

                foreach ($index['key'] as $key) {
                    $index_data[] = "`" . $key . "`";
                }

                $this->db->query("ALTER TABLE `" . CONFIG_DB_PREFIX . $table['name'] . "` ADD INDEX `" . $index['name'] . "` (" . implode(",", $index_data) . ")");
            }
        }

        // DB Engine
        if (isset($table['engine'])) {
            $this->db->query("ALTER TABLE `" . CONFIG_DB_PREFIX . $table['name'] . "` ENGINE = `" . $table['engine'] . "`");
        }

        // Charset
        if (isset($table['charset'])) {
            $sql = "ALTER TABLE `" . CONFIG_DB_PREFIX . $table['name'] . "` DEFAULT CHARACTER SET `" . $table['charset'] . "`";

            if (isset($table['collate'])) {
                $sql .= " COLLATE `" . $table['collate'] . "`";
            }

            $this->db->query($sql);
        }
    }

}





		




	
