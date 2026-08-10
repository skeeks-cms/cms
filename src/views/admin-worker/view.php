<?php
use skeeks\cms\backend\widgets\BackendEntityLink;
use skeeks\cms\backend\widgets\BackendSurfaceWidget;
use yii\helpers\Html;

/* @var $model \skeeks\cms\models\CmsUser */
/* @var $this yii\web\View */
/* @var $controller \skeeks\cms\backend\controllers\BackendModelController */
/* @var $action \skeeks\cms\backend\actions\BackendModelCreateAction|\skeeks\cms\backend\actions\IHasActiveForm */
/* @var $model \common\models\User */
$controller = $this->context;
$action = $controller->action;
$model = $action->model;


$isCanEdit = \Yii::$app->user->can("cms/admin-user/manage", ['model' => $model]);

$jsData = \yii\helpers\Json::encode([
    'backend' => \yii\helpers\Url::to(['update-attribute', 'pk' => $model->id]),
]);

$this->registerJs(<<<JS



(function(sx, $, _)
{
    $("body").on("click", ".sx-send-sms-trigger", function() {
        $("#sx-send-sms-modal").modal('show');
        $(".sx-send-sms-phone").empty().append($(this).data("phone"));
        $("#sx-send-sms-phone-value").val($(this).data("phone"));
        return false;
    });
    
    sx.classes.FastEdit = sx.classes.Component.extend({
   
        _onDomReady: function()
        {
            var self = this;
            
            $('body').on('click', function (e) {
                //did not click a popover toggle or popover
                if ($(e.target).data('toggle') !== 'popover'
                    && $(e.target).closest('.popover').length === 0
                    && !$(e.target).hasClass("sx-fast-edit-popover")
                    && !$(e.target).closest(".sx-fast-edit-popover").length
                    ) { 
                    $('.sx-fast-edit-popover').popover('hide');
                }
            });
            
            $("body").on("click", ".sx-fast-edit-popover", function() {
                var jWrapper = $(this);
                $(".sx-fast-edit-popover").popover("hide");
                self._createPopover(jWrapper);
            });
            
            
        },
        
        _createPopover(jWrapper) {
            
            if (!jWrapper.hasClass('is-rendered')) {
                jWrapper.popover({
                    "html": true,
                    //'container': "body",
                    'trigger': "click",
                    'boundary': 'window',
                    'title': jWrapper.data('title').length ? jWrapper.data('title') : "",
                    'content': $(jWrapper.data('form'))
                });
    
                jWrapper.on('show.bs.popover', function (e, data) {
                    jWrapper.addClass('is-rendered');
                });
            }
            

            jWrapper.popover('show');
        }
        
        
    });
})(sx, sx.$, sx._);

new sx.classes.FastEdit({$jsData});
JS
);
$this->registerCSS(<<<CSS

.sx-send-sms-phone {
    font-weight: bold;
}
.sx-fast-edit-value {
    padding: 5px;
}

.sx-fast-edit-form-wrapper {
    display: none;
}

.sx-fast-edit {
    cursor: pointer;
    min-width: 40px;
    border-bottom: 1px dotted;
}

.sx-current-worker-task__content {
    align-items: center;
    display: flex;
    justify-content: space-between;
    width: 100%;
}


CSS
);
?>


