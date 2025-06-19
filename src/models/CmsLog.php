<?php

namespace skeeks\cms\models;

use skeeks\cms\base\ActiveRecord;
use skeeks\cms\models\behaviors\HasJsonFieldsBehavior;
use skeeks\cms\models\behaviors\HasStorageFileMulti;
use skeeks\cms\models\queries\CmsLogQuery;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
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
            self::LOG_TYPE_COMMENT => 'Комментарий',
            self::LOG_TYPE_UPDATE  => 'Обновление данных',
            self::LOG_TYPE_INSERT  => 'Создание записи',
            self::LOG_TYPE_DELETE  => 'Удаление',
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
                        $res[] = "<span>".(string)ArrayHelper::getValue($data, "name").": </span>".Html::tag('span', (string)ArrayHelper::getValue($data, "as_text", ''), [
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