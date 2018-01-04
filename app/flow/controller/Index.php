<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/22
 * Time: 17:15
 */

namespace app\flow\controller;

use app\flow\form\FlowForm;
use app\flow\lib\Flow;
use beacon\Controller;
use beacon\DB;
use beacon\Pagelist;
use beacon\Request;
use beacon\Utils;


class Index extends Controller
{
    /**
     * @param Request $request
     * @return mixed
     */
    public function indexAction(Request $request)
    {
        $pagelist = new PageList('select * from @pf_flow_list order by id desc');
        $list = $pagelist->getList();
        $pinfo = $pagelist->getInfo();
        $this->assign('list', $list);
        $this->assign('pdata', $pinfo);
        if ($request->isAjax()) {
            $data = [];
            $data['pdata'] = $pinfo;
            $data['html'] = $this->fetch('extends:common/list_ajax.tpl|index.tpl');
            $this->success('获取成功', $data);
        }
        return $this->fetch('index.tpl');
    }

    public function addAction(Request $request)
    {
        $form = new FlowForm();
        if ($request->isGet()) {
            $this->assign('form', $form);
            return $this->fetch('flow_act.tpl');
        }
        $vals = $form->autoComplete(function ($errors) {
            $this->error($errors);
        });
        DB::insert('@pf_flow_list', $vals);
        $this->success("添加成功");
    }

    public function editAction(Request $request, int $id)
    {
        $form = new FlowForm();
        if ($request->isGet()) {
            $row = DB::getRow('select * from @pf_flow_list where id=?', $id);
            $form->initValues($row);
            $form->addHideBox('id', $id);
            $this->assign('form', $form);
            return $this->fetch('flow_act.tpl');
        }
        $vals = $form->autoComplete(function ($errors) {
            $this->error($errors);
        });
        DB::update('@pf_flow_list', $vals, $id);
        $this->success("编辑成功");
    }

    public function deleteAction(int $id)
    {
        DB::beginTransaction();
        try {
            DB::delete('@pf_flow_connection', 'flowid=?', $id);
            DB::delete('@pf_flow_place', 'flowid=?', $id);
            DB::delete('@pf_flow_transition', 'flowid=?', $id);
            DB::delete('@pf_flow_list', $id);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            // $this->error($e->getMessage());
        }
        $this->success("删除成功");
    }

    public function testAction(Request $request)
    {
        $temp = $request->post('data:s', '');
        if (Utils::isJsonString($temp)) {
            $data = json_decode($temp, true);
            $temp = [];
            if (is_array($data)) {
                foreach ($data as $item) {
                    $token = Flow::getToken($item['t'], $item['n'], $item['b']);
                    if ($token > 0) {
                        $temp[] = ['i' => $item['i'], 'tk' => $token];
                    }
                }
            }
            $this->success('ok', $temp);
        } else {
            $this->error('错误');
        }
    }

}