<?php

namespace app\home\controller;

use app\home\form\UserForm;
use beacon\Controller;
use beacon\DB;
use beacon\Request;

class Index extends Controller
{
    public function indexAction(Request $request, string $name = 'wj008', int $t = 2)
    {
        // $request->setCookie('aaaaa', 'bbbbb');
        // $this->context->setCookie('bbbxxxxxx', 'xxx');
        $this->context->write(var_export($request->getSession(), true));
        //$request->setSession('aaa', 'xxx');
        // $row = DB::getList('select 1 as temp;');
        // $v = DB::beginTransaction();
        //$row = DB::getMedoo()->update('building', ['name' => '荣昌大厦2wqw23'], ['id' => 1]);
        // try {
        //     DB::update('@pf_building', ['name' => '荣昌大厦"eeDDSDXXXEddEEEE'], 1);
        //     DB::update('@pf_building', ['namec' => '荣昌大厦"2wqw23sss'], 1);
        //} catch (\Exception $exception) {
        // }
        // $v = DB::rollBack();
        // $row = DB::getList('select * from sl_building');
        $form = new UserForm($this->context, 'add');
        // $box = \beacon\Form::getBoxInstance('text');
        $this->assign('data', '1');
        $this->assign('form', $form);
        return $this->fetch('index.tpl');
    }

    public function showAction()
    {
        return $this->fetch('show.tpl');
    }

    public function saveAction()
    {
        $form = new UserForm('add');
        $vals = $form->autoComplete();
        return $vals;
    }
}