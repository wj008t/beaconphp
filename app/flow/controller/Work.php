<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/31
 * Time: 16:53
 */

namespace app\flow\controller;


use app\flow\lib\Flow;
use beacon\Console;
use beacon\Controller;
use beacon\DB;
use beacon\Request;
use beacon\Pagelist;

class Work extends Controller
{
    public function indexAction(Request $request)
    {
        $request->setSession('userId', 1);
        $pagelist = new PageList('select * from @pf_task order by id desc');
        $list = $pagelist->getList();
        $pinfo = $pagelist->getInfo();
        $this->assign('list', $list);
        $this->assign('pdata', $pinfo);
        if ($request->isAjax()) {
            $data = [];
            $data['pdata'] = $pinfo;
            $data['html'] = $this->fetch('extends:common/list_ajax.tpl|task.tpl');
            $this->success('获取成功', $data);
        }
        return $this->fetch('task.tpl');
    }

    public function createAction(Request $request)
    {
        try {
            DB::beginTransaction();
            DB::insert('@pf_task', ['name' => '测试用例']);
            $id = DB::lastInsertId();
            $data = Flow::create($id, '测试工作流程', ['userId' => 1, 'targetId' => 1]);
            DB::update('@pf_task', ['state' => $data['state']], $id);
            DB::commit();
            $this->success('执行成功');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('执行失败');
        }
    }

    public function step1Action(Request $request)
    {
        try {
            DB::beginTransaction();
            $taskId = $request->param('taskId');
            $branch = 'step1';
            //try
            $reday = Flow::reday($taskId, '测试工作流程', $branch, 1);
            //TODO
            Console::log($reday);
            $tokenId = $reday['tokenId'];
            $data = Flow::fire($tokenId, $branch, $reday['condition'], ['userId' => 1, 'targetId' => 1]);
            DB::update('@pf_task', ['state' => $data['state']], $data['taskId']);
            DB::commit();
            $this->success('执行成功');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error($e->getMessage());
        }
    }

    public function step2Action(Request $request)
    {
        try {
            DB::beginTransaction();
            $taskId = $request->get('taskId:i', 0);
            $branch = 'step2';
            $reday = Flow::reday($taskId, '测试工作流程', $branch, 1);
            //TODO
            Console::log($reday);
            $tokenId = $reday['tokenId'];
            $data = Flow::fire($tokenId, $branch, $reday['condition'], ['userId' => 1, 'targetId' => 1]);
            DB::update('@pf_task', ['state' => $data['state']], $data['taskId']);
            DB::commit();
            $this->success('执行成功');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error($e->getMessage());
        }
    }

    public function step3Action(Request $request)
    {
        try {
            DB::beginTransaction();
            $taskId = $request->get('taskId:i', 0);
            $branch = 'step3';
            $reday = Flow::reday($taskId, '测试工作流程', $branch, 1);
            //TODO
            Console::log($reday);
            $tokenId = $reday['tokenId'];
            $data = Flow::fire($tokenId, $branch, $reday['condition'], ['userId' => 1, 'targetId' => 1]);
            DB::update('@pf_task', ['state' => $data['state']], $data['taskId']);
            DB::commit();
            $this->success('执行成功');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error($e->getMessage());
        }
    }

    public function step4Action(Request $request)
    {
        try {
            DB::beginTransaction();
            $taskId = $request->get('taskId:i', 0);
            $branch = 'step4';
            $reday = Flow::reday($taskId, '测试工作流程', $branch, 1);
            //TODO
            Console::log($reday);
            $tokenId = $reday['tokenId'];
            $data = Flow::fire($tokenId, $branch, $reday['condition'], ['userId' => 1, 'targetId' => 1]);
            DB::update('@pf_task', ['state' => $data['state']], $data['taskId']);
            DB::commit();
            $this->success('执行成功');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error($e->getMessage());
        }
    }

    public function deleteAction(Request $request)
    {
        try {
            DB::beginTransaction();
            $taskId = $request->get('taskId:i', 0);
            Flow::delete($taskId, '测试工作流程');
            DB::delete('@pf_task', $taskId);
            DB::commit();
            $this->success('执行成功');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('执行失败', $e->getMessage());
        }
    }

}