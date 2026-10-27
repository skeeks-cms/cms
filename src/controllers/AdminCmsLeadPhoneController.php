<?php

namespace skeeks\cms\controllers;

use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\controllers\traits\LeadContactActions;
use skeeks\cms\models\CmsLeadPhone;
use skeeks\yii2\form\fields\HtmlBlock;
use skeeks\yii2\form\fields\TextField;
use yii\base\Event;
use yii\helpers\ArrayHelper;

class AdminCmsLeadPhoneController extends BackendModelStandartController
{
    use LeadContactActions;

    public function init()
    {
        $this->name = 'Телефоны сделки';
        $this->modelShowAttribute = 'value';
        $this->modelClassName = CmsLeadPhone::class;
        $this->permissionName = 'cms/admin-lead';
        $this->generateAccessActions = false;
        parent::init();
    }

    public function actions()
    {
        return ArrayHelper::merge(parent::actions(), $this->leadContactActions());
    }

    public function updateFields($action): array
    {
        $action->model->cms_lead_id = $this->trustedLeadId($action->model);
        return [
            ['class' => HtmlBlock::class, 'content' => '<div class="row no-gutters"><div class="col-12">'],
            'value' => [
                'class' => TextField::class,
                'elementOptions' => ['placeholder' => '+7 903 722-28-73', 'autocomplete' => 'off'],
                'on beforeRender' => function (Event $event) {
                    \skeeks\cms\admin\assets\JqueryMaskInputAsset::register(\Yii::$app->view);
                    $id = \yii\helpers\Html::getInputId($event->sender->model, $event->sender->attribute);
                    \Yii::$app->view->registerJs('$("#'.$id.'").mask("+7 999 999-99-99");');
                },
            ],
            'name' => ['class' => TextField::class, 'elementOptions' => ['placeholder' => 'Например рабочий телефон']],
            ['class' => HtmlBlock::class, 'content' => '</div></div>'],
        ];
    }
}
