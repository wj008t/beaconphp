<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/23
 * Time: 13:33
 */

namespace app\flow\controller;


use beacon\Controller;
use beacon\Request;

class Main extends Controller
{
    private $fid = 0;

    public function initialize()
    {
        $request = new Request($this->context);
        if ($request->isAjax()) {
            $this->context->setContentType('json');
        }
        $this->fid = $request->get('fid:i', 0);
        if ($this->fid == 0) {
            $this->error('缺少参数', null, '/flow/index');
        }
        $this->assign('fid', $this->fid);
    }

    public function indexAction()
    {
        return $this->fetch('main.tpl');
    }

    public function addPlaceAction(Request $request)
    {
        if ($request->isGet()) {
            return $this->fetch('place_act.tpl');
        }
        $name = $request->post('name:s', '');
        $code = $request->post('code:s', '');
        $mode = $request->post('mode:i', 0);
        $state = $request->post('state:i', 0);

        if ($name == '') {
            $this->error(['name' => '必须填写名称']);
        }
        if ($code == '') {
            $this->error(['code' => '必须填写标识符号']);
        }
        if ($mode == 1) {
            $row = $this->db->getRow('select * from @pf_flow_place where mode=1 and flowId=?', $this->fid);
            if ($row != null) {
                $this->error(['mode' => '只能有一个起始库所']);
            }
        }
        if ($mode == 0) {
            $row = $this->db->getRow('select * from @pf_flow_place where mode=1 and flowId=?', $this->fid);
            if ($row == null) {
                $this->error(['mode' => '还未创建起始库所']);
            }
        }
        $this->db->insert('@pf_flow_place', ['name' => $name, 'code' => $code, 'left' => 0, 'top' => 0, 'mode' => $mode, 'state' => $state, 'flowId' => $this->fid]);
        $id = $this->db->lastInsertId();
        $this->success('添加成功', ['id' => $id, 'name' => $name, 'code' => $code, 'mode' => $mode, 'state' => $state, 'left' => 0, 'top' => 0]);
    }

    public function editPlaceAction(Request $request, int $id = 0)
    {
        $row = $this->db->getRow('select * from @pf_flow_place where id=? and flowId=?', [$id, $this->fid]);
        if ($request->isGet()) {
            $this->assign('row', $row);
            return $this->fetch('place_edit.tpl');
        }
        $this->context->setContentType('json');
        $name = $request->post('name:s', '');
        $code = $request->post('code:s', '');
        $state = $request->post('state:i', 0);
        if ($name == '') {
            $this->error(['name' => '必须填写名称']);
        }
        if ($code == '') {
            $this->error(['code' => '必须填写标识符号']);
        }
        $this->db->update('@pf_flow_place', ['name' => $name, 'code' => $code, 'state' => $state], 'id=? and flowId=?', [$id, $this->fid]);
        $this->success('添加成功', ['id' => $id, 'name' => $name, 'code' => $code, 'mode' => $row['mode'], 'state' => $state, 'left' => $row['left'], 'top' => $row['top']]);
    }

    public function addTransitionAction(Request $request)
    {
        if ($request->isGet()) {
            return $this->fetch('transition_act.tpl');
        }
        $name = $request->post('name:s', '');
        $code = $request->post('code:s', '');
        $url = $request->post('url:s', '');
        $timeout_day = $request->post('timeout_day:i', 0);
        $timeout_hour = $request->post('timeout_hour:i', 0);
        $timeout_minute = $request->post('timeout_minute:i', 0);
        $timeout_second = $request->post('timeout_second:i', 0);
        $timeout = $timeout_day * 86400 + $timeout_hour * 3600 + $timeout_minute * 60 + $timeout_second;
        $timeoutCondition = $request->post('timeoutCondition:s', '');
        if ($name == '') {
            $this->error(['name' => '必须填写名称']);
        }
        if ($code == '') {
            $this->error(['code' => '必须填写标识符号']);
        }
        $this->db->insert('@pf_flow_transition', ['name' => $name, 'code' => $code, 'left' => 0, 'top' => 0, 'url' => $url, 'timeout' => $timeout, 'timeoutCondition' => $timeoutCondition, 'flowId' => $this->fid]);
        $id = $this->db->lastInsertId();
        $this->success('添加成功', ['id' => $id, 'name' => $name, 'code' => $code, 'left' => 0, 'top' => 0, 'url' => $url, 'timeout' => $timeout]);
    }

