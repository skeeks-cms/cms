<?php

namespace skeeks\cms\controllers;

use skeeks\cms\backend\BackendAction;
use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\controllers\traits\LeadContactActions;
use skeeks\cms\models\CmsCompanyLink;
use skeeks\cms\models\CmsLeadLink;
use skeeks\yii2\form\fields\SelectField;
use skeeks\yii2\form\fields\TextField;
use yii\helpers\ArrayHelper;

/** Links of a lead; the form repeats the company link form. */
class AdminCmsLeadLinkController extends BackendModelStandartController
{
    use LeadContactActions;

    public function init()
    {
        $this->name = 'Ссылки сделки';
        $this->modelShowAttribute = 'url';
        $this->modelClassName = CmsLeadLink::class;
        $this->permissionName = 'cms/admin-lead';
        $this->generateAccessActions = false;
        parent::init();
    }

    public function actions()
    {
        return ArrayHelper::merge(parent::actions(), $this->leadContactActions(BackendAction::SIZE_SMALL, true));
    }

    public function updateFields($action): array
    {
        $action->model->cms_lead_id = $this->trustedLeadId($action->model);
        return [
            'link_type' => [
                'class' => SelectField::class,
                'items' => CmsCompanyLink::getLinkTypes(),
            ],
            'url' => ['class' => TextField::class, 'elementOptions' => ['placeholder' => 'https://', 'autocomplete' => 'off']],
            'name' => ['class' => TextField::class, 'elementOptions' => ['placeholder' => 'Например основной сайт']],
        ];
    }
}
