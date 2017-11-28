<?php
/**
 * Created by PhpStorm.
 * User: wjs57y
 * Date: 17/3/18
 *
 * Time: 下午3:56
 */
namespace common\models;

use Yii;

class CommonModel
{

    //返回字典数组
    public static function getDictArrByKey($key)
    {
        $dict = CdoAdminDao::getInstance()->getOneWhere('dict_key="' . $key . '"', 'content', 'config_dict');
        $result = [];
        if ($dict) {
            $section = explode(';', $dict['content']);
            foreach ($section as $sec) {
                $pair = explode(':', $sec);
                $value = isset($pair[1]) ? $pair[1] : $pair[0];
                $result[$pair[0]] = Yii::t('utility-dict', $value);
            }

        }
        return $result;
    }

    //返回字典字符串
    public static function getDictStrByKey($key)
    {
        $dict = CdoAdminDao::getInstance()->getOneWhere('dict_key="' . $key . '"', 'content', 'config_dict');
        return Yii::t('utility-dict', $dict['content']);
    }

    public static function addInstance($url)
    {
        if (empty($url)) {
            return array();
        }
        $where = "url = \"{$url}\"";
        $bconf = CdoWorkflowDao::getInstance()->getOneWhere($where, '*', 'workflow_bconf');
        if (empty($bconf)) {
            return array();
        }
        $where = "workflow_id = {$bconf['workflow_id']} and node_sequ=1";
        $node = CdoWorkflowDao::getInstance()->getOneWhere($where, '*', 'workflow_node');
        $roles = CdoWorkflowDao::getInstance()->getWhereList('workflow_role', "node_id={$node['node_id']}", '*');
        $roleid_arr = ArrayUtil::getArrayValue($roles, 'operator_id');
        $rolename_arr = ArrayUtil::getArrayValue($roles, 'operator_name');
        $readers = CdoWorkflowDao::getInstance()->getOne($bconf['workflow_id'], 'readers', 'workflow');
        $info['workflow_id'] = $bconf['workflow_id'];
        $info['current_node_id'] = $node['node_id'];
        $info['deal_user_id'] = implode(';', $roleid_arr);
        $info['deal_user_name'] = implode(';', $rolename_arr);
        $info['title'] = $bconf['title'];
        $info['url'] = $url;
        $info['params'] = json_encode(Yii::$app->request->post());
        $info['create_time'] = $info['update_time'] = date('Y-m-d H:i:s');
        $info['create_operator_id'] = Yii::$app->session['userInfo']['user_id'];
        $info['create_operator'] = $info['update_operator'] = Yii::$app->session['userInfo']['real_name'];
        $info['readers'] = $readers['readers'] ? $readers['readers'] : '';
        return CdoWorkflowDao::getInstance()->addOne($info, 'workflow_instance');

    }

    public static function getOneConf($url)
    {
        if (empty($url)) {
            return array();
        }
        return CdoWorkflowDao::getInstance()->getOneWhere("url='$url'", '*', 'workflow_bconf');
    }

    public static function whereFormat($params, $col_list)
    {
        $where = '';
        if (empty($col_list)) {
            return '';
        }
        foreach ($col_list as $col) {
            if (isset($params[$col]) && $params[$col] != '') {
                $where .= " and {$col}='{$params[$col]}'";
            }
        }
        return $where;
    }

    public static function getUser(){
        if(empty(Yii::$app->getSession()->get(Yii::$app->user->idParam))){
            return false;
        }
        $user = User::findIdentity(Yii::$app->getSession()->get(Yii::$app->user->idParam));
        return $user;
    }

    public static function formatCol($model_data, $filter)
    {
        if (!is_array($model_data) || !is_array($filter) || empty($model_data) || empty($filter)) {
            return [];
        }
        $result = [];
        foreach ($model_data as $col_name => $row) {
            if (in_array($col_name, $filter)) {
                $result[$col_name] = $row;
            }
        }
        return $result;
    }

    public static function filterSubmit($info, $typeList)
    {
        if (!is_array($info) || !is_array($typeList)) {
            return $info;
        }
        foreach ($info as $key => &$item) {
            if (!is_array($item)) {
                $item = trim($item);
                //$item = preg_replace("/[\n\r]/", '', $item);
            }
            if (isset($typeList[$key])) {
                if (is_numeric($typeList[$key])) {
                    self::filterCount($item, $typeList[$key]);
                } else {
                    $func = 'filter' . $typeList[$key];
                    self::$func($item);
                }
            }
        }
        return $info;
    }

    public static function filterApp($item)
    {
        self::filterEmpty($item);
        $isHas = CdoResourceDao::getInstance()->getCount('app_online_version', 'app_id=' . $item . ' and state=1');
        if ($isHas == 0) {
            throw new  CommonException(CommonErrMap::ERROR_APP_NOT_EXIST);
        }
    }

    public static function filterEmpty($item)
    {
        if ($item === '') {
            throw new  CommonException(CommonErrMap::ERROR_EMPTY);
        }
    }

    public static function initNewData($input_data = [])
    {
        $data['create_time'] = date('Y-m-d H:i:s');
        $data['create_operator'] = Yii::$app->session['userInfo']['real_name'];
        if ($input_data) {
            return array_merge($data, $input_data);
        }
        return $data;
    }

    public static function initUpdateData()
    {
        $data['update_time'] = date('Y-m-d H:i:s');
        $data['update_operator'] = Yii::$app->session['userInfo']['real_name'];
        return $data;
    }

    public static function filterCount($item, $num)
    {
        if (mb_strlen($item) > $num) {
            throw new  CommonException(CommonErrMap::ERROR_TOO_LONG);
        }
    }

    public static function addUploadLog($item, $reason)
    {
        $userInfo = CdoAdminDao::getInstance()->getOneWhere("real_name='{$item['create_operator']}'", 'user_id', 'user');
        $log = [
            'url' => Yii::$app->controller->_url,
            'oper' => 'upload',
            'querystring' => json_encode($item),
            'userid' => $userInfo['user_id'],
            'username' => $item['create_operator'],
            'create_time' => date('Y-m-d H:i:s')
        ];
        $logId = CdoAdminDao::getInstance()->addOne($log, 'log');
        $operation = [
            'user_id' => $userInfo['user_id'],
            'user_name' => $item['create_operator'],
            'url' => Yii::$app->controller->_url,
            'create_time' => date('Y-m-d H:i:s'),
            'log_id' => $logId,
            'app_id' => Yii::$app->request->post('app_id', 0),
            'operation' => $reason,
            'reason' => Yii::$app->controller->_operation_reason,
            'check_id' =>  isset(Yii::$app->params['check_id']) ? Yii::$app->params['check_id'] : 0
        ];
        isset($item['app_id']) ? $operation['app_id'] = $item['app_id'] : '';
        CdoAdminDao::getInstance()->addOne($operation, 'operation');
    }
}
