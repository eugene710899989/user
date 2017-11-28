<?php
/**
 * Created by PhpStorm.
 * User: 80084073
 * Date: 17-3-17
 * Time: 下午9:09
 */
namespace common\controllers;

//use common\components\exception\admin\AdminErrMap;
//use common\components\exception\admin\AdminException;
//use common\components\log\Clog;
//use common\dao\CdoAdminDao;
//use common\dao\CurlServiceDao;
//use common\extensions\Common;
use app\dao\UserDao;
use common\models\CommonModel;
//use admin\modules\workflow\models\Workflow;
use Yii;
use yii\web\Controller;

//use common\components\exception\common\CommonErrMap;
//use common\components\exception\common\CommonException;

class BasicController extends Controller
{
    public $template = '';

    public $tpl_type = '.php';

    public $doc = [];

    public $oper = '';

    public $_ext = '';

    public $_url = '';

    public function beforeAction($action)
    {
        Yii::$app->language = 'zh_CN';
        if ((!strstr($action->id, 'login') && !strstr($action->id, 'home') && !strstr($action->id, 'index')) && empty(CommonModel::getUser())) {
            return $this->redirect('/site/login');
        }
        if (strpos($action->id, '.json') !== false) {
            $this->_ext = 'json';
        }

//        Yii::$app->redis->expire(Yii::$app->session->getId(),Yii::$app->params['redis_expire_seconds']);
        $this->_url = '/' . Yii::$app->id . Yii::$app->request->get('r');
        if (parent::beforeAction($action)) {
            return true;
        }
        return false;
    }

    public function afterAction($action, $result)
    {
        $this->doc['_url'] = $this->_url;
        $this->doc['_search'] = array_merge(Yii::$app->request->queryParams, Yii::$app->request->bodyParams);
        $this->doc['language'] = Yii::$app->language;
        if ($this->_ext == 'json') {
            $this->asJson($this->doc);
        } else {
            return parent::afterAction($action, $result);
        }
    }


}