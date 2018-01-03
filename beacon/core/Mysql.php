<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/13
 * Time: 14:03
 */

namespace beacon {

    use \PDO as PDO;
    use \PDOException as PDOException;
    use \Exception as Exception;

    class SqlSection
    {
        public $sql = null;
        public $args = null;

        public function __construct(string $sql, $args = null)
        {
            $this->sql = $sql;
            $this->args = $args;
        }

        public function format()
        {
            return Mysql::format($this->sql, $this->args);
        }
    }


    class Mysql
    {
        /**
         * @var \PDO|null
         */
        private $conn = null;


        private $transactionCounter = 0;
        private $host = '127.0.0.1', $port = 3306, $name = '', $user = '', $pass = '', $prefix = '';
        private $retry = 0;


        public function __construct($host, $port = 3306, $name = '', $user = '', $pass = '', $prefix = '')
        {
            $this->host = $host;
            $this->port = $port;
            $this->name = $name;
            $this->user = $user;
            $this->pass = $pass;
            $this->prefix = $prefix;
        }

        public function init()
        {

            if ($this->conn === null) {
                /*
                $host = Config::get('db.db_host', '127.0.0.1');
                $port = Config::get('db.db_port', 3306);
                $name = Config::get('db.db_name', '');
                $user = Config::get('db.db_user', '');
                $pass = Config::get('db.db_pwd', '');
                $prefix = Config::get('db.db_prefix', 'sl_');
                */
                $this->transactionCounter = 0;
                if (!empty($this->name)) {
                    $link = 'mysql:host=' . $this->host . ';port=' . $this->port . ';dbname=' . $this->name;
                } else {
                    $link = 'mysql:host=' . $this->host . ';port=' . $this->port . ';';
                }
                try {
                    $this->conn = new PDO($link, $this->user, $this->pass, [PDO::ATTR_PERSISTENT => true, PDO::ATTR_TIMEOUT => 120, PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"]);
                } catch (PDOException $exc) {
                    throw $exc;
                }
            }
            return $this;
        }

        public function __destruct()
        {
            $this->conn = null;
        }

        public function beginTransaction()
        {
            $this->init();
            try {
                if (!$this->transactionCounter++) {
                    return $this->conn->beginTransaction();
                }
                $this->conn->exec('SAVEPOINT trans' . $this->transactionCounter);
                return $this->transactionCounter >= 0;
            } catch (\ErrorException $exception) {
                throw $exception;
            } catch (\PDOException $exception) {
                throw new Exception("执行语句错误 beginTransaction");
            }
        }

        public function commit()
        {
            $this->init();
            try {
                if (!--$this->transactionCounter) {
                    return $this->conn->commit();
                }
                return $this->transactionCounter >= 0;
            } catch (\ErrorException $exception) {
                throw $exception;
            } catch (\PDOException $exception) {
                throw new Exception("执行语句错误 commit");
            }
        }

        public function rollBack()
        {
            $this->init();
            try {
                if (--$this->transactionCounter) {
                    $this->exec('ROLLBACK TO trans' . ($this->transactionCounter + 1));
                    return true;
                }
                return $this->conn->rollBack();
            } catch (\ErrorException $exception) {
                throw $exception;
            } catch (\PDOException $exception) {
                throw new Exception("执行语句错误 rollBack");
            }
        }

        public function exec(string $sql)
        {
            $this->init();
            $esql = str_replace('@pf_', $this->prefix, $sql);
            try {
                $data = $this->conn->exec($esql);
                $this->retry = 0;
                return $data;
            } catch (\ErrorException $exception) {
                $temp = $exception->getMessage();
                if ($this->retry < 3 && preg_match('@MySQL\s+server\s+has\s+gone\s+away@i', $temp)) {
                    $this->conn = null;
                    $this->retry++;
                    $this->init();
                    return $this->exec($sql);
                }
                throw $exception;
            } catch (\PDOException $exception) {
                throw new Exception("执行语句错误 exec");
            }
        }

        public function lastInsertId($name = null)
        {
            $this->init();
            try {
                $data = $this->conn->lastInsertId($name);
                $this->retry = 0;
                return $data;
            } catch (\ErrorException $exception) {
                $temp = $exception->getMessage();
                if ($this->retry < 3 && preg_match('@MySQL\s+server\s+has\s+gone\s+away@i', $temp)) {
                    $this->conn = null;
                    $this->retry++;
                    $this->init();
                    return $this->lastInsertId($name);
                }
                throw $exception;
            } catch (\PDOException $exception) {
                throw new Exception("执行语句错误 lastInsertId");
            }
        }

        public function execute(string $sql, $args = null)
        {
            $this->init();
            $esql = str_replace('@pf_', $this->prefix, $sql);
            $eargs = $args;
            if ($eargs !== null && !is_array($eargs)) {
                $eargs = [$eargs];
            }
            if ($eargs !== null && preg_match('@\?@', $esql) && preg_match('@:(\w+)@', $esql)) {
                $index = 0;
                $esql = preg_replace_callback('@\?@', function ($match) use (&$eargs, &$index) {
                    $key = ':beacon_temp_index_' . $index;
                    if (!isset($eargs[$index])) {
                        throw new Exception("参数不足，" . print_r($eargs, true));
                    }
                    $eargs[$key] = $eargs[$index];
                    unset($eargs[$index]);
                    $index++;
                    return $key;
                }, $esql);
            }
            try {
                $sth = $this->conn->prepare($esql);
                $ret = $sth->execute($eargs);
                if ($ret === FALSE) {
                    $str = print_r($sth->errorInfo(), true);
                    throw new Exception("执行语句错误 execute\n{$str}");
                }
                $this->retry = 0;
                return $sth;
            } catch (\PDOException $exception) {
                throw new Exception("执行语句错误 execute");
            } catch (\ErrorException $exception) {
                $temp = $exception->getMessage();
                if ($this->retry < 3 && preg_match('@MySQL\s+server\s+has\s+gone\s+away@i', $temp)) {
                    $this->conn = null;
                    $this->retry++;
                    $this->init();
                    return $this->execute($sql, $args);
                }
                throw $exception;
            } catch (\Exception $exception) {
                throw $exception;
            }
        }

        /**
         * @param string $sql
         * @param null $args
         * @param null $fetch_style
         * @param null $fetch_argument
         * @param array|null $ctor_args
         * @return array
         */
        public function getList(string $sql, $args = null, $fetch_style = null, $fetch_argument = null, array $ctor_args = null)
        {
            if ($fetch_style === null) {
                $fetch_style = PDO::FETCH_ASSOC;
            }
            $stm = $this->execute($sql, $args);

            if ($fetch_style !== null && $fetch_argument !== null && $ctor_args !== null) {
                $rows = $stm->fetchAll($fetch_style, $fetch_argument, $ctor_args);
            } elseif ($fetch_style !== null && $fetch_argument !== null) {
                $rows = $stm->fetchAll($fetch_style, $fetch_argument);
            } elseif ($fetch_style !== null) {
                $rows = $stm->fetchAll($fetch_style);
            } else {
                $rows = $stm->fetchAll();
            }
            $stm->closeCursor();
            return $rows;
        }

        /**
         * @param string $sql
         * @param null $args
         * @param null $fetch_style
         * @param null $cursor_orientation
         * @param int $cursor_offset
         * @return mixed|null
         */
        public function getRow(string $sql, $args = null, $fetch_style = null, $cursor_orientation = null, $cursor_offset = 0)
        {
            if ($fetch_style === null) {
                $fetch_style = PDO::FETCH_ASSOC;
            }
            $stm = $this->execute($sql, $args);
            $row = $stm->fetch($fetch_style, $cursor_orientation, $cursor_offset);
            $stm->closeCursor();
            return $row === false ? null : $row;
        }

        /**
         * 获得单个字段内容
         * @param string $sql
         * @param null $args
         * @param null $field
         * @return mixed|null
         */
        public function getOne(string $sql, $args = null, $field = null)
        {
            $row = $this->getRow($sql, $args);
            if ($row == null) {
                return null;
            }
            if (is_string($field) && !empty($field)) {
                return isset($row[$field]) ? $row[$field] : null;
            }
            return current($row);
        }

        public function getMax(string $tbname, string $field, $where = null, $args = null)
        {
            $sql = "select max(`{$field}`) from {$tbname}";
            if ($where !== null) {
                $where = trim($where);
                if ($args != null) {
                    $args = is_array($args) ? $args : [$args];
                }
                if (is_int($where) || is_numeric($where)) {
                    $args = [intval($where)];
                    $where = 'id=?';
                }
                $sql .= 'where ' . $where;
            }
            $row = $this->getRow($sql, $args, PDO::FETCH_NUM);
            if ($row == null) {
                return null;
            }
            return $row[0];
        }

        public function getMin(string $tbname, string $field, $where = null, $args = null)
        {
            $sql = "select min(`{$field}`) from {$tbname}";
            if ($where !== null) {
                $where = trim($where);
                if ($args != null) {
                    $args = is_array($args) ? $args : [$args];
                }
                if (is_int($where) || is_numeric($where)) {
                    $args = [intval($where)];
                    $where = 'id=?';
                }
                $sql .= 'where ' . $where;
            }
            $row = $this->getRow($sql, $args, PDO::FETCH_NUM);
            if ($row == null) {
                return null;
            }
            return $row[0];
        }

        public function sql(string $sql, $args = null)
        {
            return new SqlSection($sql, $args);
        }

        public static function escape($value)
        {
            if ($value === null) {
                return 'NULL';
            }
            $type = gettype($value);
            switch ($type) {
                case 'bool':
                case 'boolean':
                    return $value ? 1 : 0;
                case 'int':
                case 'integer':
                case 'double':
                case 'float':
                    return $value;
                case 'string':
                    break;
                case 'array':
                case 'object':
                    $value = json_encode($value, JSON_UNESCAPED_UNICODE);
                    break;
                default :
                    $value = strval($value);
                    break;
            }
            $value = '\'' . preg_replace_callback('@[\0\b\t\n\r\x1a\"\'\\\\]@', function ($m) {
                    $charsMap = [
                        '\0' => '\\0',
                        '\b' => '\\b',
                        '\t' => '\\t',
                        '\n' => '\\n',
                        '\r' => '\\r',
                        '\x1a' => '\\Z',
                        '"' => '\\"',
                        '\'' => '\\\'',
                        '\\' => '\\\\'
                    ];
                    return $charsMap[$m[0]];
                }, $value) . '\'';
            return $value;
        }

        public static function format(string $sql, $args = null)
        {
            if ($args == null) {
                return $sql;
            }
            if (!is_array($args)) {
                $args = [$args];
            }
            if (preg_match('@\?@', $sql)) {
                $index = 0;
                $sql = preg_replace_callback('@\?@', function ($match) use (&$args, &$index) {
                    if (!isset($args[$index])) {
                        $index++;
                        return '?';
                    }
                    $value = $args[$index];
                    $index++;
                    return Mysql::escape($value);
                }, $sql);
            }
            if (preg_match('@:(\w+)@', $sql)) {
                $sql = preg_replace_callback('@:(\w+)@', function ($match) use (&$args) {
                    $index = $match[1];
                    if (!isset($args[$index])) {
                        return $match[0];
                    }
                    $value = $args[$index];
                    return Mysql::escape($value);
                }, $sql);
            }
            return $sql;
        }

        public function insert(string $tbname, array $values = [])
        {
            if (count($values) == 0) {
                return;
            }
            $names = [];
            $vals = [];
            foreach ($values as $key => $item) {
                $names[] = '`' . $key . '`';
                if ($item === null) {
                    $vals [] = 'NULL';
                } else if ($item instanceof SqlSection) {
                    $vals [] = $item->format();
                } else {
                    $vals [] = Mysql::escape($item);
                }
            }
            $sql = 'insert into ' . $tbname . '(' . join(',', $names) . ') values (' . join(',', $vals) . ')';
            $Stm = $this->execute($sql);
            $Stm->closeCursor();
        }

        public function replace(string $tbname, array $values = [])
        {
            if (count($values) == 0) {
                return;
            }
            $names = [];
            $vals = [];
            foreach ($values as $key => $item) {
                $names[] = '`' . $key . '`';
                if ($item === null) {
                    $vals [] = 'NULL';
                } else if ($item instanceof SqlSection) {
                    $vals [] = $item->format();
                } else {
                    $vals [] = Mysql::escape($item);
                }
            }
            $sql = 'replace into ' . $tbname . '(' . join(',', $names) . ') values (' . join(',', $vals) . ')';
            $Stm = $this->execute($sql);
            $Stm->closeCursor();
        }

        public function update(string $tbname, array $values, $where = null, $args = null)
        {
            if (count($values) == 0) {
                return;
            }
            $where = trim($where);
            if (is_int($where) || is_numeric($where)) {
                $args = [intval($where)];
                $where = 'id=?';
            }
            $maps = [];
            foreach ($values as $key => $item) {
                if ($item === null) {
                    $maps [] = '`' . $key . '`=NULL';
                } else if ($item instanceof SqlSection) {
                    $maps [] = '`' . $key . '`=' . $item->format();
                } else {
                    $maps [] = '`' . $key . '`=' . Mysql::escape($item);
                }
            }
            $sql = 'update ' . $tbname . ' set ' . join(',', $maps);
            if (!empty($where)) {
                $sql .= ' where ' . $where;
            }
            $Stm = $this->execute($sql, $args);
            $Stm->closeCursor();
        }

        public function delete(string $tbname, $where = null, $args = null)
        {
            $where = trim($where);
            if (is_int($where) || is_numeric($where)) {
                $args = [intval($where)];
                $where = 'id=?';
            }
            $sql = 'DELETE FROM ' . $tbname;
            if (!empty($where)) {
                $sql .= ' where ' . $where;
            }
            $Stm = $this->execute($sql, $args);
            $Stm->closeCursor();
        }

        public function getFields(string $tbname)
        {
            return $this->getList('desc `' . $tbname . '`');
        }

        public function existsField(string $tbname, string $field)
        {
            return $this->getRow('DESCRIBE `' . $tbname . '` `' . $field . '`;') !== null;
        }

        public function addField(string $tbname, string $field, array $options = [])
        {
            $options = array_merge([
                'type' => 'VARCHAR',
                'len' => 250,
                'scale' => 0,
                'def' => null,
                'comment' => '',
            ], $options);
            list($type, $len, $scale, $def, $comment) = $options;
            $type = strtoupper($type);
            $sql = "ALTER TABLE {$tbname} ADD `${$field}`";
            switch ($type) {
                case 'VARCHAR':
                case 'INT':
                case 'BIGINT':
                case 'SMALLINT':
                case 'INTEGER':
                case 'TINYINT':
                    $sql .= $type . '(' . $len . ')';
                    break;
                case 'DECIMAL':
                case 'DOUBLE':
                case 'FLOAT':
                    $sql .= $type . '(' . $len . ',' . $scale . ')';
                    break;
                default:
                    $sql .= $type;
                    break;
            }
            $sql .= ' DEFAULT ' . Mysql::escape($def);
            if (!$comment) {
                $sql .= ' COMMENT ' . Mysql::escape($comment);
            }
            $sql .= ';';
            return $this->exec($sql);
        }

        public function modifyField(string $tbname, string $field, array $options = [])
        {
            $options = array_merge([
                'type' => 'VARCHAR',
                'len' => 250,
                'scale' => 0,
                'def' => null,
                'comment' => '',
            ], $options);
            list($type, $len, $scale, $def, $comment) = $options;
            $type = strtoupper($type);
            $sql = "ALTER TABLE {$tbname} MODIFY `${$field}`";
            switch ($type) {
                case 'VARCHAR':
                case 'INT':
                case 'BIGINT':
                case 'SMALLINT':
                case 'INTEGER':
                case 'TINYINT':
                    $sql .= $type . '(' . $len . ')';
                    break;
                case 'DECIMAL':
                case 'DOUBLE':
                case 'FLOAT':
                    $sql .= $type . '(' . $len . ',' . $scale . ')';
                    break;
                default:
                    $sql .= $type;
                    break;
            }
            $sql .= ' DEFAULT ' . Mysql::escape($def);
            if (!$comment) {
                $sql .= ' COMMENT ' . Mysql::escape($comment);
            }
            $sql .= ';';
            return $this->exec($sql);
        }

        public function updateField(string $tbname, string $oldfield, string $newfield, array $options = [])
        {
            if ($oldfield == $newfield) {
                return $this->modifyField($tbname, $newfield, $options);
            }
            $chkNew = $this->existsField($tbname, $newfield);
            if ($chkNew) {
                return $this->modifyField($tbname, $newfield, $options);
            }
            $chkOld = $this->existsField($tbname, $oldfield);
            if (!$chkOld && !$chkNew) {
                return $this->addField($tbname, $newfield, $options);
            }

            $options = array_merge([
                'type' => 'VARCHAR',
                'len' => 250,
                'scale' => 0,
                'def' => null,
                'comment' => '',
            ], $options);
            list($type, $len, $scale, $def, $comment) = $options;
            $type = strtoupper($type);
            $sql = "ALTER TABLE {$tbname} CHANGE `${$oldfield}` `${$newfield}`";
            switch ($type) {
                case 'VARCHAR':
                case 'INT':
                case 'BIGINT':
                case 'SMALLINT':
                case 'INTEGER':
                case 'TINYINT':
                    $sql .= $type . '(' . $len . ')';
                    break;
                case 'DECIMAL':
                case 'DOUBLE':
                case 'FLOAT':
                    $sql .= $type . '(' . $len . ',' . $scale . ')';
                    break;
                default:
                    $sql .= $type;
                    break;
            }
            $sql .= ' DEFAULT ' . Mysql::escape($def);
            if (!$comment) {
                $sql .= ' COMMENT ' . Mysql::escape($comment);
            }
            $sql .= ';';
            return $this->exec($sql);
        }

        public function dropField(string $tbname, string $field)
        {
            if ($this->existsField($tbname, $field)) {
                $sql = "ALTER TABLE {$tbname} DROP `${$field}`;";
                return $this->exec($sql);
            }
            return null;
        }

        public function existsTable(string $tbname)
        {
            $tbname = str_replace('@pf_', $this->prefix, $tbname);
            $row = $this->getRow('SHOW TABLES LIKE ?;', $tbname);
            return $row != null;
        }

        public function dropTable(string $tbname)
        {
            $tbname = str_replace('@pf_', $this->prefix, $tbname);
            return $this->exec('DROP TABLE IF EXISTS ' . Mysql::escape($tbname) . ';');
        }
    }
}