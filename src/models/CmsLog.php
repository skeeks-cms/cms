<?php

namespace skeeks\cms\models;

use skeeks\cms\base\ActiveRecord;
use skeeks\cms\models\behaviors\HasJsonFieldsBehavior;
use skeeks\cms\models\behaviors\HasStorageFileMulti;
use skeeks\cms\models\queries\CmsLogQuery;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\HtmlPurifier;
use yii\helpers\Inflector;
/**
 * @property int              $id
 *
 * @property int|null         $created_by
 * @property int|null         $updated_by
 * @property int|null         $created_at
 * @property int|null         $updated_at
 *
 * @property int|null         $cms_company_id
 * @property int|null         $cms_user_id
 *
 * @property string|null      $model_as_text
 * @property string|null      $model_code
 * @property int|null         $model_id
 *
 * @property string|null      $sub_model_code
 * @property int|null         $sub_model_id
 * @property string|null      $sub_model_log_type
 * @property string|null      $sub_model_as_text
 * @property array            $data
 *
 * @property string           $log_type
 * @property int|null         $is_pinned
 *
 * @property string|null      $comment
 *
 * @property CmsCompany       $cmsCompany
 * @property CmsUser          $cmsUser
 *
 * @property CmsStorageFile[] $files
 * @property CmsStorageFile[] $logTypeAsText
 *
 *
 * @property ActiveRecord     $model
 * @property ActiveRecord     $subModel
 */
class CmsLog extends ActiveRecord
{
    const LOG_TYPE_COMMENT = "comment";
    const LOG_TYPE_PHONE_CALL = "phone_call";

