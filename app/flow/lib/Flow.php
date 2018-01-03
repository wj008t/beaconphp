<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/31
 * Time: 15:53
 */

namespace app\flow\lib {

    use beacon\DB;
    use beacon\HttpContext;
    use beacon\Request;
    use beacon\Utils;

    class FlowException extends \Exception
    {

    }

    class Flow
    {

        //创建任务
        public static function create(HttpContext $context, int $taskId, string $name = '', array $data)
        {
            $db = $context->getDataBase();
            self::valid($data);
            try {
                $db->beginTransaction();
                //数据库行锁
                $db->update('@pf_flow_list', ['name' => $db->sql('name')], 'name=?', $name);
                $flow = $db->getRow('select * from @pf_flow_list where name=?', $name);
                if ($flow == null) {
                    throw new FlowException('没有找到对应的工作流程');
                }
                $place = $db->getRow("select * from @pf_flow_place where flowid=? and mode=1", $flow['id']);
                if ($place == null) {
                    throw new FlowException('没有找到工作流程的起始库所');
                }
                $token = [
                    'flowId' => $flow['id'],
                    'placeId' => $place['id'],
                    'state' => $place['state'],
                    'taskId' => $taskId,
                    'userId' => $data['userId'],
                    'createId' => $data['userId'],
                    'targetId' => $data['targetId'],
                    'targetGroupId' => $data['targetGroupId'],
                    'data' => $data,
                    'addTime' => date('Y-m-d H:i:s'),
                    'updateTime' => date('Y-m-d H:i:s'),
                ];
                $db->insert('@pf_flow_token', $token);
                $tokenId = $db->lastInsertId();
                self::createTimeout($context, $tokenId);
                $db->commit();
                $data = array_replace($token['data'], [
                    'tokenId' => $tokenId,
                    'taskId' => $taskId,
                    'state' => $token['state'],
                    'userId' => $token['userId'],
                    'targetId' => $token['targetId'],
                    'targetGroupId' => $token['targetGroupId']
                ]);
                return $data;
            } catch (FlowException $e) {
                $db->rollBack();
                throw $e;
            } catch (\Exception $e) {
                $db->rollBack();
                var_export($e);
                throw new FlowException('执行创建错误');
            }
        }

        //准备处理
        public static function reday(HttpContext $context, int $tokenId, $branch = '', array $args = [])
        {
            $db = $context->getDataBase();
            if ($tokenId == 0) {
                throw new FlowException('执行失败，任务Token不存在');
            }
            if (empty($branch)) {
                throw new FlowException('执行失败，没有指定执行的分支');
            }
            $args['userId'] = isset($args['userId']) ? $args['userId'] : $context->getSession('userId');
            if (empty($args['userId'])) {
                $args['userId'] = 0;
            }
            $args['groupId'] = isset($args['groupId']) ? $args['groupId'] : $context->getSession('groupId');
            if (empty($args['groupId'])) {
                $args['groupId'] = 0;
            }
            //锁任务ID
            $db->update('@pf_flow_token', ['id' => $db->sql('id')], $tokenId);
            if ($args['userId'] != 0 || $args['groupId'] != 0) {
                $token = $db->getRow('select * from @pf_flow_token where id=? and (targetId=? or targetGroupId=?)', [$tokenId, $args['userId'], $args['groupId']]);
            } else if (isset($args['timeout']) && isset($args['condition']) && isset($args['sign']) && $args['timeout'] > 0 && $args['timeout'] < time()) {
                $token = $db->getRow('select * from @pf_flow_token where id=?', [$tokenId]);
                if ($token != null) {
                    $flow = $db->getRow('select `key` from @pf_flow_list where id=?', $token['flowId']);
                    if ($flow == null) {
                        throw new FlowException('执行失败，缺少执行参数');
                    }
                    $sign = md5(md5($flow['key']) . md5($args['condition'] . '|' . $tokenId . '|' . $args['timeout'] . '|' . $branch));
                    if ($sign != $args['sign']) {
                        throw new FlowException('执行失败，签名校验失败');
                    }
                }
            } else {
                throw new FlowException('执行失败，缺少执行参数' . var_export($args, true));
            }
            if ($token == null) {
                throw new FlowException('执行失败，要求的身份不符');
            }
            $item = $db->getRow('select B.id,B.code,B.timeout,B.url,B.timeoutCondition from @pf_flow_connection A,@pf_flow_transition B where A.flowId=? and A.sourceType=? and A.source=? and  A.target=B.id and B.flowId=A.flowId and B.code=? limit 0,1', [$token['flowId'], 'place', $token['placeId'], $branch]);
            if ($item == null) {
                throw new FlowException('执行失败，任务Token没有可发射的事件');
            }
            $temp = [];
            $conditionList = $db->getList('select * from @pf_flow_connection where flowId=? and sourceType=? and source=?', [$token['flowId'], 'transition', $item['id']]);
            if (count($conditionList) == 0) {
                throw new FlowException('执行失败，并不存在相应的目标分支');
            }
            foreach ($conditionList as $xitem) {
                $place = $db->getRow('select id,code,name from @pf_flow_place where flowId=? and id=?', [$token['flowId'], $xitem['target']]);
                if ($place == null) {
                    throw new FlowException('执行失败，业务工作流错误，可能已经删除了对应的库所');
                }
                $temp[$xitem['condition']] = ['placeId' => $place['id'], 'placeName' => $place['name'], 'placeCode' => $place['code']];
            }
            if (is_string($token['data']) && Utils::isJsonString($token['data'])) {
                $token['data'] = json_decode($token['data'], true);
            }
            if (!is_array($token['data'])) {
                $token['data'] = [];
            }
            $data = array_replace($token['data'], [
                'tokenId' => $tokenId,
                'taskId' => $token['taskId'],
                'state' => $token['state'],
                'userId' => $token['userId'],
                'targetId' => $token['targetId'],
                'targetGroupId' => $token['targetGroupId'],
                'condition' => $temp,
            ]);
            return $data;
        }

