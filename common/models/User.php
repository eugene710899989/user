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
use yii\base\Model;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\helpers\ArrayHelper;


class User extends Model
{
    const STATUS_DELETED = 0;
    const STATUS_ACTIVE = 10;

    public static function checkLogin()
    {
        $params = Common::params();
        $userInfo = CdoAdminDao::getInstance()->getOneWhere("user_id='{$params['user_id']}' and password='" . md5($params['password']) . "'", '*', 'user');
        Yii::$app->session->set('userInfo', $userInfo);
        Yii::$app->session->set('region', 'CN');
        Yii::$app->session->set('lang', 'zh');
        if (empty($userInfo)) {
            throw new CommonException(CommonErrMap::ERROR_NAME_PASSWORD);
        }
        return $userInfo;
    }

    //设置用户信息
    public static function setLoginInfo($userId)
    {
        $userInfo = CdoAdminDao::getInstance()->getOneWhere("user_id='$userId' and valid_state=1", '*', 'user');
        Yii::$app->session->set('userInfo', $userInfo);
        Yii::$app->session->set('region', 'CN');
        Yii::$app->session->set('lang', 'zh');
        if (empty($userInfo)) {
            return false;
        }
        return $userInfo;
    }

    public static function findByUsername($username)
    {
        return UserDao::getInstance()->getOneMWhere(["username" => $username, 'status' => '10'], '*', 'user');
    }

    /**
     * 获取用户信息 菜单 权限
     **/
    public static function setUserInfo($userId)
    {
        $where = 'user_id="' . $userId . '" and valid_state=1';
        $roleList = CdoAdminDao::getInstance()->getWhereList('user_role', $where, 'user_id,role_id');
        if (empty($roleList)) {
            $roleList = [['user_id' => $userId, 'role_id' => 0]];
        }
        $roleIds = implode(',', ArrayHelper::getColumn($roleList, 'role_id'));
        $rightInfo = CdoAdminDao::getInstance()->getWhereList('role_menu', "role_id in($roleIds) and valid_state=1", 'role_id,menu_id,m_oper');
        //合并多个角色权限
        $rightDetail = [];
        $menuIds = [0];
        foreach ($rightInfo as $item) {
            if (isset($rightDetail[$item['menu_id']])) {
                $rightDetail[$item['menu_id']]['m_oper'] = ($rightDetail[$item['menu_id']]['m_oper']) | ($item['m_oper']);
            } else {
                $rightDetail[$item['menu_id']] = $item;
            }
            $menuIds[] = $item['menu_id'];
        }

        $menuInfo = CdoAdminDao::getInstance()->getWhereList('menu', 'id in(' . implode(',', $menuIds) . ') and valid_state=1', '*');
        $menuInfo = ArrayUtil::mergeTwoArray($menuInfo, $rightDetail, 'id', 'menu_id');
        $whereMenu = 'valid_state=1';
        $menu_arr = CdoAdminDao::getInstance()->getWhereOrderList('menu', $whereMenu, 'sequ', '*');
        $menu = ArrayUtil::mergeTwoArray($menu_arr, $menuInfo, 'id', 'menu_id');
        $menu_tree = [];
        foreach ($menu as $one) {
            if (isset($one['m_oper'])) {
                if ($one['parentid'] == 0) {
                    $menu_tree [$one['sys_module']][$one['id']]['name'] = $one['name'];
                    $menu_tree [$one['sys_module']][$one['id']]['icon'] = $one['icon'];
                } else {
                    $menu_tree [$one['sys_module']][$one['parentid']]['children'][] = $one;
                    Yii::$app->redis->hmset(Yii::$app->session->getId(), $one['url'], base_convert($one['m_oper'], 2, 10));
                }
            } else {
                Yii::$app->redis->hmset(Yii::$app->session->getId(), $one['url'], 0);
            }
        }
        Yii::$app->redis->hmset(Yii::$app->session->getId(), 'menu', json_encode($menu_tree));
        Yii::$app->redis->expire(Yii::$app->session->getId(), 10000);
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

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['status', 'default', 'value' => self::STATUS_ACTIVE],
            ['status', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_DELETED]],
        ];
    }
    public static function findIdentity($id)
    {
        return UserDao::getInstance()->getOneMWhere(['id' => $id, 'status' => self::STATUS_ACTIVE], '', UserDao::tableName());
    }


    /**
     * @inheritdoc
     */

    /**
     * Finds user by username
     *
     * @param string $username
     * @return static|null
     */

    /**
     * Finds user by password reset token
     *
     * @param string $token password reset token
     * @return static|null
     */
    public static function findByPasswordResetToken($token)
    {
        if (!static::isPasswordResetTokenValid($token)) {
            return null;
        }

        return UserDao::getInstance()->getOneMWhere([
            'password_reset_token' => $token,
            'status' => self::STATUS_ACTIVE,
        ], '*', UserDao::tableName());
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
