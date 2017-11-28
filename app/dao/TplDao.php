<?php

namespace app\dao;

use Yii;


/**
 * This is the model class for table "app_top_white".
 */
class TplDao extends MasterDao
{
    public static $table = 'tpl';

    public static function getDb()
    {
        return Yii::$app->get(self::$dbname);
    }

    public static function tableName()
    {
        return self::$table;
    }
}
