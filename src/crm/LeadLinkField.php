<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\crm;

use skeeks\cms\models\CmsLead;
use skeeks\cms\widgets\AjaxSelectModel;
use skeeks\yii2\form\fields\WidgetField;
use yii\db\BaseActiveRecord;
use yii\helpers\Html;
use yii\web\JsExpression;

/**
 * The field «Сделка» of a record written for a client — a service, a bill, a
 * document (owner's decision 29.10.2026, «Полноценная работа в рамках своих
 * сделок»): open deals available to the employee (CmsLeadQuery::forManager(),
 * on «Свои сделки» — his own), of the client chosen in the form or of the
 * record; the deal already linked stays choosable. The server rule is
 * CrmAccess::validateLeadLink().
 */
class LeadLinkField
{
    /**
     * A new record opened from a deal (`<Form>[cms_lead_id]` of the link
     * «Добавить» on the deal card): the client of the deal, when the record has none.
     */
    public static function applyLeadClient(BaseActiveRecord $model): void
    {
        if (!$model->isNewRecord || !$model->hasAttribute('cms_lead_id') || !$model->cms_lead_id
            || $model->cms_company_id || $model->cms_user_id) {
            return;
        }
        $lead = CmsLead::find()->forManager()->andWhere([CmsLead::tableName().'.id' => (int)$model->cms_lead_id])->one();
        if (!$lead) {
            $model->cms_lead_id = null;
            return;
        }
        $model->cms_company_id = $lead->cms_company_id ? (int)$lead->cms_company_id : null;
        $model->cms_user_id = $lead->cms_company_id ? null : ($lead->cms_user_id ? (int)$lead->cms_user_id : null);
    }

    /**
     * The WidgetField config. The client inputs of the form (when it has them)
     * narrow the deals while the client is being chosen.
     */
    public static function config(BaseActiveRecord $model, array $field = []): array
    {
        $companyInput = Html::getInputId($model, 'cms_company_id');
        $userInput = Html::getInputId($model, 'cms_user_id');

        return array_replace_recursive([
            'class'        => WidgetField::class,
            'widgetClass'  => AjaxSelectModel::class,
            'hint'         => 'Сделка, по которой это сделано: запись видна тем, кто ведёт сделку',
            'widgetConfig' => [
                'modelClass'  => CmsLead::class,
                'searchQuery' => function ($word = '') use ($model) {
                    $request = \Yii::$app->request;
                    $companyId = (int)($request->get('cms_company_id') ?: $model->cms_company_id);
                    $userId = (int)($request->get('cms_user_id') ?: $model->cms_user_id);
                    $table = CmsLead::tableName();
                    $query = CmsLead::find()->forManager();
                    if ($companyId) {
                        $query->andWhere([$table.'.cms_company_id' => $companyId]);
                    } elseif ($userId) {
                        $query->andWhere([$table.'.cms_user_id' => $userId, $table.'.cms_company_id' => null]);
                    }
                    $query->andWhere(['or',
                        ['not in', $table.'.status', [CmsLead::STATUS_SUCCESS, CmsLead::STATUS_REJECTED]],
                        [$table.'.id' => (int)$model->cms_lead_id],
                    ]);
                    if ($word) {
                        $query->search($word);
                    }

                    return $query->orderBy([$table.'.id' => SORT_DESC]);
                },
                'pluginOptions' => [
                    'ajax' => [
                        // The visible client input wins: a form with the switcher «Компания / Клиент» hides the other one.
                        'data' => new JsExpression('function(params) {
                            var c = $("#'.$companyInput.'"), u = $("#'.$userInput.'");
                            var company = c.length && c.closest(".form-group").is(":visible") ? c.val() : "";
                            var user = !company && u.length && u.closest(".form-group").is(":visible") ? u.val() : "";
                            return {q: params.term, cms_company_id: company || "", cms_user_id: user || ""};
                        }'),
                    ],
                ],
            ],
        ], $field);
    }
}