<?php $pjax = \skeeks\cms\widgets\Pjax::begin(); ?>
    <div class="sx-detail-layout">


        <div class="sx-detail-layout__aside sx-surface-stack">


            <div class="sx-surface sx-surface--raised sx-surface--padded">
                <div class="sx-surface__title">Сотрудник <i data-toggle="tooltip" data-html="true"
                                                         title="Информация о сотруднике"
                                                         class="far fa-question-circle sx-hint-icon"></i>
                </div>
                <div class="sx-phones-block">
                    <?php if ($model->post) : ?>
                        <div class="d-flex mb-2">
                            <div class="sx-collection-cell sx-collection-cell--stack w-100">
                                <div class="sx-collection-cell__secondary">
                                    Должность
                                </div>
                                <div class="sx-collection-cell__primary">
                                    <?= Html::encode($model->post); ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($model->work_shedule) : ?>
                        <div class="d-flex mb-2">
                            <div class="sx-collection-cell sx-collection-cell--stack w-100">
                                <div class="sx-collection-cell__secondary">
                                    График работы
                                </div>
                                <div class="sx-collection-cell__primary">
                                    <?php $data = \skeeks\yii2\scheduleInputWidget\ScheduleInputWidget::getWorkingData($model->work_shedule); ?>
                                    <? foreach ($data as $row) : ?>
                                        <div>
                                            <?
                                            $stringDay = implode(', ', \yii\helpers\ArrayHelper::getValue($row, 'daysStrings'));
                                            ?>
                                            <b><?= Html::encode($stringDay); ?></b> <?= \yii\helpers\ArrayHelper::getValue($row, 'startHour') ?>:<?= \yii\helpers\ArrayHelper::getValue($row, 'startMinutes') ?>
                                            - <?= \yii\helpers\ArrayHelper::getValue($row, 'endHour') ?>:<?= \yii\helpers\ArrayHelper::getValue($row, 'endMinutes') ?>
                                        </div>
                                    <? endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if ($model->departments) : ?>
                        <div class="d-flex mb-2">
                            <div class="sx-collection-cell sx-collection-cell--stack w-100">
                                <div class="sx-collection-cell__secondary">
                                    Отделы
                                </div>
                                <div class="sx-collection-cell__primary">
                                    <? foreach ($model->departments as $department) : ?>
                                        <div>
                                            <?= BackendEntityLink::widget([
                                                'controllerId' => '/cms/admin-cms-department',
                                                'modelId'      => $department->id,
                                                'label'        => $department->fullName,
                                                'options'      => ['class' => 'sx-preview-card__related'],
                                            ]); ?>
                                        </div>
                                    <? endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if ($model->subordinates) : ?>
                        <div class="d-flex mb-2">
                            <div class="sx-collection-cell sx-collection-cell--stack w-100">
                                <div class="sx-collection-cell__secondary">
                                    Подчиненные
                                </div>
                                <div class="sx-collection-cell__primary">
                                    <? foreach ($model->subordinates as $subordinate) : ?>
                                        <div><?= BackendEntityLink::widget([
                                            'controllerId' => '/cms/admin-user',
                                            'modelId'      => $subordinate->id,
                                            'label'        => $subordinate->shortDisplayName,
                                            'options'      => ['class' => 'sx-preview-card__related'],
                                        ]); ?></div>
                                    <? endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?><?php if ($model->leaders) : ?>
                        <div class="d-flex mb-2">
                            <div class="sx-collection-cell sx-collection-cell--stack w-100">
                                <div class="sx-collection-cell__secondary">
                                    Руководители
                                </div>
                                <div class="sx-collection-cell__primary">
                                    <? foreach ($model->leaders as $leader) : ?>
                                        <div><?= BackendEntityLink::widget([
                                            'controllerId' => '/cms/admin-user',
                                            'modelId'      => $leader->id,
                                            'label'        => $leader->shortDisplayName,
                                            'options'      => ['class' => 'sx-preview-card__related'],
                                        ]); ?></div>
                                    <? endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>
            </div>

            <div class="sx-surface sx-surface--raised sx-surface--padded">
                <div class="sx-surface__title">Телефон <i data-toggle="tooltip" data-html="true"
                                                       title="У пользователя может быть задано несколько телефонов. Первый из них является основным и используется по умолчанию."
                                                       class="far fa-question-circle sx-hint-icon"></i>
                </div>
                    <div class="sx-phones-block">
                        <? foreach ($model->cmsUserPhones as $cmsUserPhone) : ?>
                        <div class="d-flex mb-2">
                            <div class="sx-collection-cell sx-collection-cell--stack w-100">
                                <div class="sx-collection-cell__secondary">
                                        <? if ($cmsUserPhone->name) : ?>
                                            <? echo $cmsUserPhone->name; ?>
                                        <? else : ?>
                                            Телефон
                                        <? endif; ?>
                                    </div>
                                <div class="sx-collection-cell__primary">
                                        <?= Html::a(
                                            Html::encode($cmsUserPhone->value),
                                            'tel:' . preg_replace('/[^\d+]/', '', $cmsUserPhone->value)
                                        ); ?>
                                        <?php if ($cmsUserPhone->is_approved) : ?>
                                            <span class="sx-text--success" data-html="true" data-toggle="tooltip"
                                                  title="Телефон подтвержден пользователем<br />Пользователь реально получил код на этот телефон и подтвердил его.">✓</span>
                                        <?php else : ?>
                                            <span class="sx-text--muted" data-html="true" data-toggle="tooltip" title="Пользователь не подтверждал телефон."><i class="far fa-question-circle"></i></span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="my-auto">
                                    <?
                                    \skeeks\cms\backend\widgets\AjaxControllerActionsWidget::begin([
                                        'controllerId' => "/cms/admin-user-phone",
                                        'modelId'      => $cmsUserPhone->id,
                                        'tag'          => 'div',
                                        'options'      => [
                                            'title' => 'Редактировать телефон',
                                            'class' => 'sx-icon-action',
                                        ],
                                    ]);
                                    ?>
                                    <!--<i class="hs-admin-angle-down"></i>-->
                                    <i class="fas fa-ellipsis-v"></i>
                                    <?php \skeeks\cms\backend\widgets\AjaxControllerActionsWidget::end(); ?>

                                </div>
                                <div class="my-auto d-flex">

                                    <button type="button" class="btn btn-default sx-send-sms-trigger mr-1"
                                            data-phone="<?= Html::encode($cmsUserPhone->value); ?>"
                                            data-html="true" title="Написать sms">
                                        <i class="fas fa-sms"></i>
                                    </button>

                                    <a class="btn btn-default"
                                       href="tel:<?= Html::encode(preg_replace('/[^\d+]/', '', $cmsUserPhone->value)); ?>"
                                       data-html="true" title="Начать звонок">
                                        <i class="fas fa-phone"></i>
                                    </a>
                                </div>
                            </div>
                        <? endforeach; ?>
                    </div>

                <?php if($isCanEdit) : ?>
                    <?

                    $actionData = \yii\helpers\Json::encode([
                        "isOpenNewWindow" => true,
                        "size"            => 'small',
                        "url"             => (string)\skeeks\cms\backend\helpers\BackendUrlHelper::createByParams([
                            "/cms/admin-user-phone/create",
                            'CmsUserPhone' => [
                                'cms_user_id' => $model->id,
                            ],
                        ])->enableEmptyLayout()->enableNoActions()->url,
                    ]);
                    ?>
                    <button class="btn btn-default btn-sm" onclick='<?= new \yii\web\JsExpression(<<<JS
               new sx.classes.backend.widgets.Action({$actionData}).go(); return false;
