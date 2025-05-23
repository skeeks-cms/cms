<?php

namespace skeeks\cms\models;

use skeeks\cms\base\ActiveRecord;
use skeeks\cms\models\queries\CmsWebNotifyQuery;
use yii\helpers\ArrayHelper;
/**
 * @property int         $id
 *
 * @property int|null    $created_at
 *
 * @property string      $name
 * @property string|null $comment
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

            [['model_code'], "string"],
            [['model_id'], "integer"],

            [['is_read'], "integer"],


            [['comment', 'model_code', 'model_id', 'comment'], 'default', 'value' => null],
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
        $items[] = <<<HTML
<div class="sx-item {$class}">
<div class="sx-item-inner">
<div class="sx-time">{$time}</div>
<div class="sx-name">{$this->name}</div>
<div class="sx-model">
HTML;
if ($this->model) {
    $items[] = \skeeks\cms\backend\widgets\AjaxControllerActionsWidget::widget([
        'controllerId'            => \yii\helpers\ArrayHelper::getValue(\Yii::$app->skeeks->modelsConfig, [$this->model_code, 'controller']),
        'modelId'                 => $this->model_id,
        'tag'                 => 'span',
        'isRunFirstActionOnClick' => true,
        'content' => $this->model->asText,
        'options'                 => [
            'class' => 'sx-action-trigger',
        ],
    ]);
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
     * @return CmsWebNotifyQuery
     */
    public static function find()
    {
        return new CmsWebNotifyQuery(get_called_class());
    }
}