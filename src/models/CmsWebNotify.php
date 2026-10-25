<?php

namespace skeeks\cms\models;

use skeeks\cms\base\ActiveRecord;
use skeeks\cms\models\queries\CmsWebNotifyQuery;
use skeeks\cms\backend\widgets\BackendEntityLink;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
/**
 * @property int         $id
 *
 * @property int|null    $created_at
 *
 * @property string      $name
 * @property string|null $comment
 * @property string|null $url
 *
 * @property string|null $model_code
 * @property int|null    $model_id
 *
 * @property int         $cms_user_id
 *
 * @property bool        $is_read
 *
 * @property CmsUser     $cmsUser
 */
class CmsWebNotify extends ActiveRecord
{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_web_notify}}';
    }


    /**
     * @inheritdoc
     */
    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [

            [
                [
                    'created_at',
                    'cms_user_id',
                ],
                'integer',
            ],

            [['name', 'comment'], "string"],
            [['url'], 'string', 'max' => 1000],

            [['model_code'], "string"],
            [['model_id'], "integer"],

            [['is_read'], "integer"],


            [['comment', 'url', 'model_code', 'model_id'], 'default', 'value' => null],
            [['is_read'], 'default', 'value' => 0],

            [
                ['name'],
                "required",
            ],
        ]);
    }


    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
        ]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsUser()
    {
        return $this->hasOne(\Yii::$app->user->identityClass, ['id' => 'cms_user_id']);
    }

    /**
     * One broken notification (a model without a label, a missing controller,
     * a failing widget) must not break the whole header notification list:
     * it is rendered without a link and the error goes to the log.
     */
    public function getHtml()
    {
        try {
            return $this->renderHtml();
        } catch (\Throwable $e) {
            \Yii::error("Web notification #{$this->id} ({$this->model_code}#{$this->model_id}) render failed: ".$e->getMessage()."\n".$e->getTraceAsString(), self::class);
        }

        return $this->renderHtml(false);
    }

    /**
     * Link label of the notification model: the asText property of CMS
     * records, the asText() method of plain Yii records, otherwise the id.
     */
    public function getModelLabel($model): string
    {
        if ($model->canGetProperty('asText')) {
            return (string)$model->asText;
        }
        if (method_exists($model, 'asText')) {
            return (string)$model->asText();
        }

        return '#'.(int)$this->model_id;
    }

    protected function renderHtml(bool $withLinks = true): string
    {
        $items = [];
        $class = $this->is_read ? "": "sx-not-read";
        $time = \Yii::$app->formatter->asRelativeTime($this->created_at);
        $model = $withLinks ? $this->model : null;
        $url = $withLinks ? $this->getTargetUrl() : null;
        $name = Html::encode($this->name);
        if (!$model && $url) {
            $name = Html::a($name, $url, [
                'class'      => 'sx-action-trigger',
                'aria-label' => (string)$this->name,
            ]);
        }
        $items[] = <<<HTML
<div class="sx-item {$class}">
<div class="sx-item-inner">
<div class="sx-time">{$time}</div>
<div class="sx-name">{$name}</div>
<div class="sx-model">
HTML;
if ($model) {
    $controllerId = ArrayHelper::getValue(\Yii::$app->skeeks->modelsConfig, [$this->model_code, 'controller']);
    $label = $this->getModelLabel($model);
    $items[] = $url
        ? Html::a(Html::encode($label), $url, [
            'class'      => 'sx-action-trigger',
            'aria-label' => $label,
        ])
        : ($controllerId
        ? BackendEntityLink::widget([
            'controllerId' => $controllerId,
            'modelId'      => $this->model_id,
            'label'        => $label,
            'options'      => [
                'class'      => 'sx-action-trigger',
                'aria-label' => $label,
            ],
        ])
        : Html::encode($label));
}


$items[] = <<<HTML
</div>
</div>
</div>
HTML;


        return implode("", $items);
    }

    public function getModel()
    {
        $classData = (array)ArrayHelper::getValue(\Yii::$app->skeeks->modelsConfig, $this->model_code);

        if ($classData) {
            $class = (string)ArrayHelper::getValue($classData, 'class', $this->model_code);
            if (class_exists($class)) {
                return $class::find()->andWhere(['id' => $this->model_id])->one();
            }
        }

        return;
    }

    /**
     * Explicit links are used when the recipient must open an entity in a
     * different application surface than the model's configured controller.
     * The CmsLead fallback keeps notifications created before the URL column
     * was introduced usable for a partner.
     */
    public function getTargetUrl(): ?string
    {
        if ($this->url) {
            return (string)$this->url;
        }

        $model = $this->model;
        if ($model instanceof CmsLead
            && \Yii::$app->has('user')
            && !\Yii::$app->user->isGuest
            && (int)$model->partner_id === (int)\Yii::$app->user->id
        ) {
            return $model->getPartnerViewUrl();
        }

        if ($model instanceof CmsTask
            && \Yii::$app->has('user')
            && !\Yii::$app->user->isGuest
            && (int)$model->created_by === (int)\Yii::$app->user->id
            && $model->isClientAuthored()
        ) {
            return $model->getClientViewUrl();
        }

        return null;
    }

    /**
     * @return CmsWebNotifyQuery
     */
    public static function find()
    {
        return new CmsWebNotifyQuery(get_called_class());
    }
}
