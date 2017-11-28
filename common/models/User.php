<?php
/**
 * Created by PhpStorm.
 * User: 80074590
 * Date: 2017-04-12
 * Time: 14:52
 */
namespace common\models;

//use resource\exception\ResourceErrMap;
//use resource\exception\ResourceException;
//use resource\dao\AppResourceDao;
use app\dao\MasterDao;
use app\dao\UserDao;
use Yii;

//use common\dao\CdoAdminDao;
//use common\extensions\ArrayUtil;
//use common\extensions\Common;
//use resource\dao\CdoCheckDao;
use yii\behaviors\TimestampBehavior;
use yii\web\IdentityInterface;


class User extends MasterDao implements IdentityInterface
{
    public static $table = 'user';
    public static $command = array();
    public static $_instance;
    const STATUS_DELETED = 0;
    const STATUS_ACTIVE = 10;
    public $password_hash;
    public $auth_key;


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

    public static function findByUsername($username)
    {
        return self::getInstance()->getOneMWhere(["username" => $username, 'status' => '10'], '*', 'user');
    }


    public function getId()
    {
        return $this->id;
    }

    /**
     * @inheritdoc
     */
    public function getAuthKey()
    {
        return $this->auth_key;
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


    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::className(),
        ];
    }

    public static function findIdentity($id)
    {
//        print_r($id);exit;
        return self::getInstance()->getOneMWhere(['id' => strval($id), 'status' => strval(self::STATUS_ACTIVE)], '', self::tableName());
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        throw new NotSupportedException('"findIdentityByAccessToken" is not implemented.');
    }

    /**
     * Generates password hash from password and sets it to the model
     *
     * @param string $password
     */


    /**
     * Generates "remember me" authentication key
     */
    public function generateAuthKey()
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }

    /**
     * Generates new password reset token
     */
    public function generatePasswordResetToken()
    {
        $this->password_reset_token = Yii::$app->security->generateRandomString() . '_' . time();
    }

    /**
     * Removes password reset token
     */
    public function removePasswordResetToken()
    {
        $this->password_reset_token = null;
    }

    public static function findByPasswordResetToken($token)
    {
        if (!static::isPasswordResetTokenValid($token)) {
            return null;
        }

        return self::getInstance()->getOneMWhere([
            'password_reset_token' => $token,
            'status' => self::STATUS_ACTIVE,
        ], '*', self::tableName());
    }

    /**
     * Finds out if password reset token is valid
     *
     * @param string $token password reset token
     * @return bool
     */
    public static function isPasswordResetTokenValid($token)
    {
        if (empty($token)) {
            return false;
        }

        $timestamp = (int)substr($token, strrpos($token, '_') + 1);
        $expire = Yii::$app->params['user.passwordResetTokenExpire'];
        return $timestamp + $expire >= time();
    }


    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return bool if password provided is valid for current user
     */

}
