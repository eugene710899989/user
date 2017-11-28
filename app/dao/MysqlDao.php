<?php
namespace app\dao;

use common\models\CommonModel;
use Yii;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

class MysqlDao extends ActiveRecord
{

    public function saveOne($id, $info, $table, $sign = false)
    {
        $res = $this->command()->update($table, $info, 'id=:id', array(':id' => $id))->execute();
        /*  if (!$sign) {
              $this->createSign($table, "id='$id'");
          }*/
        return $res;

    }

    public function addOne($info, $table)
    {
        $this->command()->insert($table, $info)->execute();
        $id = $this->getDb()->getLastInsertID();
        //$this->createSign($table, "id='$id'");
        return $id;
    }

    public function delOne($id, $table)
    {
        $res = $this->command()->delete($table, 'id=:id', array(':id' => $id))->execute();
        return $res;

    }

    public function delWhere($where, $table)
    {
        $where = self::filterWhere($where);
        $res = $this->command()->delete($table, $where)->execute();
        return $res;

    }

    public function getOne($id, $field = '*', $table)
    {
        $info = self::find()->select($field)->from($table)->where('id=:id', array(':id' => $id))->asArray()->one();
        return $info;
    }

    public function getOneM($id, $field = '*', $table)
    {
        $info = self::find()->select($field)->from($table)->where('id=:id', array(':id' => $id))->one();
        return $info;
    }

    public function getOneMWhere($where, $field = '*', $table)
    {
        $where = self::filterWhere($where);
        $info = self::find()->select($field)->from($table)->where($where)->one();
        return $info;
    }

    public function getOneWhere($where, $field = '*', $table)
    {
        $where = self::filterWhere($where);
        $info = self::find()->select($field)->from($table)->where($where)->asArray()->one();
        return $info;

    }

    public function getList($start, $limit, $table, $order = '', $where = '', $field = '*')
    {
        $where = self::filterWhere($where);
        $data = self::find()->select($field)->from($table)->
        where($where)->orderBy($order)->limit($limit)->offset($start)->asArray()->all();
        return $data;
    }

    public function getLimitList($table, $where = '1=1', $field = '*', $limit = 10)
    {
        $where = self::filterWhere($where);
        $data = self::find()->select($field)->from($table)->
        where($where)->limit($limit)->offset(0)->asArray()->all();
        return $data;
    }

    public function getWhereList($table, $where = '1=1', $field = '*')
    {
        $where = self::filterWhere($where);
        $data = self::find()->select($field)->from($table)->
        where($where)->asArray()->all();
        return $data;
    }

    public function getDistinctList($table, $where = '1=1', $field = '*', $limit = 10)
    {
        $where = self::filterWhere($where);
        $data = self::find()->select($field)->distinct()->from($table)->
        where($where)->limit($limit)->offset(0)->asArray()->all();
        return $data;
    }


    public function getWhereOrderList($table, $where = '1=1', $order = '', $field = '*')
    {
        $where = self::filterWhere($where);
        $data = self::find()->select($field)->from($table)->
        where($where)->orderBy($order)->asArray()->all();
        return $data;
    }

    public function getCount($table, $where = '1=1')
    {
        $where = self::filterWhere($where);
        $num = self::find()->from($table)->where($where)->count();
        return $num;

    }

    public function getGroupCount($table, $where = '1=1', $group = 'position')
    {
        $where = self::filterWhere($where);
        $num = self::find()->from($table)->where($where)->groupBy($group)->count();
        return $num;

    }

    public function getGroup($table, $where = '1=1', $group = 'position')
    {
        $where = self::filterWhere($where);
        $res = self::find()->from($table)->where($where)->groupBy($group);
        return $res;
    }

    public function getGroupList($table, $where = '1=1', $field = '*', $group = 'position')
    {
        $where = self::filterWhere($where);
        $res = self::find()->select($field)->from($table)->where($where)->groupBy($group)->asArray()->all();
        return $res;

    }

