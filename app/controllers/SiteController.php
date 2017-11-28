<?php
namespace app\controllers;

use app\dao\TplDao;
use app\models\Email;
use common\controllers\BasicController;
use common\models\CommonModel;
use Yii;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use common\models\LoginForm;
use yii\web\User;

/**
 * Site controller
 */
class SiteController extends BasicController
{

    public $script_names = [];

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

    public function actionTpl()
    {
        if ($this->_ext == 'json' || Yii::$app->request->method == 'POST') {
            $this->asJson([]);
            $this->_ext = 'json';
            if (!empty($_POST['type'])) {
                if ($_POST['type'] == 'get_tpl') {
                    $this->doc['tpl_info'] = Email::get_tpl();
                } elseif ($_POST['type'] == 'delete') {
                    Email::tpl_del();
                    $this->doc['error'] = Email::$error;
                }
            } else {
                Email::tpl_save();
                $this->doc['error'] = Email::$error;
            }
        } else {
            $this->script_names =
                [
                    '/assets/scripts/email_tpl.js',
                    "/assets/summernote/summernote.js",
                    '/assets/summernote/lang/summernote-zh-CN.js',
                ];
            $tpl = TplDao::getInstance()->getWhereList("tpl");
            $format_tpl = [];
            if (!empty($tpl)) {
                $format_tpl = ArrayHelper::map($tpl, 'id', 'tpl_name');
            }
            return $this->render('tpl', ['tpls' => $format_tpl, 'error' => Email::$error]);
        }
    }

    /**
     * Login action.
     *
     * @return string
     */
    public function actionLogin()
    {
        $user = CommonModel::getUser();
        if ($user) {
            return $this->goHome();
        }
        $model = new LoginForm();
        if (!$user && Yii::$app->request->isPost && Yii::$app->request->post()) {
            $model->setAttributes(Yii::$app->request->post());
            if ($model->login()) {
                return $this->goHome();
            }
        }
        return $this->render('login', ['model' => $model]);
    }

    /**
     * Logout action.
     *
     * @return string
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }
}