JS
                    ); ?>'>Добавить
                    </button>
                <?php endif; ?>



            </div>


            <div class="sx-surface sx-surface--raised sx-surface--padded">
                <div class="sx-surface__title">Email <i data-toggle="tooltip" data-html="true"
                                                     title="У пользователя может быть задано несколько email адресов. Первый из них является основным и используется по умолчанию."
                                                     class="far fa-question-circle sx-hint-icon"></i>
                </div>
                <div>
                    <div class="sx-phones-block">
                        <? foreach ($model->cmsUserEmails as $cmsUserEmail) : ?>
                        <div class="d-flex mb-2">
                            <div class="sx-collection-cell sx-collection-cell--stack w-100">
                                <div class="sx-collection-cell__secondary">
                                        <? if ($cmsUserEmail->name) : ?>
                                            <? echo $cmsUserEmail->name; ?>
                                        <? else : ?>
                                            Email
                                        <? endif; ?>
                                    </div>
                                <div class="sx-collection-cell__primary">
                                        <?= Html::a(
                                            Html::encode($cmsUserEmail->value),
                                            'mailto:' . $cmsUserEmail->value
                                        ); ?>
                                        <?php if ($cmsUserEmail->is_approved) : ?>
                                            <span class="sx-text--success" data-html="true" data-toggle="tooltip"
                                                  title="Email подтвержден пользователем<br />Пользователь реально получил код на этот email и подтвердил его.">✓</span>
                                        <?php else : ?>
                                            <span class="sx-text--muted" data-html="true" data-toggle="tooltip" title="Пользователь не подтверждал email."><i class="far fa-question-circle"></i></span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="my-auto">
                                    <?
                                    \skeeks\cms\backend\widgets\AjaxControllerActionsWidget::begin([
                                        'controllerId' => "/cms/admin-user-email",
                                        'modelId'      => $cmsUserEmail->id,
                                        'tag'          => 'div',
                                        'options'      => [
                                            'title' => 'Редактировать email',
                                            'class' => 'sx-icon-action',
                                        ],
                                    ]);
                                    ?>
                                    <!--<i class="hs-admin-angle-down"></i>-->
                                    <i class="fas fa-ellipsis-v"></i>
                                    <?php \skeeks\cms\backend\widgets\AjaxControllerActionsWidget::end(); ?>

                                </div>
                                <div class="my-auto">
                                    <a class="btn btn-default"
                                       href="mailto:<?= Html::encode($cmsUserEmail->value); ?>"
                                       data-html="true" title="Написать письмо">
                                        <i class="far fa-envelope"></i>
                                    </a>
                                </div>
                            </div>
                        <? endforeach; ?>
                    </div>

                    <?php if($isCanEdit) : ?>
                    <?

                    $actionData = \yii\helpers\Json::encode([
                        "isOpenNewWindow" => true,
                        "size"            => 'small',
                        "url"             => (string)\skeeks\cms\backend\helpers\BackendUrlHelper::createByParams([
                            "/cms/admin-user-email/create",
                            'CmsUserEmail' => [
                                'cms_user_id' => $model->id,
                            ],
                        ])->enableEmptyLayout()->enableNoActions()->url,
                    ]);
                    ?>
                    <button class="btn btn-default btn-sm" onclick='<?= new \yii\web\JsExpression(<<<JS
               new sx.classes.backend.widgets.Action({$actionData}).go(); return false;
