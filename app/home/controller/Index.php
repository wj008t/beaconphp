<?php

namespace app\home\controller;

use core\Controller;
use core\DB;
use core\Request;

class Index extends Controller
{
    public function indexAction(Request $request, string $name = 'wj008')
    {
        // $row = DB::getList('select 1 as temp;');
        \ChromePhp::log(gettype(DB::engine()));
        $v = DB::beginTransaction();
        //$row = DB::getMedoo()->update('building', ['name' => '荣昌大厦2wqw23'], ['id' => 1]);
        try {
            DB::update('@pf_building', ['name' => '荣昌大厦"eeDDSDXXXEddEEEE'], 1);
            DB::update('@pf_building', ['namec' => '荣昌大厦"2wqw23sss'], 1);
        } catch (\Exception $exception) {
        }
        $v = DB::rollBack();
        $row = DB::getList('select * from sl_building');
        $this->assign('data', '1');
        return $this->fetch('index.tpl');
    }
}