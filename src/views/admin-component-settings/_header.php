<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 27.03.2015
 *
 * @var $component \skeeks\cms\base\Component
 */
/* @var $this yii\web\View */

$this->registerCss(<<<CSS
li.divider
{
    height: 5px;
    margin: 0;
    background-color: #f8f9fa;
    border-top: 1px solid #d1d4d7;
    border-bottom: 1px solid #d1d4d7;
}
CSS
);

?>

<?
$clientSettings = [
    'remove' =>
        [
            'backend' => \yii\helpers\Url::to('remove') . "?" . http_build_query(\Yii::$app->request->get()),
        ],
    'cache' =>
        [
            'backend' => \yii\helpers\Url::to('cache') . "?" . http_build_query(\Yii::$app->request->get())
        ]
];

$componentSettingsJson = \yii\helpers\Json::encode($clientSettings);

$this->registerJs(<<<JS
(function(sx, $, _)
{
    sx.createNamespace('classes', sx);

    sx.classes.ComponentSettings = sx.classes.Component.extend({

        _init: function()
        {
            this.Remove     = new sx.classes.ComponentSettingsRemove(this.get('remove'));
            this.Cache      = new sx.classes.ComponentSettingsCache(this.get('cache'));
        },
    });

    /**
    * Удаление настроек
    */
    sx.classes.ComponentSettingsRemove = sx.classes.Component.extend({

        _init: function()
        {
            this.ajaxQuery      = sx.ajax.preparePostQuery(this.get('backend'));
            this.ajaxHandler    = new sx.classes.AjaxHandlerStandartRespose(this.ajaxQuery);

            this.ajaxHandler.bind('success', function(data, response)
            {
                window.location.reload();
            });
        },

        removeAll: function()
        {
            this.ajaxQuery.setData({
                'do': 'all'
            });

            this.ajaxQuery.execute();
        },

        removeDefault: function()
        {
            this.ajaxQuery.setData({
                'do': 'default'
            });

            this.ajaxQuery.execute();
        },

        removeSites: function()
        {
            this.ajaxQuery.setData({
                'do': 'sites'
            });

            this.ajaxQuery.execute();
        },

        removeUsers: function()
        {
            this.ajaxQuery.setData({
                'do': 'users'
            });

            this.ajaxQuery.execute();
        },

        removeBySite: function(site_code)
        {
            this.ajaxQuery.setData({
                'do': 'site',
                'code': site_code,
            });

            this.ajaxQuery.execute();
        },

        removeByUser: function(user_id)
        {
            this.ajaxQuery.setData({
                'do': 'user',
                'id': user_id,
            });

            this.ajaxQuery.execute();
        },
    });

    /**
    * Удаление настроек
    */
    sx.classes.ComponentSettingsCache = sx.classes.Component.extend({

        _init: function()
        {
            this.ajaxQuery      = sx.ajax.preparePostQuery(this.get('backend'));
            this.ajaxHandler    = new sx.classes.AjaxHandlerStandartRespose(this.ajaxQuery);
        },

        clearAll: function()
        {
            this.ajaxQuery.setData({
                'do': 'all'
            });

            this.ajaxQuery.execute();
        },
    });

    sx.ComponentSettings = new sx.classes.ComponentSettings({$componentSettingsJson});
})(sx, sx.$, sx._);
JS
);
?>

<!--<h1>Управление: <?php /*= $component->descriptor->name; */ ?></h1>
    <hr />-->
<div class="row">
    <div class="col-lg-2">
        <ul class="nav nav-pills nav-stacked">
            <li role="presentation"
                class="<?= in_array(\Yii::$app->controller->action->id, ['index']) ? "active" : "" ?>"><a
                        href="<?= \yii\helpers\Url::to('index') . "?" . http_build_query(\Yii::$app->request->get()); ?>">
                    <i class="glyphicon glyphicon-asterisk"></i> <?= \Yii::t('skeeks/cms', 'The default settings') ?>
                </a></li>

            <?php if (\skeeks\cms\models\CmsSite::find()->active()->count() > 1) : ?>
                <li role="presentation"
                    class="<?= in_array(\Yii::$app->controller->action->id, ['sites', 'site']) ? "active" : "" ?>"><a
                            href="<?= \yii\helpers\Url::to('sites') . "?" . http_build_query(\Yii::$app->request->get()); ?>">
                        <i class="glyphicon glyphicon-globe"></i> <?= \Yii::t('skeeks/cms', 'Sites settings') ?>
                    </a></li>
            <?php endif; ?>

            <li role="presentation"
                class="<?= in_array(\Yii::$app->controller->action->id, ['users', 'user']) ? "active" : "" ?>"><a
                        href="<?= \yii\helpers\Url::to('users') . "?" . http_build_query(\Yii::$app->request->get()); ?>">
                    <i class="glyphicon glyphicon-user"></i> <?= \Yii::t('skeeks/cms', 'Users settings') ?>
                </a></li>
            <!--<li role="presentation" class="<?php /*= \Yii::$app->controller->action->id == 'langs' ? "active" : ""*/ ?>"><a href="#">Настройки языков</a></li>-->
            <!--<li role="presentation"><a href="#">Настройки языков</a></li>-->
            <li class="divider"></li>
            <li role="presentation"
                class="<?= in_array(\Yii::$app->controller->action->id, ['cache']) ? "active" : "" ?>"><a
                        href="<?= \yii\helpers\Url::to('cache') . "?" . http_build_query(\Yii::$app->request->get()); ?>">
                    <i class="glyphicon glyphicon-retweet"></i> <?= \Yii::t('skeeks/cms', 'Clearing cache') ?></a>
            </li>

            <li role="presentation"
                class="<?= in_array(\Yii::$app->controller->action->id, ['remove']) ? "active" : "" ?>"><a
                        href="<?= \yii\helpers\Url::to('remove') . "?" . http_build_query(\Yii::$app->request->get()); ?>">
                    <i class="fa fa-times"></i> <?= \Yii::t('skeeks/cms', 'Remove{s}Recovery',
                        ['s' => '/']) ?></a>
            </li>

        </ul>
    </div>
    <div class="col-lg-10">