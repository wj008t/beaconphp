<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/31
 * Time: 16:53
 */

namespace app\flow\controller;


use app\flow\lib\Flow;
use beacon\Controller;
use beacon\PageList;
use beacon\Request;

class Work extends Controller
{
    public function indexAction(Request $request)
    {
        $request->setSession('userId', 1);
        $pagelist = new PageList($this->context, 'select * from @pf_task order by id desc');
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
            $this->db->beginTransaction();
            $this->db->insert('@pf_task', ['name' => '测试用例']);
            $id = $this->db->lastInsertId();
            $data = Flow::create($this->context, $id, '测试工作流程', ['userId' => 1, 'targetId' => 1]);
            $this->db->update('@pf_task', ['state' => $data['state']], $id);
            $this->db->commit();
            $this->success('执行成功');
        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->error('执行失败');
        }
    }

    public function param(Request $request, $branch)
    {
        $args = [];
        if ($request->post('timeout:i', 0) > 0) {
            $args['timeout'] = $request->post('timeout:i', 0);
            $args['condition'] = $request->post('condition:i', 0);
            $args['sign'] = $request->post('sign:s', '');
            $tokenId = $request->post('tokenId:i', 0);
            if ($request->post('branch:s', '') != $branch) {
                $this->error('执行失败');
            }
        } else {
            //手动执行
            $userId = $request->getSession('userId');
            $taskId = $request->param('taskId:i', 0);
            $args['userId'] = $userId;
            $args['condition'] = $request->param('condition:i', 1);
            $tokenId = Flow::getToken($this->context, $taskId, '测试工作流程', $branch, $args);
        }
        if ($tokenId == 0) {
            $this->error('业务流程已过期，请刷新页面');
        }
        return [$tokenId, $args];
    }

    public function step1Action(Request $request)
    {
        try {
            $this->db->beginTransaction();
            $branch = 'step1';
            list($tokenId, $args) = $this->param($request, $branch);
            //处理自动执行
            Flow::reday($this->context, $tokenId, $branch, $args);
            $data = Flow::fire($this->context, $tokenId, $branch, $args['condition'], ['userId' => 1, 'targetId' => 1]);
            $this->db->update('@pf_task', ['state' => $data['state']], $data['taskId']);
            $this->db->commit();
            $this->success('执行成功');
        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->error('执行失败', $e->getMessage());
        }
    }

    public function step2Action(Request $request)
    {
        try {
            $this->db->beginTransaction();
            $branch = 'step2';
            list($tokenId, $args) = $this->param($request, $branch);
            //处理自动执行
            Flow::reday($this->context, $tokenId, $branch, $args);
            $data = Flow::fire($this->context, $tokenId, $branch, $args['condition'], ['userId' => 1, 'targetId' => 1]);
            $this->db->update('@pf_task', ['state' => $data['state']], $data['taskId']);
            $this->db->commit();
            $this->success('执行成功');
        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->error('执行失败', $e->getMessage());
        }
    }

    public function step3Action(Request $request)
    {
        try {
            $this->db->beginTransaction();
            $branch = 'step3';
            list($tokenId, $args) = $this->param($request, $branch);
            //处理自动执行
            Flow::reday($this->context, $tokenId, $branch, $args);
            $data = Flow::fire($this->context, $tokenId, $branch, $args['condition'], ['userId' => 1, 'targetId' => 1]);
            $this->db->update('@pf_task', ['state' => $data['state']], $data['taskId']);
            $this->db->commit();
            $this->success('执行成功');
        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->error('执行失败', $e->getMessage());
        }
    }

    public function step4Action(Request $request)
    {
        try {
            $this->db->beginTransaction();
            $branch = 'step4';
            list($tokenId, $args) = $this->param($request, $branch);
            //处理自动执行
            Flow::reday($this->context, $tokenId, $branch, $args);
            $data = Flow::fire($this->context, $tokenId, $branch, $args['condition'], ['userId' => 1, 'targetId' => 1]);
            $this->db->update('@pf_task', ['state' => $data['state']], $data['taskId']);
            $this->db->commit();
            $this->success('执行成功');
        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->error('执行失败', $e->getMessage());
        }
    }

    public function deleteAction(Request $request)
    {
        try {
            $this->db->beginTransaction();
            $taskId = $request->get('taskId:i', 0);
            Flow::delete($this->context, $taskId, '测试工作流程');
            $this->db->delete('@pf_task', $taskId);
            $this->db->commit();
            $this->success('执行成功');
        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->error('执行失败', $e->getMessage());
        }
    }

}