        //触发分支
        public static function fire(HttpContext $context, int $tokenId, $branch = '', $connection, array $data)
        {
            $db = $context->getDataBase();
            self::valid($data);
            $token = $db->getRow('select * from @pf_flow_token where id=?', $tokenId);
            if ($token == null) {
                throw new FlowException('执行失败，任务Token不存在');
            }
            $connecList = $db->getList('select * from @pf_flow_connection where flowId=? and sourceType=? and source=?', [$token['flowId'], 'place', $token['placeId']]);
            if (count($connecList) == 0) {
                throw new FlowException('执行错误，任务Token没有可发射的事件');
            }
            //目标库所
            $targetPlace = null;
            if (count($connecList) == 1) {
                $item = $connecList[0];
                $transition = $db->getRow('select id,code from @pf_flow_transition where flowId=? and id=?', [$token['flowId'], $item['target']]);
                if ($transition == null) {
                    throw new FlowException('执行错误，业务工作流错误，可能已经删除了对应的业务事件');
                }
                $condition = $db->getRow('select * from @pf_flow_connection where flowId=? and sourceType=? and source=? and `condition`=?', [$token['flowId'], 'transition', $transition['id'], $connection]);
                if ($condition == null) {
                    throw new FlowException('执行错误，为找到相对于的条件分支');
                }
                $place = $db->getRow('select id,state,code,`name`,mode from @pf_flow_place where flowId=? and id=?', [$token['flowId'], $condition['target']]);
                if ($place == null) {
                    throw new FlowException('执行错误，业务工作流错误，可能已经删除了对应的库所');
                }
                $targetPlace = $place;
            } else {
                if (empty($branch)) {
                    throw new FlowException('执行错误，没有指定要执行的分支路径');
                }
                foreach ($connecList as $item) {
                    $transition = $db->getRow('select id,code from @pf_flow_transition where flowId=? and id=?', [$token['flowId'], $item['target']]);
                    if ($transition == null) {
                        throw new FlowException('执行错误，业务工作流错误，可能已经删除了对应的业务事件');
                    }
                    if ($transition['code'] != $branch) {
                        continue;
                    }
                    $condition = $db->getRow('select * from @pf_flow_connection where flowId=? and sourceType=? and source=? and `condition`=?', [$token['flowId'], 'transition', $transition['id'], $connection]);
                    if ($condition == null) {
                        throw new FlowException('执行错误，为找到相对于的条件分支');
                    }
                    $place = $db->getRow('select id,state,code,`name`,mode from @pf_flow_place where flowId=? and id=?', [$token['flowId'], $condition['target']]);
                    if ($place == null) {
                        throw new FlowException('执行错误，业务工作流错误，可能已经删除了对应的库所');
                    }
                    $targetPlace = $place;
                }
            }
            if ($targetPlace === null) {
                throw new FlowException('执行错误，没有找到对应条件的库所');
            }
            if (is_string($token['data']) && Utils::isJsonString($token['data'])) {
                $token['data'] = json_decode($token['data'], true);
            }
            if (!is_array($token['data'])) {
                $token['data'] = [];
            }
            $data = array_replace($token['data'], $data);
            $vals = [
                'placeId' => $targetPlace['id'],
                'state' => $targetPlace['state'],
                'userId' => $data['userId'],
                'createId' => $data['userId'],
                'targetId' => $data['targetId'],
                'targetGroupId' => $data['targetGroupId'],
                'data' => $data,
                'updateTime' => date('Y-m-d H:i:s'),
            ];
            $db->update('@pf_flow_token', $vals, $tokenId);
            if ($targetPlace['mode'] != 2) {
                self::createTimeout($context, $tokenId);
            } else {
                $db->delete('@pf_flow_queue', 'tokenId=?', $tokenId);
            }
            $data = array_replace($vals['data'], [
                'tokenId' => $tokenId,
                'taskId' => $token['taskId'],
                'state' => $vals['state'],
                'userId' => $vals['userId'],
                'targetId' => $vals['targetId'],
                'targetGroupId' => $vals['targetGroupId'],
            ]);
            return $data;
        }

