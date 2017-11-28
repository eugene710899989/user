<?php
/**
 * Created by PhpStorm.
 * User: 80084073
 * Date: 17-3-7
 * Time: 下午2:47
 */
namespace app\dao;

use common\models\CommonModel;
use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\base\InvalidValueException;
use yii\base\NotSupportedException;
use yii\web\Cookie;
use yii\web\ForbiddenHttpException;
use yii\web\IdentityInterface;
use yii\web\UserEvent;

class UserDao extends MasterDao
{
    public static $table = 'user';
    public static $command = array();
    public static $_instance;
    public $password_hash;

    private $_access = [];
    private $_identity = false;


    const STATUS_DELETED = 0;
    const STATUS_ACTIVE = 10;

    public static function getDb()
    {
        return Yii::$app->get(self::$dbname);
    }

    public static function getInstance()
    {
        if (!(self::$_instance instanceof self)) {
            self::$_instance = new self;
        }
        return self::$_instance;
    }

    public function command()
    {
        try {
            return $this->getDb()->createCommand();
        } catch (\Exception $e) {

        }
    }

    public static function tableName()
    {
        return self::$table;
    }

    public function getComponent()
    {
        return self::$dbname;
    }

    public function validatePassword($password)
    {
        $this->setPassword($this->password);
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    public function setPassword($password)
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }
}