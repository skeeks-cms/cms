<?php
/**
 * «Добавить человека» of a company: the button and its window (a found person is linked,
 * a new one is created). Shared by the company card and the «Клиент» block of the deal card.
 *
 * @var $this yii\web\View
 * @var $model \skeeks\cms\models\CmsCompany
 * @var $accessReady bool «Сделка как услуга»: the access of the new person to the services
 */

use skeeks\cms\widgets\admin\CrmContactBlockWidget;
use yii\helpers\Html;

?>
                    <?php
                    $userClass = \Yii::$app->user->identityClass;
                    $newContact = new $userClass();
                    \skeeks\cms\admin\assets\JqueryMaskInputAsset::register($this);
                    $id = \yii\helpers\Html::getInputId($newContact, "phone");

                    $individualTipUrl = \yii\helpers\Url::to(['/cms/admin-cms-company/tip-users']);

                    $this->registerJs(<<<JS
$("#{$id}").mask("+7 999 999-99-99");

(function(sx, $, _)
{
    sx.classes.IndividualTip = sx.classes.Component.extend({
        _init: function() {
            this.on("tip", function() {
                
            });
        },
        
        _onDomReady: function()
        {
            var self = this;
            
            $("body").on("change", "#sx-add-child-contact #sx-user-id", function() {
                var jForm = $(this).closest("form");
                $(".sx-new-contractor-fields", jForm).fadeOut(400, function() {
                    $(this).remove();
                    jForm.submit();
                });
                
            });
            
            $("body").on("click", "#sx-add-child-contact .sx-btn-select", function() {
                var jForm = $(this).closest("form");
                $("#sx-user-id", jForm).val($(this).data("id")).trigger("change");
            });
            
            $("#sx-add-child-contact input").on("keyup", function() {
                 var data = $(this).closest("form").serializeArray();
                 var ajaxQuery = sx.ajax.preparePostQuery('{$individualTipUrl}', data);
                 var ajaxHandler = new sx.classes.AjaxHandlerStandartRespose(ajaxQuery);
                 ajaxHandler.on("success", function(e, data) {
                    if (data.data.htmlTip) {
                        $(".sx-tips-wrapper").show();
                        $(".sx-tips").empty().append(data.data.htmlTip);
                        $(".sx-button-wrapper").hide();
                    } else {
                        $(".sx-tips").empty();
                        $(".sx-tips-wrapper").hide();
                        $(".sx-button-wrapper").show();
                    }
                 });
                 ajaxQuery.execute();
                 
                 setTimeout(function() {
                     self.trigger("tip");
                 }, 1000);
            });
        }
    });
    
    new sx.classes.IndividualTip();
})(sx, sx.$, sx._);

JS
                    );

                    $widget = \yii\bootstrap\Modal::begin([
                        'header'       => $accessReady ? "Добавление человека" : "Добавление контакта",
                        'size'         => \yii\bootstrap\Modal::SIZE_DEFAULT,
                        'toggleButton' => [
                            'label' => CrmContactBlockWidget::icon('plus').($accessReady ? ' Добавить человека' : ' Добавить контакт'),
                            'class' => 'sx-button sx-button--secondary sx-button--sm sx-contact-add',
                        ],
                    ]); ?>

                    <?php
                    $form = \skeeks\cms\base\widgets\ActiveFormAjaxSubmit::begin([
                        'id'                     => "sx-add-child-contact",
                        'action'                 => \yii\helpers\Url::to(['/cms/admin-cms-company/add-user', 'pk' => $model->id]),
                        'enableAjaxValidation'   => false,
                        'enableClientValidation' => false,
                        'clientCallback'         => new \yii\web\JsExpression(<<<JS
        function (ActiveFormAjaxSubmit) {
            ActiveFormAjaxSubmit.on('success', function(e, response) {
                
                $(".modal").modal("hide");
                setTimeout(function() {
                    window.location.reload();
                }, 500);
                
            });
        }
JS
                        ),
                    ]); ?>
                    <?php if ($accessReady) : ?>
                        <?php
                        //Access of the new person: chosen before the person, because choosing a found person submits the form.
                        $activeDeals = \skeeks\cms\models\CmsDeal::find()->forManager()
                            ->andWhere([\skeeks\cms\models\CmsDeal::tableName().'.cms_company_id' => (int)$model->id, \skeeks\cms\models\CmsDeal::tableName().'.is_active' => 1])
                            ->orderBy([\skeeks\cms\models\CmsDeal::tableName().'.id' => SORT_DESC])->all();
                        $roles = \skeeks\cms\models\CmsDealRole::find()->andWhere(['is_active' => 1])->orderBy(['priority' => SORT_ASC])->all();
                        $marketer = null;
                        foreach ($roles as $role) {
                            if ($role->code === \skeeks\cms\models\CmsDealRole::CODE_MARKETER) {
                                $marketer = $role->id;
                            }
                        }
                        $this->registerJs(<<<JS
$('#sx-add-child-contact').on('change', '[name="sx-access"]', function () {
    $('#sx-add-child-contact .sx-new-access-deals').toggle(this.value !== 'full');
});
JS
                        );
                        ?>
                        <div class="form-group">
                            <label>Доступ к услугам компании</label>
                            <div><label style="font-weight: 400;"><input type="radio" name="sx-access" value="selected" checked> Только выбранные услуги</label></div>
                            <div><label style="font-weight: 400;"><input type="radio" name="sx-access" value="full"> Полный доступ ко всем услугам, включая будущие</label></div>
                        </div>
                        <div class="form-group sx-new-access-deals">
                            <?php if ($activeDeals) : ?>
                                <label>Выдать роль в услугах<?= count($activeDeals) > 10 ? ' <span class="sx-deal-access__muted">('.count($activeDeals).')</span>' : ''; ?></label>
                                <div class="sx-new-access-deals__list">
                                    <?php foreach ($activeDeals as $deal) : ?>
                                        <div><label style="font-weight: 400;"><input type="checkbox" name="sx-access-deals[]" value="<?= (int)$deal->id; ?>"> <?= Html::encode($deal->name); ?></label></div>
                                    <?php endforeach; ?>
                                </div>
                                <?= Html::dropDownList('sx-access-role', $marketer, \yii\helpers\ArrayHelper::map($roles, 'id', 'name'), ['class' => 'form-control', 'style' => 'margin-top: 6px;']); ?>
                                <div class="sx-deal-access__muted" style="margin-top: 4px;">Можно ничего не отмечать: человек будет в контактах без доступа к услугам.</div>
                            <?php else : ?>
                                <div class="sx-deal-access__muted">Активных сделок нет: человек будет в контактах без доступа к услугам.</div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <div class="sx-new-contractor-fields">
                        <?php echo $form->field($newContact, 'phone'); ?>
                        <?php echo $form->field($newContact, 'email'); ?>

                        <?php echo $form->field($newContact, 'last_name'); ?>
                        <?php echo $form->field($newContact, 'first_name'); ?>
                        <?php echo $form->field($newContact, 'patronymic'); ?>
                    </div>
                    <div style="display: none;">
                        <input type="text" name="sx-user-id" id="sx-user-id"/>
                    </div>

                    <div class="sx-button-wrapper" style="display: none;">
                        <button type="submit" class="btn btn-primary">Добавить</button>
                    </div>

                    <div class="sx-tips-wrapper" style="display: none; padding-top: 10px;">
                        <h5>Выберите контакт</h5>
                        <div class="row sx-tips"></div>
                    </div>

                    <?php $form::end(); ?>

                    <?php $widget::end(); ?>
