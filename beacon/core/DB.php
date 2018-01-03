<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/13
 * Time: 16:51
 */

namespace beacon;

use \PDO as PDO;


class DB
{
    public static $engine = null;

    public static function engine()
    {
        if (self::$engine !== null) {
            return self::$engine;
        }
        $driver = Config::get('db.db_driver', 'Mysql');
        if ($driver == 'Mysql') {
            $host = Config::get('db.db_host', '127.0.0.1');
            $port = Config::get('db.db_port', 3306);
            $name = Config::get('db.db_name', '');
            $user = Config::get('db.db_user', '');
            $pass = Config::get('db.db_pwd', '');
            $prefix = Config::get('db.db_prefix', 'sl_');
            self::$engine = new Mysql($host, $port, $name, $user, $pass, $prefix);
            return self::$engine;
        }
    }

    public static function beginTransaction()
    {
        return self::engine()->beginTransaction();
    }

    public static function commit()
    {
        return self::engine()->commit();
    }

    public static function rollBack()
    {
        return self::engine()->rollBack();
    }

    public static function exec(string $sql)
    {
        return self::engine()->exec($sql);
    }

    public static function lastInsertId($name = null)
    {
        return self::engine()->lastInsertId($name);
    }

    public static function execute(string $sql, $args = null)
    {
        return self::engine()->execute($sql, $args);
    }

    /**
     * 获得多行内容
     * @param string $sql
     * @param null $args
     * @param null $fetch_style
     * @param null $fetch_argument
     * @param array $ctor_args
     * @return mixed
     */
    public static function getList(string $sql, $args = null, $fetch_style = null, $fetch_argument = null, array $ctor_args = null)
    {
        return self::engine()->getList($sql, $args, $fetch_style, $fetch_argument, $ctor_args);
    }

    /**
     * @param string $sql
     * @param null $args
     * @param null $fetch_style
     * @param null $cursor_orientation
     * @param int $cursor_offset
     * @return mixed
     */
    public static function getRow(string $sql, $args = null, $fetch_style = null, $cursor_orientation = null, $cursor_offset = 0)
    {
        return self::engine()->getRow($sql, $args, $fetch_style, $cursor_orientation, $cursor_offset);
    }

    /**
     * 获得单个字段内容
     * @param string $sql
     * @param null $args
     * @param null $field
     * @return
     */
    public static function getOne(string $sql, $args = null, $field = null)
    {
        return self::engine()->getOne($sql, $args, $field);
    }

    public static function getMax(string $tbname, string $field, $where = null, $args = null)
    {
        return self::engine()->getMax($tbname, $field, $where, $args);
    }

    public static function getMin(string $tbname, string $field, $where = null, $args = null)
    {
        return self::engine()->getMin($tbname, $field, $where, $args);
    }

    public static function sql(string $sql, $args = null)
    {
        return self::engine()->sql($sql, $args);
    }

    public static function insert(string $tbname, array $values = [])
    {
        return self::engine()->insert($tbname, $values);
    }

    public static function replace(string $tbname, array $values = [])
    {
        return self::engine()->replace($tbname, $values);
    }

    public static function update(string $tbname, array $values, $where = null, $args = null)
    {
        return self::engine()->update($tbname, $values, $where, $args);
    }

    public static function delete(string $tbname, $where = null, $args = null)
    {
        return self::engine()->delete($tbname, $where, $args);
    }

    public static function getFields(string $tbname)
    {
        return self::engine()->getFields($tbname);
    }

    public static function existsField(string $tbname, string $field)
    {
        return self::engine()->existsField($tbname, $field);
    }

    public static function addField(string $tbname, string $field, array $options = [])
    {
        return self::engine()->addField($tbname, $field, $options);
    }

    public static function modifyField(string $tbname, string $field, array $options = [])
    {
        return self::engine()->modifyField($tbname, $field, $options);
    }

    public static function updateField(string $tbname, string $oldfield, string $newfield, array $options = [])
    {
        return self::engine()->modifyField($tbname, $oldfield, $newfield, $options);
    }

    public static function dropField(string $tbname, string $field)
    {
        return self::engine()->dropField($tbname, $field);
    }

    public static function existsTable(string $tbname)
    {
        return self::engine()->existsTable($tbname);
    }

    public static function dropTable(string $tbname)
    {
        return self::engine()->dropTable($tbname);
    }
}