<?php

namespace skeeks\cms\controllers;

use skeeks\cms\backend\BackendAction;
use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\models\CmsLead;
use skeeks\cms\models\CmsLeadPhone;
use skeeks\yii2\form\fields\HtmlBlock;
use skeeks\yii2\form\fields\TextField;
use yii\base\Event;
use yii\helpers\ArrayHelper;

class AdminCmsLeadPhoneController extends BackendModelStandartController
{
    public function init()
    {
        $this->name = 'Телефоны лида';
        $this->modelShowAttribute = 'value';
        $this->modelClassName = CmsLeadPhone::class;
        $this->permissionName = 'cms/admin-lead';
        $this->generateAccessActions = false;
        parent::init();
    }

    public function actions()
    {
        $access = fn() => $this->canManageContact();
        $lockLead = function (Event $event): void {
            $event->sender->model->cms_lead_id = $this->trustedLeadId($event->sender->model);
        };
        return ArrayHelper::merge(parent::actions(), [
            'create' => [
                'fields' => [$this, 'updateFields'],
                'size' => BackendAction::SIZE_SMALL,
                'buttons' => ['save'],
                'accessCallback' => $access,
                'on '.\skeeks\cms\backend\actions\BackendModelCreateAction::EVENT_BEFORE_VALIDATE => $lockLead,
            ],
            'update' => [
                'fields' => [$this, 'updateFields'],
                'size' => BackendAction::SIZE_SMALL,
                'buttons' => ['save'],
                'accessCallback' => $access,
                'on '.\skeeks\cms\backend\actions\BackendModelUpdateAction::EVENT_BEFORE_VALIDATE => $lockLead,
            ],
            'delete' => ['accessCallback' => $access],
        ]);
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

    private function canManageContact(): bool
    {
        $leadId = $this->model && !$this->model->isNewRecord
            ? (int)$this->model->cms_lead_id
            : (int)ArrayHelper::getValue(\Yii::$app->request->get('CmsLeadPhone', []), 'cms_lead_id');
        $lead = CmsLead::find()
            ->forManager()
            ->cmsSite()
            ->andWhere([CmsLead::tableName().'.id' => $leadId])
            ->one();
        return $lead && $lead->canBeWorkedBy((int)\Yii::$app->user->id);
    }

    private function trustedLeadId(CmsLeadPhone $model): int
    {
        return $model->isNewRecord
            ? (int)ArrayHelper::getValue(\Yii::$app->request->get('CmsLeadPhone', []), 'cms_lead_id')
            : (int)$model->getOldAttribute('cms_lead_id');
    }
}
