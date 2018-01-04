<?php

namespace beacon;

use PDO as PDO;

/**
 * 数据分页类 of Pagelist
 *
 * @author WJ008
 */
class PageList
{

    private $context;
    private $sql;
    private $page; //当前页面
    private $records_count;  //记录数
    private $only_count; //仅显示
    private $page_size; //页面大小
    private $page_count; //最大页数
    private $key;
    private $args;
    private $info;

    /**
     * 数据库分页类 <br/>
     * (MysqlDB &gt;= 1.0.0, SaMao &gt;= 1.0.0)<br/>
     * @param string $sql 要执行的SQL语句
     * @param array $args sql参数数组
     * @param int $size 分页大小
     * @param string $pagekey 分页的URL名称$_GET['?']
     * @param int $count 直接给定记录数可以提高查询效率，例如使用缓存的记录数或者参数给回的记录数。
     */
    public function __construct(HttpContext $context, $sql, $args = array(), $size = 20, $pagekey = 'page', $count = -1, $only_count = -1)
    {
        $req = $context->getRequest();
        $this->context = $context;
        $this->sql = $sql;
        $this->page_size = intval($size);
        $this->key = $pagekey;
        $this->args = $args;
        $this->page = $req->param($pagekey . ':i', 1);
        $this->page_count = -1;
        $this->records_count = $count;
        $this->only_count = $only_count;
        $this->info = NULL;
    }

    private function getPageCount($count, $size)
    {
        if (($count % $size) == 0) {
            $pagecount = ($count / $size);
        } else {
            $pagecount = (int)($count / $size) + 1;
        }
        if ($pagecount == 0) {
            $pagecount = 1;
        }
        return $pagecount;
    }

    /**
     * 获取 分页信息数据
     * @return array <br/>
     * array(<br/>
     * 'page_key'      ：分页参数名称 如果 page,<br/>
     * 'query'         ：本页所附带的连接 即 $_SERVER['REQUEST_URI'] <br/>例如 cid=3&keyword=abc&page=3<br/>
     * 'other_query'   ：除了page 外的所有 附带参数, <br/>例如 cid=3&keyword=abc 不带页码参数<br/>
     * 'page'          ：当前 页码,<br/>
     * 'page_count'    ：页数，即最大页数,<br/>
     * 'records_count' ：记录数,<br/>
     * 'size'          ：分页尺度，即按多少条记录为一页,<br/>
     * )
     */
    public function getInfo()
    {
        if ($this->info != NULL) {
            return $this->info;
        }
        $url = $this->context->_server['REQUEST_URI'];
        $Idx = strpos($url, '?');
        $query = '';
        if ($Idx !== false) {
            $query = substr($url, $Idx + 1);
        }
        if ($this->records_count == -1) {
            if (strripos($this->sql, ' from ') === stripos($this->sql, ' from ')) {
                $sql = preg_replace('@^select\s+(distinct\s+[a-z][a-z0-9]+\s*,)?(.*)\s+from\s+@', 'select $1count(1) from ', $this->sql);
                $row = $this->context->getDataBase()->getRow($sql, $this->args, PDO::FETCH_NUM);
            } else {
                $row = $this->context->getDataBase()->getRow('select count(1) from (' . $this->sql . ') MyTempTable', $this->args, PDO::FETCH_NUM);
            }
            $this->records_count = $row[0];
        }
        if ($this->only_count == -1 || $this->only_count > $this->records_count) {
            $this->only_count = $this->records_count;
        }
        if ($this->page_count == -1) {
            $this->page_count = $this->getPageCount($this->only_count, $this->page_size);
        }
        if ($this->page <= 0) {
            $this->page = 1;
        }
        if ($this->page > $this->page_count) {
            $this->Page = $this->page_count;
        }
        $otlink = preg_replace('/' . $this->key . '=\d*&?/', '', $query);
        $this->info = array(
            'keyname' => $this->key,
            'query' => $query,
            'other_query' => $otlink,
            'page' => $this->page,
            'page_count' => $this->page_count,
            'records_count' => $this->records_count,
            'only_count' => $this->only_count,
            'page_size' => $this->page_size,
        );
        return $this->info;
    }

    /**
     * 获取 分页后的记录集数据
     * @param int $fetch_style 结果遍历类型 PDO遍历类型
     * @link http://php.net/manual/en/pdostatement.fetch.php
     *
     * @return array 返回结果集数组
     */
    public function getList($fetch_style = null, $fetch_argument = null, array $ctor_args = null)
    {
        $this->getInfo();
        $start = ($this->page - 1) * $this->page_size;
        if ($start < 0) {
            $start = 0;
        }
        $sql = $this->sql . ' limit ' . $start . ' , ' . $this->page_size;
        return $this->context->getDataBase()->getList($sql, $this->args, $fetch_style, $fetch_argument, $ctor_args);
    }

    public function getAll($fetch_style = null, $fetch_argument = null, array $ctor_args = null)
    {
        return $this->context->getDataBase()->getList($this->sql, $this->args, $fetch_style, $fetch_argument, $ctor_args);
    }

}
