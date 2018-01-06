<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2018/1/5
 * Time: 1:37
 */

namespace beacon;

class SqlItem
{
    public $sql = '';
    public $args = null;

    public function __construct(string $sql, $args = null)
    {
        $this->sql = trim($sql);
        $this->args = $args;
    }

    public function add(string $sql, $args = null)
    {
        $this->sql .= $sql;
        if ($args === null || (is_array($args) && count($args) == 0)) {
            return $this;
        }
        if (!is_array($args)) {
            $args = [$args];
        }
        if ($this->args === null) {
            $this->args = $args;
        } else {
            $this->args = array_merge($this->args, $args);
        }
        return $this;
    }

}

class SqlCondition
{
    const WITHOUT_EMPTY = 0;
    const WITHOUT_NULL = 1;
    const WITHOUT_ZERO_LENGTH = 2;
    const WITHOUT_ZERO = 3;
    /**
     * @var SqlItem[]
     */
    private $items = [];
    public $type = 'and';

    public function __construct(string $type = 'and')
    {
        $this->type = 'and';
    }

    public function where($sql, $args = null)
    {
        if ($sql instanceof SqlCondition) {
            $frame = $sql->getFrame();
            if ($frame['sql'] !== '') {
                if ($frame['type'] !== '') {
                    $this->items[] = new SqlItem($frame['type'] . ' (' . $frame['sql'] . ')', $frame['args']);
                } else {
                    $this->items[] = new SqlItem('(' . $frame['sql'] . ')', $frame['args']);
                }
            }
            return $this;
        }
        if (!is_string($sql)) {
            return $this;
        }
        $sql = trim($sql);
        if (!isset($sql[0])) {
            return $this;
        }
        $item = new SqlItem($sql, $args);
        $this->items[] = $item;
    }

    public function search(string $sql, $value, $type = self::WITHOUT_EMPTY, string $format = null)
    {
        switch ($type) {
            case self::WITHOUT_EMPTY:
                if (empty($value)) {
                    return $this;
                }
            case self::WITHOUT_NULL:
                if ($value === null) {
                    return $this;
                }
            case self::WITHOUT_ZERO_LENGTH:
                if ($value === null || strval($value) === '') {
                    return $this;
                }
            case self::WITHOUT_ZERO:
                if ($value === '0' || (is_numeric($value) && floatval($value) == 0) || $value === 0 || $value === false || $value === null) {
                    return $this;
                }
            default:
                break;
        }
        if ($format !== null) {
            $value = preg_replace('@\{0\}@', $value);
        }
        $this->where($sql, $value);
        return $this;
    }

    public function getFrame()
    {
        $sqlItems = [];
        $argItems = [];
        foreach ($this->items as $item) {
            $tempSql = $item->sql;
            $tempArgs = $item->args;
            if (preg_match('@^or\s+@i', $tempSql)) {
                if (count($sqlItems) == 0) {
                    $tempSql = preg_replace('@^or\s+@i', '', $tempSql);
                }
            } else if (preg_match('@^and\s+@i', $tempSql)) {
                if (count($sqlItems) == 0) {
                    $tempSql = preg_replace('@^and\s+@i', '', $tempSql);
                }
            } else {
                if (count($sqlItems) >= 0) {
                    $tempSql = 'and ' . $tempSql;
                }
            }
            $sqlItems[] = $tempSql;
            if (is_array($tempArgs)) {
                $argItems = array_merge($argItems, $tempArgs);
            } else if ($tempArgs !== null) {
                $argItems[] = $tempArgs;
            }

        }
        return ['sql' => join(' ', $sqlItems), 'args' => $argItems, 'type' => $this->type];
    }

}

class SqlSelector
{


    private $table = '';
    /**
     * @var SqlItem;
     */
    private $orderItem = null;
    /**
     * @var SqlItem;
     */
    private $groupItem = null;
    /**
     * @var SqlItem;
     */
    private $findItem = null;
    private $limit = '';
    /**
     * @var SqlCondition
     */
    private $condition = null;

    public function __construct($table)
    {
        $this->table = $table;
        $this->condition = new SqlCondition();
    }

    public function createSqlCondition(string $type = 'and')
    {
        return new SqlCondition($type);
    }

    public function where(string $sql, $args = null)
    {
        $this->condition->where($sql, $args);
        return $this;
    }

    public function search(string $sql, $value, $type = SqlCondition::WITHOUT_EMPTY, $format = null)
    {
        $this->condition->search($sql, $value, $type, $format);
        return $this;
    }

    public function getFrame()
    {
        return $this->condition->getFrame();
    }

    public function field(string $find, $args = null)
    {
        $this->findItem = new SqlItem($find, $args);
        return $this;
    }

