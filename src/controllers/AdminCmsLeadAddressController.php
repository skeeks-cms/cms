<?php

namespace skeeks\cms\controllers;

use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\controllers\traits\LeadContactActions;
use skeeks\cms\models\CmsLeadAddress;
use skeeks\cms\ya\map\widgets\YaMapDecodeInput;
use skeeks\yii2\form\fields\HtmlBlock;
use skeeks\yii2\form\fields\TextareaField;
use skeeks\yii2\form\fields\TextField;
use skeeks\yii2\form\fields\WidgetField;
use yii\bootstrap\Alert;
use yii\helpers\ArrayHelper;

/** Addresses of a lead; the form repeats the company address form. */
class AdminCmsLeadAddressController extends BackendModelStandartController
{
    use LeadContactActions;

    public function init()
    {
        $this->name = 'Адреса сделки';
        $this->modelShowAttribute = 'value';
        $this->modelClassName = CmsLeadAddress::class;
        $this->permissionName = 'cms/admin-lead';
        $this->generateAccessActions = false;
        parent::init();
    }

    public function actions()
    {
        // The map suggestion widget needs the regular window width.
        return ArrayHelper::merge(parent::actions(), $this->leadContactActions(null, true));
    }

    public function updateFields($action): array
    {
        $action->model->cms_lead_id = $this->trustedLeadId($action->model);

        $result = [];
        if (!\Yii::$app->yaMap->api_key) {
            $result[] = [
                'class' => HtmlBlock::class,
                'content' => Alert::widget([
                    'body' => 'У вас не настроен компонент для работы с yandex картами, в настройках компонента yandex карты пропишите api ключ.',
                    'options' => ['class' => 'alert alert-danger'],
                    'closeButton' => false,
                ]),
            ];
        }

        return array_merge($result, [
            'value' => [
                'class' => WidgetField::class,
                'widgetClass' => YaMapDecodeInput::class,
                'widgetConfig' => [
                    'modelLatitudeAttr' => 'latitude',
                    'modelLongitudeAttr' => 'longitude',
                ],
            ],
            ['class' => HtmlBlock::class, 'content' => '<div style="display: none;">'],
            'latitude',
            'longitude',
            ['class' => HtmlBlock::class, 'content' => '</div>'],
            ['class' => HtmlBlock::class, 'content' => '<div class="row"><div class="col-md-4">'],
            'entrance',
            ['class' => HtmlBlock::class, 'content' => '</div><div class="col-md-4">'],
            'floor',
            ['class' => HtmlBlock::class, 'content' => '</div><div class="col-md-4">'],
            'apartment_number',
            ['class' => HtmlBlock::class, 'content' => '</div></div>'],
            'name' => [
                'class' => TextField::class,
                'elementOptions' => ['placeholder' => 'Например, офис или склад'],
            ],
            'comment' => [
                'class' => TextareaField::class,
                'elementOptions' => ['placeholder' => 'Дополнительные примечания в свободной форме. Например код домофона или прочие особенности.'],
            ],
        ]);
    }
}