JS
                    ); ?>'>Добавить
                    </button>
                    <? endif; ?>
                </div>
            </div>


            <div class="sx-surface sx-surface--raised sx-surface--padded">
                <div class="sx-surface__title">Адреса <i data-toggle="tooltip" data-html="true"
                                                      title="У пользователя может быть задано несколько адресов. Первый из них является основным и используется по умолчанию."
                                                      class="far fa-question-circle sx-hint-icon"></i>
                </div>
                <div>
                    <div class="sx-phones-block">
                        <? foreach ($model->cmsUserAddresses as $cmsUserAddress) : ?>
                        <div class="d-flex mb-2">
                            <div class="sx-collection-cell sx-collection-cell--stack w-100">
                                <div class="sx-collection-cell__secondary">
                                        <? if ($cmsUserAddress->name) : ?>
                                            <? echo $cmsUserAddress->name; ?>
                                        <? else : ?>
                                            Адрес
                                        <? endif; ?>
                                    </div>
                                <div class="sx-collection-cell__primary">
                                        <?= Html::encode($cmsUserAddress->value); ?>
                                    </div>
                                </div>

                                <div class="my-auto">
                                    <?
                                    \skeeks\cms\backend\widgets\AjaxControllerActionsWidget::begin([
                                        'controllerId' => "/cms/admin-user-address",
                                        'modelId'      => $cmsUserAddress->id,
                                        'tag'          => 'div',
                                        'options'      => [
                                            'title' => 'Редактировать адрес',
                                            'class' => 'sx-icon-action',
                                        ],
                                    ]);
                                    ?>
                                    <!--<i class="hs-admin-angle-down"></i>-->
                                    <i class="fas fa-ellipsis-v"></i>
                                    <?php \skeeks\cms\backend\widgets\AjaxControllerActionsWidget::end(); ?>

                                </div>
                            </div>
                        <? endforeach; ?>
                    </div>
                    <?php if($isCanEdit) : ?>
                    <?

                    $actionData = \yii\helpers\Json::encode([
                        "isOpenNewWindow" => true,
                        //"size"            => 'small',
                        "url"             => (string)\skeeks\cms\backend\helpers\BackendUrlHelper::createByParams([
                            "/cms/admin-user-address/create",
                            'CmsUserAddress' => [
                                'cms_user_id' => $model->id,
                            ],
                        ])->enableEmptyLayout()->enableNoActions()->url,
                    ]);
                    ?>
                    <button class="btn btn-default btn-sm" onclick='<?= new \yii\web\JsExpression(<<<JS
               new sx.classes.backend.widgets.Action({$actionData}).go(); return false;