    public function getGroupListPage($table, $where = '1=1', $field = '*', $group = 'position', $order = '')
    {
        $sort = Yii::$app->request->post('sort', 'id');
        $sord = Yii::$app->request->post('order', 'desc');
        if (empty($order)) {
            $order = $sort . ' ' . $sord;
        }
        $start = (int)Yii::$app->request->post('offset', 0);
        $limit = (int)Yii::$app->request->post('limit', 20);
        $where = self::filterWhere($where);
        $res['rows'] = self::find()->select($field)->from($table)->where($where)->orderBy($order)->groupBy($group)->limit($limit)->offset($start)->asArray()->all();
        $total = $this->getGroupCount($table, $where, $group);
        $res['total'] = $total;
        return $res;
    }

    public function saveOneWhere($info, $where, $table)
    {
        $where = self::filterWhere($where);
        $res = $this->command()->update($table, $info, $where)->execute();
        // $this->createSign($table, $where);
        return $res;
    }

    public function getGridList($table, $wheres = '1=1', $order = '', $field = '*')
    {
        $sidx = Yii::$app->request->get('sidx', 'id');
        $sord = Yii::$app->request->get('sord', 'desc');
        if (!empty($sidx) && !empty($sord)) {
            $order = $sidx . ' ' . $sord . $order;
        }
        $page = (int)Yii::$app->request->get('page', 1);
        $rows = (int)Yii::$app->request->get('rows', 20);
        $start = $page <= 1 ? 0 : ($page - 1) * $rows;
        $wheres = self::filterWhere($wheres);
        $info['rows'] = $this->getList($start, $rows, $table, $order, $wheres, $field);
        $total = $this->getCount($table, $wheres);
        $info['page'] = $page;
        $info['total'] = (int)($total / $rows) + ($total % $rows > 0 ? 1 : 0);
        $info['records'] = $total;
        return $info;
    }

    public function getBootstrapList($table, $wheres = '1=1', $field = '*', $order = '')
    {

        $sort = Yii::$app->request->post('sort', 'id');
        $sord = Yii::$app->request->post('order', 'desc');
        if (empty($order)) {
            $order = $sort . ' ' . $sord;
        }
        $start = (int)Yii::$app->request->post('offset', 0);
        $limit = (int)Yii::$app->request->post('limit', 20);
        $wheres = self::filterWhere($wheres);
        $info['rows'] = $this->getList($start, $limit, $table, $order, $wheres, $field);
        $total = $this->getCount($table, $wheres);
        $info['total'] = $total;
        return $info;
    }

     public function getLeftJoinList($t, $wheres = '1=1', $field = '*', $t2, $on, $t3 = '', $on2 = '')
    {
        $sort = Yii::$app->request->post('sort', $t . '.id');
        $sord = Yii::$app->request->post('order', 'desc');
        $start = (int)Yii::$app->request->post('offset', 0);
        $limit = (int)Yii::$app->request->post('limit', 20);
        $order = $sort . ' ' . $sord;
        $wheres = self::filterWhere($wheres);
        if ($t3 && $on2) {
            $info['rows'] = self::find()->select($field)->from($t)
                ->leftJoin($t2, $on)
                ->leftJoin($t3, $on2)
                ->where($wheres)
                ->orderBy($order)
                ->limit($limit)
                ->offset($start)
                ->asArray()
                ->all();
            $info['total'] = self::find()->select($field)
                ->from($t)
                ->leftJoin($t2, $on)
                ->leftJoin($t3, $on2)
                ->where($wheres)
                ->count();
        } else {
            $info['rows'] = self::find()->select($field)->from($t)->leftJoin($t2, $on)->where($wheres)
                ->orderBy($order)
                ->limit($limit)
                ->offset($start)
                ->asArray()
                ->all();
            $info['total'] = self::find()->select($field)->from($t)->leftJoin($t2, $on)->where($wheres)->count();
        }
        return $info;
    }

    private static function filterWhere($where)
    {
        if (!is_array($where)) {
            $where = preg_replace(['/\\bupdate\\b/i', '/\\bdelete\\b/i', '/\\binsert\\b/i'], ['', '', ''], $where);
        }
        return $where;
    }



}