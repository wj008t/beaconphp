<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/31
 * Time: 15:53
 */

namespace app\flow\lib {

    use beacon\DB;
    use beacon\Request;
    use beacon\Utils;

    class FlowException extends \Exception
    {
        const ERROR = 1; //执行错误
        const NOT_FOUND_FLOW = 2; //缺少工作流
        const NOT_FOUND_PLACE = 3; //缺失库所
        const NOT_FOUND_TOKEN = 4; //丢失TOKEN
        const NOT_FOUND_BRANCH = 5; //丢失分支，一般是任务过期会发生
        const EXPIRED_BRANCH = 6;//过期的分支
        const MISSING_TOKEN = 7;//丢失了TOKEN
        const MISSING_BRANCH = 8;//丢失了分支
        const NOT_FOUND_CONDITION_BRANCH = 9;//没有找到条件对应的分支
        const NOT_FOUND_CONDITION_PLACE = 10;//没有找到条件对应的库所
        const MISSING_ARGS = 11; //缺少参数
        const FAILED_SIGN = 12; //签名失败
        const FAILED_AUTH = 13; //授权失败
        const FAILED_TIMEOUT = 14; //未设置超时时间
    }

    class Flow
    {

        //创建任务
        public static function create(int $taskId, string $name = '', array $data)
        {
            self::valid($data);
            try {
                DB::beginTransaction();
                //数据库行锁
                DB::update('@pf_flow_list', ['name' => DB::sql('name')], 'name=?', $name);
                $flow = DB::getRow('select * from @pf_flow_list where name=?', $name);
                if ($flow == null) {
                    throw new FlowException('没有找到对应的工作流程', FlowException::NOT_FOUND_FLOW);
                }
                $place = DB::getRow("select * from @pf_flow_place where flowid=? and mode=1", $flow['id']);
                if ($place == null) {
                    throw new FlowException('没有找到工作流程的起始库所', FlowException::NOT_FOUND_PLACE);
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
                DB::insert('@pf_flow_token', $token);
                $tokenId = DB::lastInsertId();
                self::createTimeout($tokenId);
                DB::commit();
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
                DB::rollBack();
                throw $e;
            } catch (\Exception $e) {
                DB::rollBack();
                var_export($e);
                throw new FlowException('执行创建错误', FlowException::ERROR);
            }
        }

        //准备处理
        public static function reday(int $taskId = 0, string $name = '', $branch = '', $userId = 0, $groupId = 0)
        {
            $request = Request::instance();
            $args = [];
            $exeType = $request->post('exeType:s', 'hander');
            //超时执行
            if ($exeType == 'timeout') {
                $args['timeout'] = $request->post('timeout:i', 0);
                if ($args['timeout'] <= 0) {
                    throw new FlowException('执行失败，延时未设置', FlowException::FAILED_TIMEOUT);
                }
                $args['condition'] = $request->post('condition:i', 0);
                $args['sign'] = $request->post('sign:s', '');
                $tokenId = $request->post('tokenId:i', 0);
                $branch = $request->post('branch:s', '');
                $args['userId'] = 0;
                $args['groupId'] = 0;
            } else {
                //手动执行
                $taskId = $request->param('taskId');
                $args['userId'] = $userId;
                $args['groupId'] = $groupId;
                $args['condition'] = $request->param('condition:i', 1);
                $tokenId = Flow::getToken($taskId, $name, $branch, $args);
                $exeType = 'hander';
            }

            if ($tokenId == 0) {
                throw new FlowException('执行失败，任务Token不存在', FlowException::MISSING_TOKEN);
            }

            if (empty($branch)) {
                throw new FlowException('执行失败，没有指定执行的分支', FlowException::MISSING_BRANCH);
            }
            //锁任务ID
            DB::update('@pf_flow_token', ['id' => DB::sql('id')], $tokenId);

            if ($args['userId'] != 0 || $args['groupId'] != 0) {
                //查找可以支持的令牌
                $token = DB::getRow('select * from @pf_flow_token where id=? and (targetId=? or targetGroupId=?)', [$tokenId, $args['userId'], $args['groupId']]);
                if ($token == null) {
                    throw new FlowException('执行失败，要求的身份不符', FlowException::FAILED_AUTH);
                }
            } else if (isset($args['timeout']) && isset($args['condition']) && isset($args['sign']) && $args['timeout'] > 0 && $args['timeout'] < time()) {
                $token = DB::getRow('select * from @pf_flow_token where id=?', [$tokenId]);
                if ($token == null) {
                    throw new FlowException('执行失败，没有找到对应的令牌', FlowException::NOT_FOUND_TOKEN);
                }
                $flow = DB::getRow('select `key` from @pf_flow_list where id=?', $token['flowId']);
                if ($flow == null) {
                    throw new FlowException('执行失败，对应的工作流可能已被删除', FlowException::NOT_FOUND_FLOW);
                }
                $sign = md5(md5($flow['key']) . '|' . $exeType . '|' . md5($args['condition'] . '|' . $tokenId . '|' . $args['timeout'] . '|' . $branch));
                if ($sign != $args['sign']) {
                    throw new FlowException('执行失败，签名校验失败', FlowException::FAILED_SIGN);
                }
            } else {
                throw new FlowException('执行失败，缺少用户身份对应的userid或者groupId参数' . var_export($args, true), FlowException::MISSING_ARGS);
            }
            //查询在线分支(根据源库所及连线查询目标连线对应的变迁)
            $trans = DB::getRow('select B.id,B.code,B.timeout,B.url,B.timeoutCondition from @pf_flow_connection A,@pf_flow_transition B where A.flowId=? and A.sourceType=? and A.source=? and  A.target=B.id and B.flowId=A.flowId and B.code=? limit 0,1', [$token['flowId'], 'place', $token['placeId'], $branch]);
            if ($trans == null) {
                //如果查不出来，正常情况下是任务已经过期
                throw new FlowException('执行失败，任务Token没有可用的请求分支', FlowException::EXPIRED_BRANCH);
            }
            $temp = [];
            $connectionList = DB::getList('select * from @pf_flow_connection where flowId=? and sourceType=? and source=?', [$token['flowId'], 'transition', $trans['id']]);
            if (count($connectionList) == 0) {
                throw new FlowException('执行失败，并不存在相应的目标分支', FlowException::NOT_FOUND_BRANCH);
            }
            foreach ($connectionList as $connect) {
                $place = DB::getRow('select id,code,name from @pf_flow_place where flowId=? and id=?', [$token['flowId'], $connect['target']]);
                if ($place == null) {
                    throw new FlowException('执行失败，业务工作流错误，可能已经删除了对应的库所', FlowException::NOT_FOUND_PLACE);
                }
                $temp[$connect['condition']] = ['placeId' => $place['id'], 'placeName' => $place['name'], 'placeCode' => $place['code']];
            }
            if (is_string($token['data']) && Utils::isJsonString($token['data'])) {
                $token['data'] = json_decode($token['data'], true);
            }
            if (!is_array($token['data'])) {
                $token['data'] = [];
            }
            $data = array_replace($token['data'], [
                'exeType' => $exeType,
                'tokenId' => $tokenId,
                'taskId' => $token['taskId'],
                'state' => $token['state'],
                'userId' => $token['userId'],
                'targetId' => $token['targetId'],
                'targetGroupId' => $token['targetGroupId'],
                'conditionItems' => $temp,
                'condition' => $args['condition'],
            ]);
            return $data;
        }

        //触发分支
        public static function fire(int $tokenId, $branch = '', $condition, array $data)
        {
            self::valid($data);
            $token = DB::getRow('select * from @pf_flow_token where id=?', $tokenId);
            if ($token == null) {
                throw new FlowException('执行失败，任务Token不存在', FlowException::NOT_FOUND_TOKEN);
            }
            //查询在线分支(根据源库所及连线查询目标连线对应的变迁)
            $trans = DB::getRow('select B.id,B.code,B.timeout,B.url,B.timeoutCondition from @pf_flow_connection A,@pf_flow_transition B where A.flowId=? and A.sourceType=? and A.source=? and  A.target=B.id and B.flowId=A.flowId and B.code=? limit 0,1', [$token['flowId'], 'place', $token['placeId'], $branch]);
            if ($trans == null) {
                //如果查不出来，正常情况下是任务已经过期
                throw new FlowException('执行失败，任务Token没有可用的请求分支', FlowException::EXPIRED_BRANCH);
            }
            //查询分支
            $connectList = DB::getList('select * from @pf_flow_connection where flowId=? and sourceType=? and source=?', [$token['flowId'], 'transition', $trans['id']]);
            if (count($connectList) == 0) {
                throw new FlowException('执行错误，为找到任何条件分支', FlowException::NOT_FOUND_CONDITION_BRANCH);
            }
            $place = null;
            if (count($connectList) == 1) {
                $connect = $connectList[0];
                $place = DB::getRow('select id,state,code,`name`,mode from @pf_flow_place where flowId=? and id=?', [$token['flowId'], $connect['target']]);
                if ($place == null) {
                    throw new FlowException('执行错误，业务工作流错误，可能已经删除了对应的库所', FlowException::NOT_FOUND_CONDITION_PLACE);
                }
            } else {
                foreach ($connectList as $connect) {
                    if ($connect['condition'] != $condition) {
                        continue;
                    }
                    $place = DB::getRow('select id,state,code,`name`,mode from @pf_flow_place where flowId=? and id=?', [$token['flowId'], $connect['target']]);
                    if ($place == null) {
                        throw new FlowException('执行错误，业务工作流错误，可能已经删除了对应的库所', FlowException::NOT_FOUND_CONDITION_PLACE);
                    }
                    break;
                }
            }
            if ($place == null) {
                throw new FlowException('执行错误，业务工作流错误，可能已经删除了对应的库所', FlowException::NOT_FOUND_CONDITION_PLACE);
            }
            if (is_string($token['data']) && Utils::isJsonString($token['data'])) {
                $token['data'] = json_decode($token['data'], true);
            }
            if (!is_array($token['data'])) {
                $token['data'] = [];
            }
            $data = array_replace($token['data'], $data);
            $vals = [
                'placeId' => $place['id'],
                'state' => $place['state'],
                'userId' => $data['userId'],
                'createId' => $data['userId'],
                'targetId' => $data['targetId'],
                'targetGroupId' => $data['targetGroupId'],
                'data' => $data,
                'updateTime' => date('Y-m-d H:i:s'),
            ];
            DB::update('@pf_flow_token', $vals, $tokenId);
            if ($place['mode'] != 2) {
                self::createTimeout($tokenId);
            } else {
                DB::delete('@pf_flow_queue', 'tokenId=?', $tokenId);
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
        public static function delete(int $taskId, string $name = '')
        {
            $flow = DB::getRow('select * from @pf_flow_list where name=?', $name);
            if ($flow == null) {
                return;
            }
            $token = DB::getRow('select * from @pf_flow_token where flowId=? and taskId=?', [$flow['id'], $taskId]);
            if ($token == null) {
                return;
            }
            DB::delete('@pf_flow_queue', 'tokenId=?', $token['id']);
            DB::delete('@pf_flow_token', $token['id']);
        }

        //获取令牌
        public static function getToken(int $taskId, string $name = '', $branch = '', array $args = [])
        {
            if (empty($branch)) {
                return 0;
            }
            $flow = DB::getRow('select * from @pf_flow_list where name=?', $name);
            if ($flow == null) {
                return 0;
            }
            $args['userId'] = isset($args['userId']) ? $args['userId'] : Request::instance()->getSession('userId');
            if (empty($args['userId'])) {
                $args['userId'] = 0;
            }
            $args['groupId'] = isset($args['groupId']) ? $args['groupId'] : Request::instance()->getSession('groupId');
            if (empty($args['groupId'])) {
                $args['groupId'] = 0;
            }
            $token = DB::getRow('select * from @pf_flow_token where flowId=? and taskId=? and (targetId=? or targetGroupId=?)', [$flow['id'], $taskId, $args['userId'], $args['groupId']]);
            if ($token == null) {
                return 0;
            }
            //是否可发射的Token
            $item = DB::getRow('select B.id,B.code,B.timeout,B.url,B.timeoutCondition from @pf_flow_connection A,@pf_flow_transition B where A.flowId=? and A.sourceType=? and A.source=? and  A.target=B.id and B.flowId=A.flowId and B.code=? limit 0,1', [$flow['id'], 'place', $token['placeId'], $branch]);
            if ($item == null) {
                return 0;
            }
            return $token['id'];
        }

        //验证
        private static function valid(&$data)
        {
            if (!isset($data['userId'])) {
                throw new FlowException('数据必须指定发起者用户ID {userId}', FlowException::FAILED_AUTH);
            }
            if (!isset($data['targetId']) && !isset($data['targetGroupId'])) {
                throw new FlowException('数据必须指定触发者用户ID {targetId} 或者分组id {targetGroupId}', FlowException::FAILED_AUTH);
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
        private static function createTimeout(int $tokenId)
        {
            DB::delete('@pf_flow_queue', 'tokenId=?', $tokenId);
            $token = DB::getRow('select * from @pf_flow_token where id=?', $tokenId);
            if ($token == null) {
                throw new FlowException('执行错误，任务Token不存在', FlowException::NOT_FOUND_TOKEN);
            }
            $flow = DB::getRow('select * from @pf_flow_list where id=?', $token['flowId']);
            if ($flow == null) {
                throw new FlowException('执行错误，不存在的工作流', FlowException::NOT_FOUND_FLOW);
            }
            $item = DB::getRow('select B.id,B.code,B.timeout,B.url,B.timeoutCondition from @pf_flow_connection A,@pf_flow_transition B where A.flowId=? and A.sourceType=? and A.source=? and  B.timeout >0 and A.target=B.id and B.flowId=A.flowId order by B.timeout asc limit 0,1', [$token['flowId'], 'place', $token['placeId']]);
            if ($item !== null) {
                $vals = [
                    'tokenId' => $tokenId,
                    'flowId' => $flow['id'],
                    'url' => $flow['gateway'] . $item['url'],
                    'timeout' => time() + $item['timeout'],
                    'branch' => $item['code'],
                    'condition' => (($item['timeoutCondition'] === null || $item['timeoutCondition'] === '') ? '' : $item['timeoutCondition']),
                ];
                DB::insert('@pf_flow_queue', $vals);
            }
        }


    }
}