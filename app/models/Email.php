<?php

namespace app\models;

use app\dao\TplDao;
use Yii;
use yii\base\Model;
use common\models\CommonModel;
use yii\helpers\ArrayHelper;

use yii\swiftmailer\Mailer;

class Email extends Model
{

    public static $error = '';

    public static function tpl_save()
    {
        $params = Yii::$app->request->bodyParams;
        if (empty($params['tpl_name']) || empty($params['content'])) {
            self::$error = '模板名称或内容不能为空';
            return false;
        }
        $save = [];
        if (isset($params['content'])) {
            $save['content'] = $params['content'];
            $regex = "/\[![A-Za-z0-9_-]+]/";
            preg_match_all($regex, $params['content'], $regs);
            if (count($regs[0]) > 0) {
                $save['params_search'] = implode(',', $regs[0]);
            }

        }
        $save['tpl_name'] = $params['tpl_name'];
        $save['subject'] = $params['subject'];
        if (!empty($params['tpl_id'])) {
            $save['update_time'] = date('Y-m-d H:i:s');
            TplDao::getInstance()->saveOne($params['tpl_id'], $save, 'tpl');
        } else {
            $save['create_time'] = date('Y-m-d H:i:s');
            if (TplDao::getInstance()->getOneWhere("tpl_name='{$params['tpl_name']}'", '', 'tpl')) {
                self::$error = '模板名称已经存在';
                return false;
            }
            TplDao::getInstance()->addOne($save, 'tpl');
        }
        return true;
    }

    public static function tpl_del(){
        $params = Yii::$app->request->bodyParams;
        if (!empty($params['tpl_id'])) {
            TplDao::getInstance()->delOne($params['tpl_id'], 'tpl');
        } else {
            self::$error = '错误，空的数据';
        }
        return true;
    }

    public static function get_tpl()
    {
        $params = Yii::$app->request->bodyParams;
        if(!empty($params['tpl_id'])){
            $tpl = TplDao::getInstance()->getOne($params['tpl_id'],'','tpl');
            return $tpl;
        }else{
            return [];
        }
    }

