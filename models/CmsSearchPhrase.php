<?php

namespace skeeks\cms\models;

use skeeks\cms\models\behaviors\Serialize;
use Yii;
use yii\helpers\ArrayHelper;
use yii\web\Request;

/**
 * This is the model class for table "{{%cms_search_phrase}}".
 *
 * @property integer $id
 * @property integer $created_by
 * @property integer $updated_by
 * @property integer $created_at
 * @property integer $updated_at
 * @property string $phrase
 * @property integer $result_count
 * @property integer $pages
 * @property string $ip
 * @property string $site_code
 * @property string $data_server
 * @property string $data_session
 * @property string $data_cookie
 * @property string $data_request
 * @property string $session_id
 *
 * @property CmsSite $site
 */
class CmsSearchPhrase extends \skeeks\cms\models\Core
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_search_phrase}}';
    }

    /**
     * @return array
     */
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [

            Serialize::className() =>
            [
                'class' => Serialize::className(),
                'fields' => ['data_server', 'data_session', 'data_cookie', 'data_request']
            ],

        ]);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [['created_by', 'updated_by', 'created_at', 'updated_at', 'result_count', 'pages'], 'integer'],
            [['data_server', 'data_session', 'data_cookie', 'data_request'], 'string'],
            [['phrase'], 'string', 'max' => 255],
            [['ip'], 'string', 'max' => 32],
            [['site_code'], 'string', 'max' => 15],

            ['data_request', 'default', 'value' => $_REQUEST],
            ['data_server', 'default', 'value' => $_SERVER],
            ['data_cookie', 'default', 'value' => $_COOKIE],
            ['data_session', 'default', 'value' => function(self $model, $attribute)
            {
                \Yii::$app->session->open();
                return $_SESSION;
            }],
            ['session_id', 'default', 'value' => function(self $model, $attribute)
            {
                \Yii::$app->session->open();
                return \Yii::$app->session->id;
            }],

            [['site_code'], 'default', 'value' => function(self $model, $attribute)
            {
                if (\Yii::$app->cms->site)
                {
                    return \Yii::$app->cms->site->code;
                }

                return null;
            }],

            ['ip', 'default', 'value' => \skeeks\cms\helpers\Request::getRealUserIp()],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'id' => Yii::t('app', 'ID'),
            'session_id' => Yii::t('app', 'Session ID'),
            'created_by' => Yii::t('app', 'Created By'),
            'updated_by' => Yii::t('app', 'Updated By'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
            'phrase' => Yii::t('app', 'Поисковая фраза'),
            'result_count' => Yii::t('app', 'Найдено документов'),
            'pages' => Yii::t('app', 'Количество страниц'),
            'ip' => Yii::t('app', 'Ip'),
            'site_code' => Yii::t('app', 'Site'),
            'data_server' => Yii::t('app', 'Data Server'),
            'data_session' => Yii::t('app', 'Data Session'),
            'data_cookie' => Yii::t('app', 'Data Cookie'),
            'data_request' => Yii::t('app', 'Data Request'),
        ]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSite()
    {
        return $this->hasOne(CmsSite::className(), ['code' => 'site_code']);
    }
}