<?php
/**
 * Created by PhpStorm.
 * User: 80084073
 * Date: 17-3-7
 * Time: 下午2:47
 */
namespace app\dao;

use Yii;

class MasterDao extends MysqlDao
{
    public static $dbname = 'master';

    public static $command = array();
    public static $_instance;


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

    public function getComponent()
    {
        return self::$dbname;
    }

}