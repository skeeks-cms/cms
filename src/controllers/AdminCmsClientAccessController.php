<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\controllers;

use skeeks\cms\backend\actions\BackendModelUpdateAction;
use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\deal\DealAccess;
use skeeks\cms\models\CmsClientAccess;
use skeeks\cms\models\CmsCompany;
use skeeks\cms\models\CmsCompany2user;
use skeeks\cms\models\CmsDeal;
use skeeks\cms\models\CmsDealRole;
use skeeks\cms\models\CmsUser;
use skeeks\cms\widgets\admin\DealAccessLevelsWidget;
use skeeks\cms\widgets\AjaxSelectModel;
use skeeks\yii2\form\fields\BoolField;
use skeeks\yii2\form\fields\HtmlBlock;
use skeeks\yii2\form\fields\SelectField;
use skeeks\yii2\form\fields\WidgetField;
use yii\base\Event;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\UnsetArrayValue;
use yii\web\ForbiddenHttpException;

/**
 * «Доступ клиента»: a grant of a client person on a deal or on all deals of a
 * company, opened from the deal card ("Сделка как услуга").
 *
 * Create with ?CmsClientAccess[cms_deal_id]=<deal> or ?CmsClientAccess[cms_company_id]=<company>.
 */
class AdminCmsClientAccessController extends BackendModelStandartController
{
    public function init()
    {
        $this->name = "Доступ клиента";
        $this->modelShowAttribute = "asText";
        $this->modelClassName = CmsClientAccess::class;

        $this->permissionName = 'cms/admin-company';
        $this->generateAccessActions = false;
        //A small window: the person is shown in the form, no model header.
        $this->modelHeader = '';

        parent::init();
    }

    public function actions()
    {
        $beforeValidate = function (Event $event) {
            $this->authorizeScope($event->sender->model);
        };

        return ArrayHelper::merge(parent::actions(), [
            'index'        => new UnsetArrayValue(),
            'delete-multi' => new UnsetArrayValue(),
            'create'       => [
                'name'              => 'Дать доступ',
                'fields'            => [$this, 'updateFields'],
                'size'              => BackendModelUpdateAction::SIZE_SMALL,
                'buttons'           => ['save'],
                'on beforeValidate' => $beforeValidate,
            ],
            'update'       => [
                'fields'            => [$this, 'updateFields'],
                'size'              => BackendModelUpdateAction::SIZE_SMALL,
                'buttons'           => ['save'],
                'on beforeValidate' => $beforeValidate,
            ],
            'delete'       => [
                'name' => 'Убрать доступ',
            ],
        ]);
    }

    public function getModel()
    {
        $model = parent::getModel();
        if ($model && !$model->isNewRecord) {
            $this->authorizeScope($model);
        }
        return $model;
    }

    /**
     * The employee must see the deal (or the company) the grant is about.
     */
    protected function authorizeScope(CmsClientAccess $model): void
    {
        if ($model->cms_deal_id) {
            $visible = CmsDeal::find()->forManager()->andWhere([CmsDeal::tableName().'.id' => (int)$model->cms_deal_id])->exists();
        } elseif ($model->cms_company_id) {
            $visible = CmsCompany::find()->forManager()->andWhere([CmsCompany::tableName().'.id' => (int)$model->cms_company_id])->exists();
        } else {
            $visible = true; //validation reports the missing scope
        }
        if (!$visible) {
            throw new ForbiddenHttpException('Нет доступа к этой услуге или компании.');
        }
    }

