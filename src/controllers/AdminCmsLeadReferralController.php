<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\controllers;

use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\backend\grid\BackendEntityLinkColumn;
use skeeks\cms\crm\LeadReferrals;
use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\models\CmsLead;
use skeeks\cms\models\CmsLeadReferral;
use skeeks\cms\rbac\CmsManager;
use skeeks\yii2\form\fields\BoolField;
use skeeks\yii2\form\fields\NumberField;
use skeeks\yii2\form\fields\TextareaField;
use yii\base\Event;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\JsExpression;

/**
 * «Откуда узнали» (crm\LeadReferrals): the site list the client keeps himself,
 * with the rules that fill the deal field automatically.
 */
class AdminCmsLeadReferralController extends BackendModelStandartController
{
    public function init()
    {
        $this->name = 'Откуда узнали';
        $this->modelShowAttribute = 'name';
        $this->modelClassName = CmsLeadReferral::class;
        $this->permissionName = CmsManager::PERMISSION_ROLE_ADMIN_ACCESS;
        $this->generateAccessActions = false;

        parent::init();
    }

    public function actions()
    {
        return ArrayHelper::merge(parent::actions(), [
            'index' => [
                'emptyState' => [
                    'title' => 'Список «Откуда узнали» пуст',
                    'description' => 'Отмечайте у сделок, откуда клиент узнал о вас: так видно, какая реклама приносит продажи. '
                        .'Добавьте пункты вручную или начните со стандартного списка: «Яндекс Директ», «Поиск (SEO)», «2ГИС», «Рекомендация» и другие — '
                        .'кнопка «Стандартный список» вверху страницы.',
                    'icon' => 'fas fa-bullhorn',
                    'action' => ['backendAction' => 'create', 'label' => 'Добавить пункт'],
                ],
                'filters' => false,
                // «Стандартный список»: the missing items of the starting list, by POST (actionDefaults()).
                'pageHeader' => ['actions' => ['create', [
                    'label' => 'Стандартный список',
                    'url' => Url::to(['defaults']),
                    'icon' => 'fas fa-list',
                    'variant' => 'secondary',
                    'options' => [
                        'data-pjax' => '0',
                        'title' => 'Добавить недостающие пункты: «Яндекс Директ», «Поиск (SEO)», «2ГИС» и другие. Существующие не меняются.',
                        'onclick' => new JsExpression('var q = sx.ajax.preparePostQuery(this.href); var h = new sx.classes.AjaxHandlerStandartRespose(q); h.on("success", function () { window.location.reload(); }); q.execute(); return false;'),
                    ],
                ]]],
                'grid' => [
                    'on init' => static function (Event $event) {
                        $query = $event->sender->dataProvider->query;
                        $query->cmsSite();
                        $query->addSelect([
                            CmsLeadReferral::tableName().'.*',
                            'countLeads' => CmsLead::find()
                                ->select([new Expression('count(1)')])
                                ->andWhere(['cms_lead_referral_id' => new Expression(CmsLeadReferral::tableName().'.id')]),
                        ]);
                    },
                    'defaultOrder' => ['sort' => SORT_ASC],
                    'visibleColumns' => ['checkbox', 'actions', 'name', 'is_active', 'match_rules', 'sort', 'countLeads'],
                    'columns' => [
                        'name' => [
                            'class' => BackendEntityLinkColumn::class,
                            'controllerId' => '/cms/admin-cms-lead-referral',
                            'attribute' => 'name',
                        ],
                        'is_active' => [
                            'label' => 'В списке',
                            'format' => 'raw',
                            'value' => static fn(CmsLeadReferral $model) => $model->is_active
                                ? Html::tag('span', 'Да', ['class' => 'sx-status sx-status--success'])
                                : Html::tag('span', 'Выключен', ['class' => 'sx-status']),
                        ],
                        'match_rules' => [
                            'label' => 'Автоматически',
                            'format' => 'raw',
                            'value' => static function (CmsLeadReferral $model) {
                                $rules = LeadReferrals::parseRules((string)$model->match_rules);
                                if (!$rules) {
                                    return Html::tag('span', 'только вручную', ['class' => 'sx-collection-cell__secondary']);
                                }
                                // A short human summary; the rules themselves are in the item form.
                                $kinds = [];
                                foreach ($rules as $rule) {
                                    foreach (array_keys($rule) as $key) {
                                        $kinds[] = [
                                            LeadReferrals::KEY_REFERRER => 'по переходу с сайта',
                                            LeadReferrals::KEY_CHANNEL => 'по каналу',
                                            LeadReferrals::KEY_PHONE => 'по номеру звонка',
                                        ][$key] ?? 'по меткам';
                                    }
                                }
                                $kinds = array_values(array_unique($kinds));

                                return Html::tag('span', Html::encode(mb_strtoupper(mb_substr($kinds[0], 0, 1)).mb_substr(implode(', ', $kinds), 1)), [
                                    'class' => 'sx-collection-cell__secondary',
                                    'title' => (string)$model->match_rules,
                                ]);
                            },
                        ],
                        'sort' => [
                            'value' => static fn(CmsLeadReferral $model) => \Yii::$app->formatter->asInteger($model->sort),
                        ],
                        'countLeads' => [
                            'label' => 'Сделок',
                            'format' => 'raw',
                            'value' => static fn(CmsLeadReferral $model) => Html::tag('span',
                                Html::tag('strong', \Yii::$app->formatter->asInteger((int)$model->raw_row['countLeads'])),
                                ['class' => 'sx-collection-cell--metric']
                            ),
                        ],
                    ],
                ],
            ],
            'create' => ['fields' => [$this, 'updateFields']],
            'update' => ['fields' => [$this, 'updateFields']],
        ]);
    }

    /**
     * Adds the missing items of the starting list to the current site (POST); existing items are not changed.
     */
    public function actionDefaults()
    {
        $rr = new RequestResponse();
        if (!\Yii::$app->request->isPost) {
            $rr->success = false;
            $rr->message = 'Нужен POST-запрос.';
            return $rr;
        }
        $added = LeadReferrals::ensureDefaults((int)\Yii::$app->skeeks->site->id);
        $rr->success = true;
        $rr->message = $added ? 'Добавлено пунктов: '.$added : 'Все стандартные пункты уже есть.';

        return $rr;
    }

    public function updateFields($action)
    {
        return [
            'name',
            'is_active' => ['class' => BoolField::class, 'allowNull' => false],
            'sort' => ['class' => NumberField::class],
            'match_rules' => ['class' => TextareaField::class, 'elementOptions' => ['rows' => 5, 'placeholder' => "utm_source=yandex utm_medium=cpc\nreferrer=2gis.ru"]],
            'code',
        ];
    }
}
