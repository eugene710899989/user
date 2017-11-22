<?php
/**
 * Created by PhpStorm.
 * User: 80084073
 * Date: 17-3-7
 * Time: 下午2:47
 */
namespace app\dao;

use Yii;
use yii\base\NotSupportedException;
use yii\web\IdentityInterface;

class UserDao extends MasterDao implements IdentityInterface
{
    public static $table = 'user';
    public static $command = array();
    public static $_instance;
    public $password_hash;
    public $auth_key;
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

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->getPrimaryKey();
    }

    /**
     * @inheritdoc
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * @inheritdoc
     */
    public static function findIdentity($id)
    {
        return UserDao::getInstance()->getOneMWhere(['id' => $id, 'status' => self::STATUS_ACTIVE], '', UserDao::tableName());
    }

    public function validatePassword($password)
    {
        $this->setPassword($this->password);
        return Yii::$app->security->validatePassword($password, $this->password_hash);
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
    public function setPassword($password)
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }


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

}