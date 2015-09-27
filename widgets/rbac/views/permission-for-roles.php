<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 25.08.2015
 */
/* @var $this yii\web\View */
/* @var $widget \skeeks\cms\widgets\rbac\PermissionForRoles */
?>
<div id="<?= $widget->id; ?>" class="form-group">
    <? if ($widget->label): ?>
        <label><?= $widget->label; ?></label>
    <? endif;  ?>

    <?/*= \yii\helpers\Html::checkboxList(
        'sx-permission-' . $widget->permissionName,
        $widget->permissionRoles,
        \yii\helpers\ArrayHelper::map(\Yii::$app->authManager->getRoles(), 'name', 'description')
    ); */?>
    <?= \skeeks\widget\chosen\Chosen::widget([
        'multiple'          => true,
        'name'              => 'sx-permission-' . $widget->permissionName,
        'value'             => $widget->permissionRoles,
        'items'             => $widget->items
    ]); ?>

    <? $this->registerJs(<<<JS
    (function(sx, $, _)
    {
        sx.classes.PermissionForRoles = sx.classes.Component.extend({

            getJQueryWrapper: function()
            {
                return $('#' + this.get('id'));
            },

            getJQuerySelect: function()
            {
                return $('select', this.getJQueryWrapper());
            },

            _onDomReady: function()
            {
                var self = this;

                this.getJQuerySelect().on('change', function()
                {
                    self.save();
                });
            },

            save: function()
            {
                //sx.block(this.jQueryWrapper);
                this.AjaxQuery = sx.ajax.preparePostQuery(this.get('backend', ''), {
                    'permissionName'    : this.get('permissionName'),
                    'roles'             : this.getJQuerySelect().val()
                });

                new sx.classes.AjaxHandlerStandartRespose(this.AjaxQuery, {
                    'blocker'       : new sx.classes.Blocker(this.getJQueryWrapper()),
                    'enableBlocker' : true
                });

                new sx.classes.AjaxHandlerNoLoader(this.AjaxQuery);

                this.AjaxQuery.execute();
            }
        });

        new sx.classes.PermissionForRoles({$widget->getClientOptionsJson()});
    })(sx, sx.$, sx._);
JS
)?>
</div>
