<?php
/**
 * ProfileController
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 14.10.2014
 * @since 1.0.0
 */

namespace skeeks\cms\controllers;

use common\models\LoginForm;
use frontend\models\ContactForm;
use frontend\models\PasswordResetRequestForm;
use frontend\models\ResetPasswordForm;
use frontend\models\SignupForm;
use skeeks\cms\actions\backend\BackendModelMultiActivateAction;
use skeeks\cms\actions\backend\BackendModelMultiDeactivateAction;
use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\grid\BooleanColumn;
use skeeks\cms\models\CmsContractor;
use skeeks\cms\models\CmsContractorBank;
use skeeks\crm\components\CrmComponent;
use skeeks\crm\controllers\AdminCrmBillController;
use skeeks\crm\controllers\AdminCrmContactController;
use skeeks\crm\controllers\AdminCrmContractorController;
use skeeks\crm\models\CrmBankData;
use skeeks\crm\models\CrmContact;
use skeeks\crm\models\CrmContractor;
use skeeks\yii2\form\fields\BoolField;
use skeeks\yii2\form\fields\NumberField;
use skeeks\yii2\form\fields\SelectField;
use skeeks\yii2\form\fields\TextareaField;
use yii\helpers\ArrayHelper;
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class AdminCmsContractorBankController extends BackendModelStandartController
{
    public function init()
    {
        $this->name = "Банковские реквизиты";
        $this->modelShowAttribute = "id";
        $this->modelClassName = CmsContractorBank::class;

        $this->generateAccessActions = false;
        $this->permissionName = 'cms/admin-company';

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return ArrayHelper::merge(parent::actions(), [
            'create' => [
                'isVisible' => false,
                'fields'    => [$this, 'updateFields'],
            ],
            'update' => [
                'fields' => [$this, 'updateFields'],
            ],
            'index'  => [
                'filters' => [
                    'visibleFilters' => [
                        'bank_name',
                    ],
                ],
                'grid'    => [
                    'defaultOrder'   => [
                        'sort' => SORT_ASC,
                    ],
                    'visibleColumns' => [
                        'checkbox',
                        'actions',
                        //'id',
                        'bank_name',
                        'bic',
                        'correspondent_account',
                        'checking_account',
                        'is_active',
                    ],
                    'columns'        => [
                        'is_active' => [
                            'class' => BooleanColumn::class
                        ]
                    ],
                ],
            ],
            
            "activate-multi" => [
                'class' => BackendModelMultiActivateAction::class,
            ],

            "deactivate-multi" => [
                'class' => BackendModelMultiDeactivateAction::class,
            ],
        ]);
    }

    public function updateFields($action)
    {
        if ($cms_contractor_id = \Yii::$app->request->get('cms_contractor_id')) {
            $action->model->cms_contractor_id = $cms_contractor_id;
        }

        return [
            'cms_contractor_id' => [
                'class' => SelectField::class,
                'items' => ArrayHelper::map(
                    CmsContractor::find()->forManager()->all(),
                    'id',
                    'asText'
                ),
            ],
            'bank_name',
            'bic',
            'correspondent_account',
            'checking_account',

            'is_active' => [
                'class' => BoolField::class,
                'allowNull' => false,
            ],

            'sort' => [
                'class' => NumberField::class
            ],

            'bank_address',

            'comment'           => [
                'class' => TextareaField::class,
            ],
        ];
    }

}
