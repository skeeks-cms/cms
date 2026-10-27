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
use skeeks\cms\models\CmsDeal;
use skeeks\cms\models\CmsDealItem;
use skeeks\cms\models\CmsDealType;
use skeeks\yii2\form\fields\HtmlBlock;
use skeeks\yii2\form\fields\NumberField;
use skeeks\yii2\form\fields\SelectField;
use skeeks\yii2\form\fields\TextareaField;
use yii\base\Event;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\UnsetArrayValue;
use yii\web\ForbiddenHttpException;

/**
 * «Состав сделки»: a line of what the client gets in the deal, opened from
 * the deal card ("Сделка как услуга").
 *
 * Create with ?CmsDealItem[cms_deal_id]=<deal>.
 */
class AdminCmsDealItemController extends BackendModelStandartController
{
    public function init()
    {
        $this->name = "Состав сделки";
        $this->modelShowAttribute = "asText";
        $this->modelClassName = CmsDealItem::class;

        $this->permissionName = 'cms/admin-company';
        $this->generateAccessActions = false;
        $this->modelHeader = '';

        parent::init();
    }

    public function actions()
    {
        $beforeValidate = function (Event $event) {
            $this->authorizeDeal($event->sender->model);
        };

        return ArrayHelper::merge(parent::actions(), [
            'index'        => new UnsetArrayValue(),
            'delete-multi' => new UnsetArrayValue(),
            'create'       => [
                'name'              => 'Добавить в состав',
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
                'name' => 'Убрать из состава',
            ],
        ]);
    }

    public function beforeAction($action)
    {
        //Code may be updated before the migration of "Сделка как услуга".
        if (!\skeeks\cms\models\queries\CmsDealQuery::dealAccessReady()) {
            throw new \yii\web\NotFoundHttpException('Состав сделки станет доступен после обновления базы данных.');
        }
        return parent::beforeAction($action);
    }

    public function getModel()
    {
        $model = parent::getModel();
        if ($model && !$model->isNewRecord) {
            $this->authorizeDeal($model);
        }
        return $model;
    }

    /**
     * The employee must see the deal of the line.
     */
    protected function authorizeDeal(CmsDealItem $model): void
    {
        if (!$model->cms_deal_id) {
            return; //validation reports the missing deal
        }
        if (!CmsDeal::find()->forManager()->andWhere([CmsDeal::tableName().'.id' => (int)$model->cms_deal_id])->exists()) {
            throw new ForbiddenHttpException('Нет доступа к этой сделке.');
        }
    }

    public function updateFields($action)
    {
        /** @var CmsDealItem $model */
        $model = $action->model;
        if ($model->isNewRecord) {
            $model->load(\Yii::$app->request->get());
        }
        //Before anything of the deal (its resources) is read: a deal from the request may be foreign.
        $this->authorizeDeal($model);
        //decimal(…, 4) columns: show 1 and 1500, not 1,0000 and 1500,0000.
        if (!\Yii::$app->request->isPost) {
            $model->quantity = $model->quantity === null || $model->quantity === '' ? 1 : (float)$model->quantity;
            $model->price = $model->price === null || $model->price === '' ? null : (float)$model->price;
        }
        $deal =$model->cms_deal_id ? CmsDeal::findOne((int)$model->cms_deal_id) : null;

        //Resources of this deal; a line that keeps a moved resource as history shows it too.
        $resources = [CmsDealItem::RESOURCE_NONE => 'Без ресурса'];
        if ($deal) {
            /** @var DealAccess $dealAccess */
            $dealAccess = \Yii::$app->get('dealAccess');
            foreach ($dealAccess->resources($deal) as $resource) {
                $provider = $dealAccess->getProvider($resource->type);
                $resources[$resource->type.':'.$resource->id] = ($provider ? $provider->label().' ' : '').$resource->title;
            }
            //Only the saved link of a stored line, never a key from the request: that would name a foreign resource.
            $oldType = $model->isNewRecord ? null : (string)$model->getOldAttribute('resource_type');
            $oldId = $model->isNewRecord ? 0 : (int)$model->getOldAttribute('resource_id');
            if ($oldType && $oldId && !isset($resources[$oldType.':'.$oldId])) {
                $old = $dealAccess->resource($oldType, $oldId);
                $resources[$oldType.':'.$oldId] = ($old ? $old->title : $oldType.':'.$oldId).' (перенесён в другую сделку)';
            }
        }

        $period = $deal && $deal->dealType && $deal->dealType->is_periodic ? $deal->dealType->periodAsText : '';

        return [
            'name',
            'quantity'    => [
                'class' => NumberField::class,
                'step'  => 'any',
            ],
            'price'       => [
                'class' => NumberField::class,
                'step'  => 0.01,
            ],
            'period'      => [
                'class' => SelectField::class,
                'items' => CmsDealType::optionsForPeriod(),
                'hint'  => $period ? 'Пусто — как у сделки ('.mb_strtolower($period).')' : 'Пусто — как у сделки',
            ],
            'resourceKey' => [
                'class'     => SelectField::class,
                'allowNull' => false,
                'items'     => $resources,
            ],
            'description' => [
                'class' => TextareaField::class,
            ],
            'priority',
            [
                'class'   => HtmlBlock::class,
                'content' => Html::activeHiddenInput($model, 'cms_deal_id'),
            ],
        ];
    }
}
