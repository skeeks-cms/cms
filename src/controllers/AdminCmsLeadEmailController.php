<?php

namespace skeeks\cms\controllers;

use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\controllers\traits\LeadContactActions;
use skeeks\cms\models\CmsLeadEmail;
use skeeks\yii2\form\fields\HtmlBlock;
use skeeks\yii2\form\fields\TextField;
use yii\helpers\ArrayHelper;

class AdminCmsLeadEmailController extends BackendModelStandartController
{
    use LeadContactActions;

    public function init()
    {
        $this->name = 'Email-ы сделки';
        $this->modelShowAttribute = 'value';
        $this->modelClassName = CmsLeadEmail::class;
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
            'value' => ['class' => TextField::class, 'elementOptions' => ['placeholder' => 'Email', 'autocomplete' => 'off']],
            'name' => ['class' => TextField::class, 'elementOptions' => ['placeholder' => 'Например рабочий email']],
            ['class' => HtmlBlock::class, 'content' => '</div></div>'],
        ];
    }
}