    const LOG_TYPE_DELETE = "delete";
    const LOG_TYPE_UPDATE = "update";
    const LOG_TYPE_INSERT = "insert";

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_log}}';
    }


    static public function typeList()
    {
        return [
            self::LOG_TYPE_PHONE_CALL => 'Телефонный звонок',
            self::LOG_TYPE_COMMENT    => 'Комментарий',
            self::LOG_TYPE_UPDATE     => 'Обновление данных',
            self::LOG_TYPE_INSERT     => 'Создание записи',
            self::LOG_TYPE_DELETE     => 'Удаление',
        ];
    }

    /**
     * @return string
     */
    public function getLogTypeAsText()
    {
        return (string)ArrayHelper::getValue(\Yii::$app->skeeks->logTypes, $this->log_type, "Прочее");
    }


    /**
     * @return array
     */
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            HasJsonFieldsBehavior::class => [
                'class'  => HasJsonFieldsBehavior::class,
                'fields' => ['data'],
            ],
            HasStorageFileMulti::class   => [
                'class'     => HasStorageFileMulti::class,
                'relations' => [
                    [
                        'relation' => 'files',
                        'property' => 'fileIds',
                    ],
                ],
            ],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [

            [
                [
                    'created_by',
                    'updated_by',
                    'created_at',
                    'updated_at'
                    ,
                    'cms_company_id'
                    ,
                    'cms_user_id',
                    'is_pinned',
                ],
                'integer',
            ],

            [['log_type', 'comment'], "string"],

            [['data'], "safe"],
            [['model_code'], "string"],
            [['model_id'], "integer"],
            [['model_as_text'], "string"],

            [['sub_model_code'], "string"],
            [['sub_model_log_type'], "string"],
            [['sub_model_id'], "integer"],
            [['sub_model_as_text'], "string"],

            [['model_as_text'], 'default', 'value' => null],
            [['sub_model_as_text'], 'default', 'value' => null],
            [['data'], 'default', 'value' => null],
            [['log_type'], 'default', 'value' => self::LOG_TYPE_COMMENT],
            [['is_pinned'], 'default', 'value' => 0],

            [['comment', 'cms_company_id', 'cms_user_id'], 'default', 'value' => null],
            [['sub_model_code', 'sub_model_id', 'sub_model_log_type'], 'default', 'value' => null],

            /*[['comment'], "filter", 'filter' => 'trim'],*/

            [
                ['comment'],
                "required",
                'when' => function () {
                    return $this->log_type == self::LOG_TYPE_COMMENT;
                },
            ],

            [['fileIds'], 'safe'],

            [
                ['fileIds'],
                \skeeks\cms\validators\FileValidator::class,
                'skipOnEmpty' => false,
                //'extensions'    => [''],
                'maxFiles'    => 50,
                'maxSize'     => 1024 * 1024 * 100,
                'minSize'     => 256,
            ],
        ]);
    }


    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'cms_company_id' => "Компания",
            'cms_user_id'    => "Клиент",
            'comment'        => "Текст комментария",
            'is_pinned'      => "Закрепить комментарий",
            'fileIds'        => "Файлы",
        ]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsCompany()
    {
        return $this->hasOne(CmsCompany::class, ['id' => 'cms_company_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsUser()
    {
        return $this->hasOne(\Yii::$app->user->identityClass, ['id' => 'cms_user_id']);
    }


    protected $_file_ids = null;

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFiles()
    {
        return $this->hasMany(CmsStorageFile::class, ['id' => 'storage_file_id'])
            ->via('cmsLogFiles')
            ->orderBy(['priority' => SORT_ASC]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsLogFiles()
    {
        return $this->hasMany(CmsLogFile::className(), ['cms_log_id' => 'id']);
    }

    /**
     * @return array
     */
    public function getFileIds()
    {
        if ($this->_file_ids !== null) {
            return $this->_file_ids;
        }

        if ($this->files) {
            return ArrayHelper::map($this->files, 'id', 'id');
        }

        return [];
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function setFileIds($ids)
    {
        $this->_file_ids = $ids;
        return $this;
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
    public function getSubModel()
    {
        $classData = (array)ArrayHelper::getValue(\Yii::$app->skeeks->modelsConfig, $this->sub_model_code);

        if ($classData) {
            $class = (string)ArrayHelper::getValue($classData, 'class', $this->sub_model_code);
            if (class_exists($class)) {
                return $class::find()->andWhere(['id' => $this->sub_model_id])->one();
            }
        }

        return;
    }

    protected function formatLogValue($key, $name, $value)
    {
        if (is_array($value)) {
            return $this->renderCollapsedValue(print_r($value, true), true);
        }

        if ($value === null || $value === '') {
            return '';
        }

        $value = (string)$value;
        $lowerKey = mb_strtolower((string)$key, 'UTF-8');
        $lowerName = mb_strtolower((string)$name, 'UTF-8');

        $relationValue = $this->formatRelationLogValue($key, $value);
        if ($relationValue !== null) {
            return $relationValue;
        }

        if ($this->isDurationLogValue($lowerKey, $lowerName, $value)) {
            return \skeeks\cms\helpers\CmsScheduleHelper::durationAsText((int)$value);
        }

        if ($this->isTimestampLogValue($lowerKey, $lowerName, $value)) {
            return \Yii::$app->formatter->asDatetime((int)$value);
        }

        if ($this->isLongLogValue($value)) {
            return $this->renderCollapsedValue($value);
        }

        return $value;
    }

    protected function formatRelationLogValue($key, $value)
    {
        if (!is_numeric($value) || !preg_match('/_id$/', (string)$key)) {
            return null;
        }

        $model = $this->subModel ?: $this->model;
        if (!$model) {
            return null;
        }

        $relationName = lcfirst(Inflector::id2camel(substr((string)$key, 0, -3), '_'));
        $relation = $model->getRelation($relationName, false);
        if (!$relation || count($relation->link) !== 1) {
            return null;
        }

        $targetAttribute = array_key_first($relation->link);
        $modelClass = $relation->modelClass;
        $relatedModel = $modelClass::find()->andWhere([$targetAttribute => $value])->one();

        return $relatedModel ? (string)$relatedModel : null;
    }

    protected function isDurationLogValue($lowerKey, $lowerName, $value)
    {
        if (!is_numeric($value)) {
            return false;
        }

        return (bool)preg_match('/duration|seconds|длительность|продолжительность/u', $lowerKey.' '.$lowerName);
    }

    protected function isTimestampLogValue($lowerKey, $lowerName, $value)
    {
        if (!is_numeric($value)) {
            return false;
        }

        $timestamp = (int)$value;
        if ($timestamp < 946684800 || $timestamp > 4102444800) {
            return false;
        }

        if ($this->isDurationLogValue($lowerKey, $lowerName, $value)) {
            return false;
        }

        return (bool)(
            preg_match('/(^|_)at$|(^|_)time$|date|timestamp/u', $lowerKey)
            || preg_match('/дата|время|начало|завершение/u', $lowerName)
        );
    }

    protected function isLongLogValue($value)
    {
        $plain = trim(strip_tags($value));

        return strlen($value) > 900 || mb_strlen($plain, 'UTF-8') > 360 || (strip_tags($value) !== $value && mb_strlen($plain, 'UTF-8') > 180);
    }

    protected function renderCollapsedValue($value, $isPre = false)
    {
        $plain = trim(preg_replace('/\s+/u', ' ', strip_tags((string)$value)));
        $preview = mb_substr($plain, 0, 220, 'UTF-8');
        if (mb_strlen($plain, 'UTF-8') > 220) {
            $preview .= '...';
        }

        $full = $isPre
            ? Html::tag('pre', Html::encode((string)$value), ['class' => 'sx-log-value-pre'])
            : HtmlPurifier::process((string)$value);

        return Html::tag('span',
            Html::tag('span', Html::encode($preview), ['class' => 'sx-log-value-preview']).
            ' '.
            Html::button('Показать полностью', [
                'type'  => 'button',
                'class' => 'sx-log-value-toggle',
            ]).
            Html::tag('div', $full, ['class' => 'sx-log-value-full']),
            ['class' => 'sx-log-value-collapsed']
        );
    }

    public function render()
    {
        $name = ArrayHelper::getValue(\Yii::$app->skeeks->modelsConfig, [$this->model_code, 'name_one'], $this->model_code);

        if ($this->log_type == CmsLog::LOG_TYPE_COMMENT) {
            return $this->comment;
        } elseif ($this->log_type == CmsLog::LOG_TYPE_INSERT) {

            $res = [];

            if ($this->model) {
                $res[] = "<span title='ID:{$this->model_id}' data-toggle='tooltip'>{$name} «{$this->model_as_text}»</span>";
            } else {
                $res[] = "<span title='ID:{$this->model_id}' data-toggle='tooltip'>{$name} «{$this->model_as_text}»</span>";
            }

            $dataValues = (array)$this->data;

            if ($dataValues) {
                foreach ($dataValues as $key => $data) {
                    /*$res[] = "<span>" . (string) ArrayHelper::getValue($data, "name") . ": </span> <s style='color: gray;'>" .(string)  ArrayHelper::getValue($data, "old") . "</s> " . Html::tag('span', (string) ArrayHelper::getValue($data, "new", ''), [
                        'data-toggle' => 'tooltip',
                        'data-html'   => 'true',
                        'title'       => "<b>Старое значение: </b>" . (string) ArrayHelper::getValue($data, "old"),
                    ]);*/

                    $as_text = ArrayHelper::getValue($data, "as_text", '');

                    /*if (is_string($as_text)) {
                        if ($as_text == "") {
                            continue;
                        }
                    }*/

                    if (is_string($as_text)) {
                        if ($as_text == "") {
                            continue;
                        }
                    } else {
                        if (!$as_text) {
                            continue;
                        }
                    }


                    if (is_array($as_text)) {
                        $as_text = "<div class='sx-hidden-content'><a href='#'>Подробнее</a><div class='sx-hidden'><pre>".print_r($as_text, true)."</pre></div></div>";
                    }

                    $name = (string)ArrayHelper::getValue($data, "name");

                    if ($this->subModel) {
                        if ($this->subModel->getAttributeLabel($key)) {
                            $name = $this->subModel->getAttributeLabel($key);
                        }
                    } elseif ($this->model) {
                        if ($this->model->getAttributeLabel($key)) {
                            $name = $this->model->getAttributeLabel($key);
                        }
                    }

                    $as_text = $this->formatLogValue($key, $name, $as_text);

                    if ($as_text) {
                        $res[] = "<span>".$name.": </span>".Html::tag('span', $as_text, [
                                'data-toggle' => 'tooltip',
                                'data-html'   => 'true',
                                /*'title'       => "<b>Старое значение: </b>" . (string) ArrayHelper::getValue($data, "old_as_text"),*/
                            ]);
                    }

                }
            }

            $result = implode("<br />", $res);

            return $result;

        } elseif ($this->log_type == CmsLog::LOG_TYPE_UPDATE) {
            if ($this->model) {
                $res = [];

                $dataValues = (array)$this->data;

                if ($dataValues) {
                    foreach ($dataValues as $key => $data) {
                        $as_text = ArrayHelper::getValue($data, "as_text", '');

                        /*if (is_string($as_text)) {
                            if ($as_text == "") {
                                continue;
                            }
                        } else {
                            if (!$as_text) {
                                continue;
                            }
                        }*/
                        /*if (!$as_text || (is_string($as_text) && $as_text == "") ) {
                            continue;
                        }*/

                        if (is_array($as_text)) {
                            $as_text = "<div class='sx-hidden-content'><a href='#'>Подробнее</a><div class='sx-hidden'><pre>".print_r($as_text, true)."</pre></div></div>";
                        }

                        $name = (string)ArrayHelper::getValue($data, "name");

                        if ($this->subModel) {
                            if ($this->subModel->getAttributeLabel($key)) {
                                $name = $this->subModel->getAttributeLabel($key);
                            }
                        } elseif ($this->model) {
                            if ($this->model->getAttributeLabel($key)) {
                                $name = $this->model->getAttributeLabel($key);
                            }
                        }

                        $as_text = $this->formatLogValue($key, $name, $as_text);
                        $old_as_text = $this->formatLogValue($key, $name, ArrayHelper::getValue($data, "old_as_text"));
                        $data["old_as_text"] = $old_as_text;

                        $res[] = "<span>".$name.": </span>".Html::tag('span', $as_text, [
                                'data-toggle' => 'tooltip',
                                'data-html'   => 'true',
                                'title'       => "<b>Старое значение: </b>".(string)ArrayHelper::getValue($data, "old_as_text"),
                            ]);
                    }
                }

                $result = implode("<br />", $res);

                return $result;
            } else {

                $res = [];

                $dataValues = (array)$this->data;

                if ($dataValues) {
                    foreach ($dataValues as $key => $data) {
                        $name = (string)ArrayHelper::getValue($data, "name");
                        $as_text = $this->formatLogValue($key, $name, ArrayHelper::getValue($data, "as_text", ''));
                        $old_as_text = $this->formatLogValue($key, $name, ArrayHelper::getValue($data, "old_as_text"));
                        $data["old_as_text"] = $old_as_text;
                        $res[] = "<span>".$name.": </span>".Html::tag('span', $as_text, [
                                'data-toggle' => 'tooltip',
                                'data-html'   => 'true',
                                'title'       => "<b>Старое значение: </b>".(string)ArrayHelper::getValue($data, "old_as_text"),
                            ]);
                    }
                }

                $result = implode("<br />", $res);

                return $result;

                return "{$name} «{$this->model_as_text}»";
            }
        } elseif ($this->log_type == CmsLog::LOG_TYPE_DELETE) {
            return "<span title='ID:{$this->model_id}' data-toggle='tooltip'>{$name} «{$this->model_as_text}»</span>";
        } elseif ($this->log_type == CmsLog::LOG_TYPE_PHONE_CALL) {
            $callId = ArrayHelper::getValue($this->data, "id");

            $call = CmsTelephonyCall::findOne($callId);


            if ($callId && $call) {

                $status = $call->status;
                $directionText = $call->direction == CmsTelephonyCall::DIRECTION_IN ? "Входщий звонок" : "Исходщий звонок";
                $directionTextPrefix = $call->direction == CmsTelephonyCall::DIRECTION_IN ? "С номера" : "На номер";

                $durationText = '';
                if ($call->duration) {
                    $durationText = "<p><span class='sx-gray'>Длительность звонка:</span> " . \Yii::$app->formatter->asDuration($call->duration);
                }



                $statusIcon = "";
                if ($status == CmsTelephonyCall::STATUS_ANSWERED) {
                    if ($call->direction == CmsTelephonyCall::DIRECTION_IN) {
                        $statusIcon = '<svg xmlns="http://www.w3.org/2000/svg" height="48px" viewBox="0 -960 960 960" width="48px" fill="#555"><path d="M187-185.87v-415.78h79.22v280.43l492.91-492.35L814.7-758 321.78-265.65h280.44v79.78H187Z"/></svg>';
                    } else {
                        $statusIcon = '<svg xmlns="http://www.w3.org/2000/svg" height="48px" viewBox="0 -960 960 960" width="48px" fill="#555"><path d="M201.43-146.43 145.87-202l492.35-492.35H357.78v-79.78H773v415.78h-79.22v-280.43L201.43-146.43Z"/></svg>';
                    }
                } elseif ($status == CmsTelephonyCall::STATUS_FAILED) {
                    if ($call->direction == CmsTelephonyCall::DIRECTION_IN) {
                        $statusIcon = '<svg xmlns="http://www.w3.org/2000/svg" height="48px" viewBox="0 -960 960 960" width="48px" fill="#555"><path d="M482.74-252.43 185.09-550.09v198.44h-79.22v-337.79h330.22v79.79H238.22l244.52 245.08 316.83-316.82 56.13 56.56-372.96 372.4Z"/></svg>';
                    } else {
                        $statusIcon = '<svg xmlns="http://www.w3.org/2000/svg" height="48px" viewBox="0 -960 960 960" width="48px" fill="#555"><path d="m477.26-252.43-372.39-372.4 55.56-56.56 316.83 316.82 244.52-245.08H523.91v-79.79H854.7v337.79h-79.79v-198.44L477.26-252.43Z"/></svg>';
                    }
                }

                $audio = '';
                if ($call->cms_record_file_id) {
                    $size = \Yii::$app->formatter->asShortSize($call->cmsRecordFile->size);
                    $audio = <<<HTML
<div>
<figure>
    <p>
      <audio controls src="{$call->cmsRecordFile->src}"></audio>
  </p>
  <p>
      <a class="btn btn-xs btn-default" data-pjax="0" href="{$call->cmsRecordFile->src}" download="{$call->cmsRecordFile->name}.{$call->cmsRecordFile->extension}">Скачать аудио ({$size})</a>
  </p>
</figure>
</div>
HTML;

                }


                return <<<HTML
<div class="sx-tel-item {$status}">
    <div class="row">
    <div class="col-auto">
        <div class="sx-icon">
            <div class="sx-phone-icon">
                <svg xmlns="http://www.w3.org/2000/svg" height="48px" viewBox="0 -960 960 960" width="48px" fill="#555"><path d="M796.13-105.87q-119.45 0-242.46-57.13-123.02-57.13-228-162.11Q220.7-430.09 163.28-553.39q-57.41-123.3-57.41-242.18 0-25.01 16.89-42.07 16.9-17.06 41.67-17.06h140q23.61 0 38.98 13.68 15.37 13.67 21.07 37.72l26.93 116.59q3.2 21.14-1 37.45-4.19 16.3-16.94 27.92l-102.03 97.38q23.73 39.48 50.47 74.09 26.74 34.61 60.05 66.35 34.74 36.3 71.5 64.69t76.1 49l97.83-99.69q13.96-14.96 31.63-20.09 17.67-5.13 36.59-.87l107.69 24.3q24.05 6.83 37.72 23.4 13.68 16.56 13.68 39.95v138.4q0 25.09-17.1 41.83-17.1 16.73-41.47 16.73Zm-564.3-488.91 80.43-78.05-22.43-102.08H186.22q1.43 38.61 11.78 82.25 10.34 43.64 33.83 97.88Zm370.69 365.26q38.18 17.87 83.63 29.3 45.46 11.44 88.76 14v-104.64l-94.52-19.97-77.87 81.31ZM231.83-594.78Zm370.69 365.26Z"/></svg>
            </div>
            <div class="sx-phone-status-icon">
                {$statusIcon}
            </div>

        </div>
    </div>
    <div class="col">
    <div class="sx-detail">
        <div class="h5">{$directionText}</div>
        <div class=""><span class="sx-gray">{$directionTextPrefix}:</span> {$call->client_phone}</div>
        {$durationText}
        {$audio}
    </div>
    </div>
    </div>
</div>
HTML;
            } else {
                return <<<HTML
<div>Запись удалена</div>
HTML;
            }


        }
    }


    /**
     * @return CmsLogQuery
     */
    public static function find()
    {
        return new CmsLogQuery(get_called_class());
    }
}