    public function updateFields($action)
    {
        /** @var CmsClientAccess $model */
        $model = $action->model;
        if ($model->isNewRecord) {
            $model->load(\Yii::$app->request->get());
            if (!$model->cms_deal_role_id && !$model->is_full) {
                $role = CmsDealRole::find()->andWhere(['code' => CmsDealRole::CODE_MARKETER])->one();
                $model->cms_deal_role_id = $role ? $role->id : null;
                $model->levels = $role ? $role->getLevels() : [];
            }
        }
        //Before the people of the company or the deal are read: the scope from the request may be foreign.
        $this->authorizeScope($model);
        if ($originDeal = (int)\Yii::$app->request->get('deal')) {
            if (!CmsDeal::find()->forManager()->andWhere([CmsDeal::tableName().'.id' => $originDeal])->exists()) {
                throw new ForbiddenHttpException('Нет доступа к этой услуге.');
            }
        }

        $deal = $model->cms_deal_id ? CmsDeal::findOne((int)$model->cms_deal_id) : null;
        $companyId = $deal ? (int)$deal->cms_company_id : (int)$model->cms_company_id;
        if ($deal && !$model->cms_company_id) {
            $model->cms_company_id = $deal->cms_company_id;
        }
        $originDealId = $deal ? (int)$deal->id : (int)\Yii::$app->request->get('deal');

        $fields = [];

        //Who
        if ($companyId) {
            $people = CmsCompany2user::find()->with('cmsUser')->andWhere(['cms_company_id' => $companyId])->orderBy(['sort' => SORT_ASC])->all();
            $items = [];
            foreach ($people as $link) {
                if ($link->cmsUser) {
                    $items[(int)$link->cms_user_id] = $link->cmsUser->shortDisplayName.($link->comment ? ' — '.$link->comment : '');
                }
            }
            $fields['cms_user_id'] = [
                'class' => SelectField::class,
                'items' => $items,
                'hint'  => 'Люди компании. Нового человека сначала добавьте в компанию.',
            ];
        } else {
            $fields['cms_user_id'] = [
                'class'        => WidgetField::class,
                'widgetClass'  => AjaxSelectModel::class,
                'widgetConfig' => [
                    'modelClass'  => CmsUser::class,
                    'searchQuery' => function ($word = '') {
                        $query = CmsUser::find()->forManager();
                        if ($word) {
                            $query->search($word);
                        }
                        return $query;
                    },
                ],
            ];
        }

        //An existing grant belongs to its person: only the scope, role and levels change.
        if (!$model->isNewRecord) {
            $person = $model->cmsUser;
            $fields['cms_user_id'] = [
                'class'   => HtmlBlock::class,
                'content' => Html::tag('div',
                    Html::tag('div', Html::encode($person ? $person->shortDisplayName : '#'.$model->cms_user_id), ['class' => 'sx-deal-access-person']),
                    ['class' => 'col-12 form-group']
                ),
            ];
        }

        //Where: this deal or every deal of the company
        if ($companyId && $originDealId) {
            $originDeal = $deal ?: CmsDeal::findOne($originDealId);
            $fields['cms_deal_id'] = [
                'class'     => SelectField::class,
                'label'     => 'Где действует',
                'allowNull' => false,
                'items'     => [
                    (string)$originDealId => 'Только в услуге «'.($originDeal ? $originDeal->name : $originDealId).'»',
                    ''                    => 'Во всех услугах компании, включая будущие',
                ],
            ];
        }

        //Role and levels
        $roles = CmsDealRole::find()
            ->andWhere(['or', ['is_active' => 1], ['id' => (int)$model->cms_deal_role_id]])
            ->orderBy(['priority' => SORT_ASC])
            ->all();
        $fields['cms_deal_role_id'] = [
            'class'     => SelectField::class,
            'allowNull' => false,
            'items'     => ArrayHelper::map($roles, 'id', 'name'),
            'hint'  => 'Роль заполняет области доступа; их можно поменять для этого человека.',
        ];
        $fields['is_full'] = [
            'class'       => BoolField::class,
            'allowNull'   => false,
            'formElement' => BoolField::ELEMENT_CHECKBOX,
            'hint'        => 'Все области доступа, в том числе будущие',
        ];

        /** @var DealAccess $dealAccess */
        $dealAccess = \Yii::$app->get('dealAccess');
        $openLevels = null;
        if ($deal) {
            $openLevels = [];
            foreach (array_keys($dealAccess->getAreaList()) as $area) {
                $openLevels[$area] = $dealAccess->openLevel($deal, $area);
            }
        }
        $fields[] = [
            'class'   => HtmlBlock::class,
            'content' => DealAccessLevelsWidget::widget([
                'name'        => Html::getInputName($model, 'levels'),
                'levels'      => $model->getLevels(),
                'roleInputId' => Html::getInputId($model, 'cms_deal_role_id'),
                'fullInputId' => Html::getInputId($model, 'is_full'),
                'openLevels'  => $openLevels,
            ]),
        ];
        $fields[] = 'comment';

        //"Во всех услугах компании" needs the company when the deal is emptied.
        if ($companyId) {
            $model->cms_company_id = $companyId;
            $fields[] = [
                'class'   => HtmlBlock::class,
                'content' => Html::activeHiddenInput($model, 'cms_company_id'),
            ];
        }

        return $fields;
    }
}
