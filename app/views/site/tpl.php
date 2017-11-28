<?php
use yii\helpers\Html;

$this->title = 'Email-Tpl';
?>
<style>
    .input-group{
        margin:20px 0;
    }
</style>
<div class="page-header">
    <h2>
        <i class="icon-double-angle-right"></i>
        邮件模板管理
    </h2>
</div>
<div class="row">
    <div class="col-xs-12">
        <div class="tabbable">

            <div class="clearfix">
                <input type="text" class="form-control hidden" id="tpl_id" readonly="true" style="width:200px;">
                <div class="col-xs-12 input-group" id="tpl_select">
                    <span class="input-group-addon">选择模板</span>
                    <select class="form-control email-change" id="resource_tpl" onchange="tpl_change('resource_tpl')" style="width:200px;">
                        <option value="">请选择</option>
                        <?php if (!empty($tpls)) {
                            foreach ($tpls as $id => $tpl_name) {
                                echo "<option value='{$id}'>{$tpl_name}</option>\n";
                            }
                        } ?>
                    </select>
                    <button class="btn" onclick="tpl.new()" style="height:30px; margin-left:20px">新增</button>
                    <button class="btn" onclick="tpl.delete()" style="height:30px; margin-left:20px">删除</button>
                </div>
                <div class="col-xs-12 input-group hidden" id="tpl_edit">
                    <span class="input-group-addon">模板名称</span>
                    <input type="text" class="form-control" id="tpl_name" style="width:200px;" name="tpl_name">
                    <button class="btn" onclick="tpl_select()" style="height:30px; margin-left:20px">返回</button>
                </div>
                <div class="col-xs-12 input-group">
                    <span class="input-group-addon">邮件主题</span>
                    <input type="text" class="form-control" id="tpl_subject" name="subject">
                </div>
            </div>
            <div class="summernote" id="tpl_content"></div>
            <button class="btn btn-info btn-block" onclick="tpl_save('resource_tpl')" id="save">保存</button>

        </div>
    </div>
</div>

<!-- Javascript -->