    public static function send_mail()
    {
        $params = Yii::$app->request->bodyParams;

        if (!empty($params['dev_ids']) && !empty($params['subject']) && !empty($params['content']) && !empty($params['type'])) {
            if ($params['type'] == 1) {
                $params['dev_ids'] = ResMain::forMsgFormat($params['dev_ids']);
                $devs = CdoOpenPlatformDao::getInstance()->getWhereList('dev_company_info', "dev_id in ({$params['dev_ids']})", 'contact_email,company_name');
                $remark = '相关企业 ' . implode(',', array_unique(array_filter(ArrayHelper::getColumn($devs, 'company_name'))));

            } elseif ($params['type'] == 2) {
                $params['dev_ids'] = ResMain::forMsgFormat($params['dev_ids']);
                $apps = CdoOpenResourceDao::getInstance()->getWhereList('app_base', "app_id in ({$params['dev_ids']})", 'app_id,dev_id');
                if ($apps) {
                    $base_ext = CdoOpenResourceDao::getInstance()->getWhereList('app_base_ext', "app_id in ({$params['dev_ids']})",
                        'app_id,business_username,business_email,business_mobile');

                    $app_version = AuditCommon::getLastOrOnlineVersion("app_id in ({$params['dev_ids']})");
                    if ($app_version) {
                        $version_ids = implode(',', array_filter(ArrayHelper::getColumn($app_version, 'version_id')));
                        $lang = CommonModel::getRegionLang();
                        $names = CdoOpenResourceDao::getInstance()->getWhereList('intl_version', "version_id in ({$version_ids}) and lang='{$lang}'", 'app_name');
                        $remark = '相关应用 ' . implode(',', array_filter(ArrayHelper::getColumn($names, 'app_name')));
                    } else {
                        $remark = '相关应用 app_id ' . $params['dev_ids'];
                    }
                    $dev_ids = implode(',', array_unique(array_filter(ArrayHelper::getColumn($apps, 'dev_id'))));
                    $devs = CdoOpenPlatformDao::getInstance()->getWhereList('dev_company_info', "dev_id in ({$dev_ids})", 'contact_email');
                } else {
                    throw new ResourceException(ResourceErrMap::ERROR_APP_DATA_LOST);
                }
            } elseif ($params['type'] == 3) {
                $params['dev_ids'] = ResMain::forMsgFormat($params['dev_ids']);
                $remark = '邮箱 ' . $params['dev_ids'];
            } else {
                throw new ResourceException(ResourceErrMap::ERROR_OPERATION);
            }

            $transaction = CdoOpenPlatformDao::getDb()->beginTransaction();
            try {
                if ($params['type'] == 1 || $params['type'] == 2) {
                    $params['content'] = str_replace('\"', '', $params['content']);
                    if (!empty($devs)) {
                        $address_origin = implode(',', array_filter(ArrayHelper::getColumn($devs, 'contact_email')));
                        $biz_emails = [];
                        if (!empty($base_ext)) {
                            $biz_original = implode(',', array_filter(ArrayHelper::getColumn($base_ext, 'business_email')));
                            if (!empty($biz_original)) {
                                $address_origin = $biz_original;
                                foreach ($base_ext as $ext) {
                                    $biz_emails[] = Enc::all(["business_username", "business_email", "business_mobile"], $ext, 'del');
                                }
                            }
                        }
                        $record_save = CommonModel::initNewData([
                            'type' => 1,
                            'content' => $params['content'],
                            'target' => $address_origin,
                            'subject' => $params['subject'],
                            'remark' => $remark
                        ]);
                        foreach ($devs as &$dev) {
                            $dev = Enc::all(["contact_email"], $dev, 'del');
                        }
                        $address = implode(',', array_filter(ArrayHelper::getColumn($devs, 'contact_email')));
                        if (!empty($biz_emails)) {
                            $address = implode(',', array_filter(ArrayHelper::getColumn($biz_emails, 'business_email')));
                        }
                    } else {
                        throw new ResourceException(ResourceErrMap::ERROR_DEV_NOT_FOUND);
                    }
                } elseif ($params['type'] == 3) {
                    $record_save = CommonModel::initNewData([
                        'type' => 1,
                        'content' => $params['content'],
                        'target' => $params['dev_ids'],
                        'subject' => $params['subject'],
                        'remark' => $remark
                    ]);
                    $address = $params['dev_ids'];
                } else {
                    throw new ResourceException(ResourceErrMap::ERROR_OPERATION);
                }
                if (!empty($params['files'])) {
                    $record_save['attachment'] = implode(',', array_filter($params['files']));
                }
                $emails = array_filter(array_unique(explode(',', $address)));
                $record_id = CdoOpenPlatformDao::getInstance()->addOne($record_save, 'msg_record');
                $detail_save = CommonModel::initNewData(['record_id' => $record_id, 'type' => 1]);
                foreach ($emails as $email) {
                    $detail_save['email'] = $email;
                    CdoOpenPlatformDao::getInstance()->addOne($detail_save, 'msg_detail');
                }
                $transaction->commit();
            } catch
            (\Exception $e) {
                $transaction->rollBack();
                throw new ResourceException(ResourceErrMap::ERROR_MAIL_FAIL, $e->getMessage() . $e->getTraceAsString());
            }

        }
    }

    public static function send($temple_id, $subject, $address, $param)
    {
        $params = [
            'subject' => $subject,
            'email_tpl_id' => $temple_id,
            'address' => $address,
            'params' => $param
        ];
        $url = Yii::$app->params['mail_url'];
        $re = ApiDao::curl_post($url, $params, false);
    }

    public static function sendDirect($subject, $mail_body, $mail_addresses)
    {
        if (empty($mail_addresses)) {
            return false;
        }
        if (is_array($mail_addresses)) {
            $mail_addresses = implode(',', $mail_addresses);
        }
        $params = [
            'subject' => $subject,
            'address' => $mail_addresses,
            'body' => $mail_body
        ];
        $url = Yii::$app->params['mail_direct_url'];
        return ApiDao::curl_post($url, $params, false);
    }
}