JS
                    ); ?>'>Добавить
                    </button>
                    <? endif; ?>
                </div>
            </div>

            <div class="sx-surface sx-surface--raised sx-surface--padded">
                <div class="sx-surface__title">Информация <i data-toggle="tooltip" data-html="true"
                                                          title="Общая информация по пользователю, есть возможность создать любое количество полей с данными." class="far fa-question-circle sx-hint-icon"></i></div>
                <div>
                    <?
                    $eav = $model->relatedPropertiesModel;
                    //$eav->initAllProperties();
                    //print_r($eav->toArray());die;
                    //print_r($model->relatedProperties);die;
                    ?>
                    <? if ($eav->toArray()) : ?>
                        <? foreach ($eav->toArray() as $key => $value) : ?>
                            <? if ($value) : ?>
                        <div class="d-flex mb-2">
                            <div class="sx-collection-cell sx-collection-cell--stack w-100">
                                <div class="sx-collection-cell__secondary">
                                            <? echo $eav->getAttributeLabel($key); ?>
                                        </div>
                                <div class="sx-collection-cell__primary">
                                            <?php echo $eav->getSmartAttribute($key); ?>
                                        </div>
                                    </div>
                                </div>
                            <? endif; ?>

                        <? endforeach; ?>
                    <? endif; ?>

                    <?php if($isCanEdit) : ?>

                    <?

                    $actionData = \yii\helpers\Json::encode([
                        "isOpenNewWindow" => true,
                        "size"            => 'small',
                        "url"             => (string)\skeeks\cms\backend\helpers\BackendUrlHelper::createByParams([
                            "/cms/admin-user/update-eav",
                            'pk' => $model->id,
                        ])->enableEmptyLayout()->enableNoActions()->enableNoModelActions()->url,
                    ]);
                    ?>
                    <button class="btn btn-default btn-sm" onclick='<?= new \yii\web\JsExpression(<<<JS
               new sx.classes.backend.widgets.Action({$actionData}).go(); return false;
JS
                    ); ?>'>Редактировать
                    </button>
                    <? endif; ?>
                </div>
            </div>


        </div>

        <div class="sx-detail-layout__main sx-surface-stack">
            <?
            $isWorkingNow = (bool)$model->isWorkingNow;
            $currentTask = $model->getExecutorTasks()->statusInWork()->one();
            $isCurrentTaskAvailable = true;

            if ($currentTask && \Yii::$app->user->id != $model->id) {
                $isCurrentTaskAvailable = \Yii::$app->user->can("cms/admin-task/manage", ['model' => $currentTask]);
            }

            $currentWorkerStateCss = <<<CSS