    public function editTransitionAction(Request $request, int $id = 0)
    {
        $row = $this->db->getRow('select * from @pf_flow_transition where id=? and flowId=?', [$id, $this->fid]);
        if ($request->isGet()) {
            $timeout = $row['timeout'];
            $row['timeout_day'] = intval($timeout / 86400);
            $timeout = $timeout % 86400;
            $row['timeout_hour'] = intval($timeout / 3600);
            $timeout = $timeout % 3600;
            $row['timeout_minute'] = intval($timeout / 60);
            $row['timeout_second'] = $timeout % 60;
            $this->assign('row', $row);
            return $this->fetch('transition_edit.tpl');
        }
        $name = $request->post('name:s', '');
        $code = $request->post('code:s', '');
        $url = $request->post('url:s', '');
        $timeout_day = $request->post('timeout_day:i', 0);
        $timeout_hour = $request->post('timeout_hour:i', 0);
        $timeout_minute = $request->post('timeout_minute:i', 0);
        $timeout_second = $request->post('timeout_second:i', 0);
        $timeout = $timeout_day * 86400 + $timeout_hour * 3600 + $timeout_minute * 60 + $timeout_second;
        $timeoutCondition = $request->post('timeoutCondition:s', '');
        if ($name == '') {
            $this->error(['name' => '必须填写名称']);
        }
        if ($code == '') {
            $this->error(['code' => '必须填写标识符号']);
        }
        $this->db->update('@pf_flow_transition', ['name' => $name, 'code' => $code, 'url' => $url, 'timeout' => $timeout, 'timeoutCondition' => $timeoutCondition], 'id=? and flowId=?', [$id, $this->fid]);
        $this->success('添加成功', ['id' => $id, 'name' => $name, 'code' => $code, 'url' => $url, 'timeout' => $timeout, 'left' => $row['left'], 'top' => $row['top']]);
    }

    public function delPlaceAction(Request $request, int $id = 0)
    {
        $this->db->delete('@pf_flow_connection', '(source=? and sourceType=? and flowId=? ) or (target=? and targetType=? and flowId=?)', [$id, 'place', $this->fid, $id, 'place', $this->fid]);
        $this->db->delete('@pf_flow_place', 'id=? and flowId=?', [$id, $this->fid]);
        $this->success('删除成功', ['type' => 'place', 'id' => $id]);
    }

    public function delTransitionAction(Request $request, int $id = 0)
    {
        $this->db->delete('@pf_flow_connection', '(source=? and sourceType=? and flowId=?) or (target=? and targetType=? and flowId=?)', [$id, 'transition', $this->fid, $id, 'transition', $this->fid]);
        $this->db->delete('@pf_flow_transition', 'id=? and flowId=?', [$id, $this->fid]);
        $this->success('删除成功', ['type' => 'transition', 'id' => $id]);
    }

    public function offsetAction(Request $request, string $type = '', int $id = 0, int $top = 0, int $left = 0)
    {
        $val = ['left' => $left, 'top' => $top];
        if ($type == 'place') {
            $this->db->update('@pf_flow_place', $val, $id);
        }
        if ($type == 'transition') {
            $this->db->update('@pf_flow_transition', $val, $id);
        }
        $val['id'] = $id;
        $val['type'] = $type;
        $this->success('获取成功', $val);
    }

    public function connectAction(Request $request, int $source, int $target, string $sourceType = '', string $targetType = '')
    {
        if ($request->isGet()) {
            return $this->fetch('connect_add.tpl');
        }
        $name = $request->post('name:s', '');
        $condition = $request->post('condition:i', 1);
        $row = $this->db->getRow('select * from @pf_flow_connection where source=? and target=? and sourceType=? and targetType=? and flowId=?', [$source, $target, $sourceType, $targetType, $this->fid]);
        if ($row) {
            $this->error('连接重复');
        } else {
            $this->db->insert('@pf_flow_connection', ['name' => $name, 'condition' => $condition, 'source' => $source, 'target' => $target, 'sourceType' => $sourceType, 'targetType' => $targetType, 'flowId' => $this->fid]);
        }
        $this->success('连接成功', ['name' => $name, 'condition' => $condition, 'source' => $source, 'target' => $target, 'sourceType' => $sourceType, 'targetType' => $targetType]);
    }

    public function detachAction(Request $request, int $source, int $target, string $sourceType = '', string $targetType = '')
    {
        $this->context->setContentType('json');
        $this->db->delete('@pf_flow_connection', 'source=? and target=? and sourceType=? and targetType=? and flowId=?', [$source, $target, $sourceType, $targetType, $this->fid]);
        $this->success('删除成功');
    }

    public function dataAction(Request $request)
    {
        $place = $this->db->getList('select * from @pf_flow_place where flowId=?', $this->fid);
        $connection = $this->db->getList('select * from @pf_flow_connection where flowId=?', $this->fid);
        $transition = $this->db->getList('select * from @pf_flow_transition where flowId=?', $this->fid);
        $this->success('获取成功', ['place' => $place, 'connection' => $connection, 'transition' => $transition]);
    }

}