    public function order(string $order, $args = null)
    {
        $order = trim($order);
        if (!preg_match('@^by\s+@i', $order)) {
            $order = 'by ' . $order;
        }
        if ($this->orderItem === null) {
            $this->orderItem = new SqlItem($order, $args);
        } else {
            $this->orderItem->add($order, $args);
        }
        return $this;
    }

    public function group(string $group, $args = null)
    {
        $group = trim($group);
        if (!preg_match('@^by\s+@i', $group)) {
            $group = 'by ' . $group;
        }
        if ($this->groupItem === null) {
            $this->groupItem = new SqlItem($group, $args);
        } else {
            $this->groupItem->add($group, $args);
        }
        return $this;
    }

    public function limit(int $offset = 0, int $size = 0)
    {
        if ($offset === 0 && $size == 0) {
            return $this;
        }
        if ($size === 0) {
            $this->limit = 'limit ' . $offset;
        } else {
            $this->limit = 'limit ' . $offset . ',' . $size;
        }
        return $this;
    }

    public function createSql($type = 0)
    {
        $sqlItems = [];
        $argItems = [];
        if ($type == 2) {
            $sqlItems[] = 'select count(*) from ' . $this->table;
        } elseif ($type == 1) {
            $findSql = 'Z.*';
            $findArgs = null;
            if ($this->findItem !== null) {
                $findSql = $this->findItem->sql;
                $findArgs = $this->findItem->args;
            }
            $sqlItems[] = "select Z.* from `{$this->table}` Z,(select id from `{$this->table}`";
            if ($findArgs !== null) {
                $argItems = array_merge($argItems, $findArgs);
            }
        } else {
            $findSql = '*';
            $findArgs = null;
            if ($this->findItem !== null) {
                $findSql = $this->findItem->sql;
                $findArgs = $this->findItem->args;
            }
            $sqlItems[] = "select {$findSql} from `{$this->table}`";
            if ($findArgs !== null) {
                $argItems = array_merge($argItems, $findArgs);
            }
        }

        $frame = $this->condition->getFrame();
        if (!empty($frame['sql'])) {
            if (preg_match('@^(AND|OR)\s+@i', $frame['sql'])) {
                $sqlItems[] = 'where ' . preg_replace('@^(AND|OR)\s+@i', '', $frame['sql']);
            } else {
                $sqlItems[] = $frame['sql'];
            }
        }
        if ($frame['args'] !== null && is_array($frame['args'])) {
            $argItems = array_merge($argItems, $frame['args']);
        }
        if ($this->groupItem != null) {
            $groupSql = $this->groupItem->sql;
            $groupArgs = $this->groupItem->args;
            if (!empty($groupSql)) {
                $sqlItems[] = 'group ' . $groupSql;
            }
            if ($groupArgs !== null && is_array($groupArgs)) {
                $argItems = array_merge($argItems, $groupArgs);
            }
        }

        if ($type != 2 && $this->orderItem != null) {
            $orderSql = $this->orderItem->sql;
            $ordeArgs = $this->orderItem->args;
            if (!empty($orderSql)) {
                $sqlItems[] = 'order ' . $orderSql;
            }
            if ($ordeArgs !== null && is_array($ordeArgs)) {
                $argItems = array_merge($argItems, $ordeArgs);
            }
        }
        if ($type != 2 && !empty($this->limit)) {
            $sqlItems[] = $this->limit;
        }
        if ($type == 1) {
            $sqlItems[] = ') Y where Z.id=Y.id';
            if ($this->orderItem != null) {
                $orderSql = $this->orderItem->sql;
                $ordeArgs = $this->orderItem->args;
                if (!empty($orderSql)) {
                    $orderSql = preg_replace_callback('@by\s+(`?\w+`?)\s+(desc|asc)@i', function ($math) {
                        return 'by Z.' . $math[1] . ' ' . $math[2];
                    }, $orderSql);
                    $sqlItems[] = 'order ' . $orderSql;
                }
                if ($ordeArgs !== null && is_array($ordeArgs)) {
                    $argItems = array_merge($argItems, $ordeArgs);
                }
            }
        }
        return ['sql' => join(' ', $sqlItems), 'args' => $argItems];
    }

    public function getCount()
    {
        $temp = $this->createSql(2);
        $count = DB::getOne($temp['sql'], $temp['args']);
        if ($count === null) {
            return 0;
        }
        return intval($count);
    }

    public function getPageList($size = 20, $pagekey = 'page', $count = -1, $only_count = -1)
    {
        if ($count == -1) {
            $count = $this->getCount();
        }
        $temp = $this->createSql(1);
        return new PageList($temp['sql'], $temp['args'], $size, $pagekey, $count, $only_count);
    }

    public function getList()
    {
        $temp = $this->createSql(0);
        return DB::getList($temp['sql'], $temp['args']);
    }

}