.sx-current-worker-state .sx-state-title {
    color: var(--sx-color-text-muted);
    font-size: 12px;
    margin-bottom: 8px;
    text-transform: uppercase;
}
.sx-current-worker-state .sx-state-message {
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--sx-color-warning-on-surface);
}
.sx-current-worker-state .sx-state-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 34px;
    flex: 0 0 34px;
    border-radius: 50%;
    background: var(--sx-color-warning-soft);
    color: var(--sx-color-warning-on-soft);
}
.sx-current-worker-state .sx-state-message.is-unavailable {
    color: var(--sx-color-text-muted);
}
.sx-current-worker-state .sx-state-message.is-unavailable .sx-state-icon {
    background: var(--sx-color-surface-muted);
    color: var(--sx-color-text-subtle);
}
CSS;
            $this->registerCss($currentWorkerStateCss, [], 'sx-current-worker-state');
            if ($isWorkingNow) :
            ?>
                <div class="sx-current-worker-task">
                    <div class="sx-surface sx-surface--raised sx-surface--padded">
                        <?php if ($currentTask) : ?>
                            <div class="sx-current-worker-state">
                                <div class="sx-state-title">Сейчас выполняет задачу</div>
                            </div>
                            <?php if ($isCurrentTaskAvailable) : ?>
                                <div class="sx-current-worker-task__content">
                                    <? echo \skeeks\cms\widgets\admin\CmsTaskViewWidget::widget(['task' => $currentTask]); ?>
                                    <? echo \skeeks\cms\widgets\admin\CmsTaskStatusWidget::widget(['task' => $currentTask]); ?>
                                </div>
                            <?php else : ?>
                                <div class="sx-current-worker-state">
                                    <div class="sx-state-message is-unavailable">
                                        <span class="sx-state-icon"><i class="fas fa-lock"></i></span>
                                        <span>Задача недоступна для просмотра.</span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php else : ?>
                            <div class="sx-current-worker-state">
                                <div class="sx-state-title">Сейчас в работе</div>
                                <div class="sx-state-message">
                                    <span class="sx-state-icon"><i class="fas fa-exclamation"></i></span>
                                    <span>Рабочее время запущено, но задача не выбрана.</span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <? endif; ?>

            <?php $pjax = \skeeks\cms\widgets\Pjax::begin([
                'id'      => 'sx-comments',
                'options' => ['class' => 'sx-activity-thread'],
            ]); ?>

                <?php BackendSurfaceWidget::begin([
                    'title'      => 'Добавить комментарий',
                    'titleTag'   => 'h3',
                    'raised'     => true,
                    'responsive' => true,
                ]); ?>
                    <?php echo \skeeks\cms\widgets\admin\CmsCommentWidget::widget([
                        'model' => $model,
                    ]); ?>
                <?php BackendSurfaceWidget::end(); ?>

                <?php echo \skeeks\cms\widgets\admin\CmsLogListWidget::widget([
                    'query'         => $model->getUserLogs()->comments(),
                    'is_show_model' => false,
                ]); ?>

            <?php $pjax::end(); ?>
        </div>
    </div>
<?php $pjax = \skeeks\cms\widgets\Pjax::end(); ?>


<?php $widget = \yii\bootstrap\Modal::begin([
    'header'       => "SMS на <span class='sx-send-sms-phone'></span>",
    'id'           => 'sx-send-sms-modal',
    'size'         => \yii\bootstrap\Modal::SIZE_DEFAULT,
    'toggleButton' => false,
]); ?>

<?php if (\Yii::$app->cms->smsProvider) : ?>

    <?php
    $form = \skeeks\cms\base\widgets\ActiveFormAjaxSubmit::begin([
        'action'               => \yii\helpers\Url::to(['send-sms', 'pk' => $model->id]),
        'enableAjaxValidation' => false,
        'clientCallback'       => new \yii\web\JsExpression(<<<JS
    function (ActiveFormAjaxSubmit) {
    ActiveFormAjaxSubmit.on('success', function(e, response) {
    $(".modal").modal("hide");
    setTimeout(function() {
        window.location.reload();
    }, 1500);
    
    });
    }
    JS
        ),
    ]); ?>

    <div class="">
        <div class="form-group" style="display:none;">
            <label class="control-label">На телефон</label>
            <input type="text" id="sx-send-sms-phone-value" name="phone" class="form-control" placeholder="">
        </div>
        <div class="form-group">
            <textarea class="form-control" name="message" placeholder="Сообщение" rows="5"></textarea>
        </div>
        <div class="form-group">
            <button type="submit" class="btn btn-primary">Отправить</button>
        </div>
    </div>
    <?php $form::end(); ?>
<?php else : ?>
    <p>На вашем сайте не настроен ни один SMS провайдер.</p>
    <p>Все просто! Зайдите в <a href="<?php echo \yii\helpers\Url::to(['/cms/admin-cms-sms-provider']); ?>">этот раздел</a> и настройте отправку смс!</p>
    <p><a class="btn btn-primary" href="<?php echo \yii\helpers\Url::to(['/cms/admin-cms-sms-provider']); ?>">Настроить SMS отправку</a></p>
<?php endif; ?>

<?php $widget::end(); ?>