        //删除工作流
        public static function delete(HttpContext $context, int $taskId, string $name = '')
        {
            $db = $context->getDataBase();
            $flow = $db->getRow('select * from @pf_flow_list where name=?', $name);
            if ($flow == null) {
                return;
            }
            $token = $db->getRow('select * from @pf_flow_token where flowId=? and taskId=?', [$flow['id'], $taskId]);
            if ($token == null) {
                return;
            }
            $db->delete('@pf_flow_queue', 'tokenId=?', $token['id']);
            $db->delete('@pf_flow_token', $token['id']);
        }

        //获取令牌
        public static function getToken(HttpContext $context, int $taskId, string $name = '', $branch = '', array $args = [])
        {
            $db = $context->getDataBase();
            if (empty($branch)) {
                return 0;
            }
            $flow = $db->getRow('select * from @pf_flow_list where name=?', $name);
            if ($flow == null) {
                return 0;
            }
            $args['userId'] = isset($args['userId']) ? $args['userId'] : $context->getSession('userId');
            if (empty($args['userId'])) {
                $args['userId'] = 0;
            }
            $args['groupId'] = isset($args['groupId']) ? $args['groupId'] : $context->getSession('groupId');
            if (empty($args['groupId'])) {
                $args['groupId'] = 0;
            }
            $token = $db->getRow('select * from @pf_flow_token where flowId=? and taskId=? and (targetId=? or targetGroupId=?)', [$flow['id'], $taskId, $args['userId'], $args['groupId']]);
            if ($token == null) {
                return 0;
            }
            //是否可发射的Token
            $item = $db->getRow('select B.id,B.code,B.timeout,B.url,B.timeoutCondition from @pf_flow_connection A,@pf_flow_transition B where A.flowId=? and A.sourceType=? and A.source=? and  A.target=B.id and B.flowId=A.flowId and B.code=? limit 0,1', [$flow['id'], 'place', $token['placeId'], $branch]);
            if ($item == null) {
                return 0;
            }
            return $token['id'];
        }

        //验证
        private static function valid(&$data)
        {
            if (!isset($data['userId'])) {
                throw new FlowException('数据必须指定发起者用户ID {userId}');
            }
            if (!isset($data['targetId']) && !isset($data['targetGroupId'])) {
                throw new FlowException('数据必须指定触发者用户ID {targetId} 或者分组id {targetGroupId}');
            }
            if (!isset($data['targetGroupId'])) {
                $data['targetGroupId'] = 0;
            }
            if (!isset($data['targetId'])) {
                $data['targetId'] = 0;
            }
        }

        /**
         * 创建定时任务
         * @param int $tokenId
         */
        private static function createTimeout(HttpContext $context, int $tokenId)
        {
            $db = $context->getDataBase();
            $db->delete('@pf_flow_queue', 'tokenId=?', $tokenId);
            $token = $db->getRow('select * from @pf_flow_token where id=?', $tokenId);
            if ($token == null) {
                throw new FlowException('执行错误，任务Token不存在');
            }
            $flow = $db->getRow('select * from @pf_flow_list where id=?', $token['flowId']);
            if ($flow == null) {
                throw new FlowException('执行错误，不存在的工作流');
            }
            $item = $db->getRow('select B.id,B.code,B.timeout,B.url,B.timeoutCondition from @pf_flow_connection A,@pf_flow_transition B where A.flowId=? and A.sourceType=? and A.source=? and  B.timeout >0 and A.target=B.id and B.flowId=A.flowId order by B.timeout asc limit 0,1', [$token['flowId'], 'place', $token['placeId']]);
            if ($item !== null) {
                $vals = [
                    'tokenId' => $tokenId,
                    'flowId' => $flow['id'],
                    'url' => $flow['gateway'] . $item['url'],
                    'timeout' => time() + $item['timeout'],
                    'branch' => $item['code'],
                    'condition' => (($item['timeoutCondition'] === null || $item['timeoutCondition'] === '') ? '' : $item['timeoutCondition']),
                ];
                $db->insert('@pf_flow_queue', $vals);
            }
        }


    }
}