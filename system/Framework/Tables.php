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

    private $primary_use = 0;
    private $after_use = '';
    private $table_use = '';
	private $column_use = '';

    protected $registry;

	function __construct($registry) {
		$this->registry = $registry;
        $this->db = $this->registry->get('db');
        $this->util = $this->registry->get('util');
	}

    function create(array $tables = []) {

        $tables = array_merge($this->tables, $tables);

        // pre($tables,1);
        
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

            foreach ($tables as $table) {
                if (isset($table['foreign'])) {
                    foreach ($table['foreign'] as $foreign) {

                        if(!isset($tables[$table['name']])) {
                            pre('Table "' . $table['name'] . '" doesnt exists! Please check and try again',1);
                        }

                        if(!isset($tables[$foreign['table']])) {
                            pre('Table "' . $foreign['table'] . '" doesnt exists! Please check try again',1);
                        }

                        if(!isset($tables[$table['name']]['column'][$foreign['key']])) {
                            pre('Table "' . $table['name'] . '" doesnt have the column "'.$foreign['key'].'"! Please check and try again',1);
                        }
                        else if(!isset($tables[$foreign['table']]['column'][$foreign['column']])) {
                            pre('Table "' . $foreign['table'] . '" doesnt have the column "'.$foreign['column'].'"! Please check and try again',1);
                        }
                        else {

                            $key = strtolower($tables[$table['name']]['column'][$foreign['key']]['type']);
                            $column = strtolower($tables[$foreign['table']]['column'][$foreign['column']]['type']);

                            if($key !== $column) {
                                pre('The column "'.$foreign['table'].'('.$foreign['column'].')" doesnt have same type with the selected foreign key "'.$foreign['key'].'" ! Please check and try again',1);
                            }

                        }

                    }
                }
            }

            $this->actions($tables);
            file_put_contents(CONFIG_DIR_STORAGE . 'logs/tables-' . $file, $hash);
        }

	}


    function actions($tables) {

        try {

            foreach ($tables as $table) {
                $foreign_query = $this->db->query("SELECT * FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = '" . CONFIG_DB_DATABASE . "' AND TABLE_NAME = '" . CONFIG_DB_PREFIX . $table['name'] . "' AND CONSTRAINT_TYPE = 'FOREIGN KEY'");
                foreach ($foreign_query->rows as $foreign) {
                    $this->db->query("ALTER TABLE `" . CONFIG_DB_PREFIX . $table['name'] . "` DROP FOREIGN KEY `" . $foreign['CONSTRAINT_NAME'] . "`");
                    pre("ALTER TABLE `" . CONFIG_DB_PREFIX . $table['name'] . "` DROP FOREIGN KEY `" . $foreign['CONSTRAINT_NAME'] . "`");
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

                        $cascade = ($foreign['cascade'] ? " ON DELETE CASCADE " : "");

                        $this->db->query("ALTER TABLE `" . CONFIG_DB_PREFIX . $table['name'] . "` ADD FOREIGN KEY (`" . $foreign['key'] . "`) REFERENCES `" . CONFIG_DB_PREFIX . $foreign['table'] . "` (`" . $foreign['column'] . "`)" . $cascade);

                        pre("ALTER TABLE `" . CONFIG_DB_PREFIX . $table['name'] . "` ADD FOREIGN KEY (`" . $foreign['key'] . "`) REFERENCES `" . CONFIG_DB_PREFIX . $foreign['table'] . "` (`" . $foreign['column'] . "`)" . $cascade);
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

        foreach ($table['column'] as $column) {

            if(!isset($column['delete'])) {
                $not_null = (!empty($column['not_null']) ? " NOT NULL" : "");
                $default = (isset($column['default']) ? " DEFAULT " . $column['default'] . "" : "");
                $auto_increment = (!empty($column['auto_increment']) ? " AUTO_INCREMENT" : "");

                $name = $column['name'];
                if(isset($column['change']) && !empty($column['change'])) {
                    $name = $column['change'];
                }

                $sql .= "  `" . $name . "` " . $column['type'] . $not_null . $default . $auto_increment . ",\n";
            }
            
        }

        # PRIMARY
        if (isset($table['primary'])) {
            $sql .= " PRIMARY KEY (" . $table['primary'] . "),\n";
        }

        # FULLTEXT
        if (isset($table['fulltext'])) {
            $fulltext_data = [];
            foreach ($table['fulltext'] as $fulltext) {
                
                if(is_array($fulltext)) {
                    $index = [];
                    foreach($fulltext as $fulltext) {
                        $index[] = "`" . $fulltext . "`";
                    }
                    $fulltext = implode(",", $index);
                }
                $fulltext_data[] = " FULLTEXT INDEX (" . $fulltext . "),\n";
            }

            $sql .= implode($fulltext_data);
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

        pre("=== CREATE TABLE ===");
        pre($sql);
        $this->db->query($sql);
    }


    # ALTER TABLE
    function alterTable($table) {
    
        foreach($table['column'] as $key => $value) {

            $sql = "ALTER TABLE `" . CONFIG_DB_PREFIX . $table['name'] . "`";

            $column_query = $this->db->query("SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = '" . CONFIG_DB_DATABASE . "' AND TABLE_NAME = '" . CONFIG_DB_PREFIX . $table['name'] . "' AND COLUMN_NAME = '" . $value['name'] . "'");

            # TODO: add CHANGE and DROP to action for columns
            #ALTER TABLE `user` CHANGE `user_idd` `user_iddd` BIGINT(22) NOT NULL;

            if(isset($value['delete'])) {
                $sql .= " DROP";
                $sql .= " `" . $value['name'] . "`";
            }
            else {

                $change = '';
                if (!$column_query->num_rows) {
                    $sql .= " ADD";
                } 
                else if(isset($value['change'])) {
                    $sql .= " CHANGE";
                    $change = "`" . $value['change'] . "`";
                }
                else {
                    $sql .= " MODIFY";
                }
    
                $sql .= " `" . $value['name'] . "` " . $change . " ". $value['type'];
    
                if (!empty($value['not_null'])) {
                    $sql .= " NOT NULL";
                }
    
                if (isset($value['default'])) {
                    $sql .= " DEFAULT " . $value['default'] . "";
                }
    
                if (isset($value['after'])) {
                    $sql .= " AFTER `" . $value['after'] . "`";
                } else if(isset($value['first'])) {
                    $sql .= " FIRST";
                }
            }

            pre("=== ALTER TABLE ===");
            pre($sql);
            $this->db->query($sql);
        } ##########

        $keys = [];

        // Remove all primary keys and indexes
        $query = $this->db->query("SHOW INDEXES FROM `" . CONFIG_DB_PREFIX . $table['name'] . "`");

        foreach ($query->rows as $result) {
            if ($result['Key_name'] == 'PRIMARY') {
                // We need to remove the AUTO_INCREMENT
                $column_query = $this->db->query("SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = '" . CONFIG_DB_DATABASE . "' AND TABLE_NAME = '" . CONFIG_DB_PREFIX . $table['name'] . "' AND COLUMN_NAME = '" . $result['Column_name'] . "'");

                $this->db->query("ALTER TABLE `" . CONFIG_DB_PREFIX . $table['name'] . "` MODIFY `" . $result['Column_name'] . "` " . $column_query->row['COLUMN_TYPE'] . " NOT NULL");
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
            $this->db->query("ALTER TABLE `" . CONFIG_DB_PREFIX . $table['name'] . "` ADD PRIMARY KEY(" . $table['primary'] . ")");
        }

        // Fulltext Key
        if (isset($table['fulltext'])) {
            $fulltext_data = [];
            foreach ($table['fulltext'] as $fulltext) {
                if(is_array($fulltext)) {
                    foreach($fulltext as $index) {
                        $fulltext_data[] = "`" . $index . "`";
                    }
                }
                else {
                    $this->db->query("ALTER TABLE `" . CONFIG_DB_PREFIX . $table['name'] . "` ADD FULLTEXT INDEX(" . $fulltext . ")");
                }
            }
            if(!empty($fulltext_data)) {
                $this->db->query("ALTER TABLE `" . CONFIG_DB_PREFIX . $table['name'] . "` ADD FULLTEXT INDEX(" . implode(",", $fulltext_data) . ")");
            }
        }

        foreach($table['column'] as $column) {
            if (isset($column['auto_increment'])) {
                $this->db->query("ALTER TABLE `" . CONFIG_DB_PREFIX . $table['name'] . "` MODIFY `" . $column['name'] . "` " . $column['type'] . " AUTO_INCREMENT");
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

            pre($sql);
            $this->db->query($sql);
        }
    }

    function table($table) {
		$this->table_use = $table;
		$this->tables[$this->table_use] = [
			'name' => $this->table_use,
			'column' => [],
			'engine' => 'default InnoDB',
			'charset' => 'default utf8mb4',
			'collate' => 'default utf8mb4_unicode_ci',
		];
		return $this;
	}
	
	function column($name, string $change = NULL) {
		$this->column_use = $name;
		$this->tables[$this->table_use]['column'][$this->column_use]['name'] = $name;
        if(!empty($change)) {
            $this->tables[$this->table_use]['column'][$this->column_use]['change'] = $change;
        }
		return $this;
	}
	
	function type($str) {
		$this->tables[$this->table_use]['column'][$this->column_use]['type'] = $str;
		return $this;
	}

    function after($str) {
		$this->tables[$this->table_use]['column'][$this->column_use]['after'] = $str;
		return $this;
	}
	
	function auto_increment($bool) {
		$this->tables[$this->table_use]['column'][$this->column_use]['auto_increment'] = $bool;
		return $this;
	}

    function default($str) {
		$this->tables[$this->table_use]['column'][$this->column_use]['default'] = $str;
		return $this;
	}

    function primary($str) {
        // $this->tables[$this->table_use]['primary'] = [$str];
        $this->tables[$this->table_use]['primary'] = $str;
		return $this;
	}

    function foreign($key, $table, $column, bool $cascade = false) {
        $this->tables[$this->table_use]['foreign'][] = [
            'key' => $key,
            'table' => $table,
            'column' => $column,
            'cascade' => $cascade
        ];
		return $this;
	}
	
	function not_null($bool) {
		$this->tables[$this->table_use]['column'][$this->column_use]['not_null'] = $bool;
		return $this;
	}
	
	function engine($str) {
		$this->tables[$this->table_use]['engine'] = $str;
		return $this;
	}
	
	function charset($str) {
		$this->tables[$this->table_use]['charset'] = $str;
		return $this;
	}
	
	function collate($str) {
		$this->tables[$this->table_use]['collate'] = $str;
		return $this;
	}

    function delete() {
        $this->tables[$this->table_use]['column'][$this->column_use]['delete'] = true;
        return $this;
    }

    function index(string $name, array $key = []) {
        $this->tables[$this->table_use]['index'][] = [
            'name' => $name,
            'key' => $key,
        ];
		return $this;
    }

    // TODO
    function edit($str) {
        return $this;
    }

}