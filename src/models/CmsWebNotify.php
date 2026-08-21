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

    public function getHtml()
    {
        $items = [];
        $class = $this->is_read ? "": "sx-not-read";
        $time = \Yii::$app->formatter->asRelativeTime($this->created_at);
        $model = $this->model;
        $url = $this->getTargetUrl();
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
    $items[] = $url
        ? Html::a(Html::encode($model->asText), $url, [
            'class'      => 'sx-action-trigger',
            'aria-label' => (string)$model->asText,
        ])
        : ($controllerId
        ? BackendEntityLink::widget([
            'controllerId' => $controllerId,
            'modelId'      => $this->model_id,
            'label'        => $model->asText,
            'options'      => [
                'class'      => 'sx-action-trigger',
                'aria-label' => (string)$model->asText,
            ],
        ])
        : Html::encode($model->asText));
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
