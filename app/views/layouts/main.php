<?php

/* @var $this \yii\web\View */
/* @var $content string */

use app\assets\AppAsset;
use common\models\CommonModel;
use yii\helpers\Html;
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use yii\widgets\Breadcrumbs;
use common\widgets\Alert;
AppAsset::register($this);

?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
    <link rel="stylesheet" href="/assets/bootstrap/css/bootstrap.css">
    <link rel="stylesheet" href="/assets/layer/skin/layer.css">
    <link rel="stylesheet" href="/assets/summernote/summernote.css">
    <link rel="stylesheet" href="/assets/summernote/summernote-bs4.css">
    <link rel="shortcut icon" href="/assets/ico/favicon.png">
    <script src="/assets/js/jquery-3.2.1.min.js"></script>
    <script src="/assets/layer/layer.min.js"></script>
    <script src="/assets/bootstrap/js/bootstrap.js"></script>

</head>
<body>
<?php $this->beginBody() ?>

<div class="wrap">
    <?php
    NavBar::begin([
        'brandLabel' => 'Email Plat',
        'brandUrl' => Yii::$app->homeUrl,
        'options' => [
            'class' => 'navbar-inverse navbar-fixed-top',
        ],
    ]);
    $menuItems = [
        ['label' => 'Home', 'url' => ['/site/index']],
    ];
    if (!CommonModel::getUser()) {
        $menuItems[] = ['label' => 'Login', 'url' => ['/site/login']];
    } else {
        $menuItems[] = ['label' => 'Tpl', 'url' => ['/site/tpl']];
        $menuItems[] = '<li>'
            . Html::beginForm(['/site/logout'], 'post')
            . Html::submitButton(
                'Logout (' . Yii::$app->user->identity->username . ')',
                ['class' => 'btn btn-link logout']
            )
            . Html::endForm()
            . '</li>';
    }
    echo Nav::widget([
        'options' => ['class' => 'navbar-nav navbar-right'],
        'items' => $menuItems,
    ]);
    NavBar::end();
    ?>

    <div class="container">
        <?= Breadcrumbs::widget([
            'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
        ]) ?>
        <?= Alert::widget() ?>
        <?= $content ?>
    </div>
</div>

<?php $this->endBody() ?>
<?php if (!empty($this->context->script_names)) {
    foreach ($this->context->script_names as $s_name) {
        echo '<script src="' . $s_name . '"></script>';
    }
} ?>
</body>

</html>
<?php $this->endPage() ?>
