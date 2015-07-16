<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 15.07.2015
 */
namespace skeeks\cms\models;

use skeeks\cms\base\db\ActiveRecord;
use skeeks\cms\components\Cms;
use Yii;
use yii\db\ActiveQuery;

/**
 * This is the model class for table "{{%cms_agent}}".
 *
 * @property integer $id
 * @property integer $last_exec_at
 * @property integer $next_exec_at
 * @property string $name
 * @property string $description
 * @property integer $agent_interval
 * @property integer $priority
 * @property string $active
 * @property string $is_period
 * @property string $is_running
 */
class CmsAgent extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_agent}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['last_exec_at', 'next_exec_at', 'agent_interval', 'priority'], 'integer'],
            [['name'], 'required'],
            [['description'], 'string'],
            [['name'], 'string'],
            [['active', 'is_period', 'is_running'], 'string', 'max' => 1],
            [['active', 'is_period', 'is_running'], 'in', 'range' => array_keys(Yii::$app->cms->booleanFormat())],

            [['active'], 'default', 'value' => Cms::BOOL_Y],
            [['is_period'], 'default', 'value' => Cms::BOOL_Y],
            [['is_running'], 'default', 'value' => Cms::BOOL_N],
            [['agent_interval'], 'default', 'value' => 86400],
            [['priority'], 'default', 'value' => 100],
            [['next_exec_at'], 'default', 'value' => function(self $model)
            {
                return \Yii::$app->formatter->asTimestamp(time()) + (int) $model->agent_interval;
            }],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'last_exec_at' => Yii::t('app', 'Дата последнего запуска'),
            'next_exec_at' => Yii::t('app', 'Дата и время следующего запуска'),
            'name' => Yii::t('app', 'Функция агента'),
            'agent_interval' => Yii::t('app', 'Интервал (сек.)'),
            'priority' => Yii::t('app', 'Priority'),
            'active' => Yii::t('app', 'Active'),
            'is_period' => Yii::t('app', 'Периодический'),
            'is_running' => Yii::t('app', 'Is Running'),
            'description' => Yii::t('app', 'Description'),
        ];
    }


    /**
     * Агенты к выполнению
     *
     * @return ActiveQuery
     */
    static public function findForExecute()
    {
        return static::find()->active()
            ->andWhere([
                'is_running' => Cms::BOOL_N
            ])
            ->andWhere([
                '<=', 'next_exec_at', \Yii::$app->formatter->asTimestamp(time())
            ])
        ;
    }


    /**
     * Выполнить агента
     *
     * @return $this
     */
    public function execute()
    {
        //Если уже запщен, то не будем запускать еще раз.
        if ($this->is_running == Cms::BOOL_Y)
        {
            return $this;
        }

        //Перед выполнением отмечаем что он сейчас выполняется.
        $this->is_running = Cms::BOOL_Y;
        $this->save();

            system("cd " . ROOT_DIR . "; php yii " . $this->name);

        $this->is_running   = Cms::BOOL_N;
        $this->next_exec_at = \Yii::$app->formatter->asTimestamp(time()) + (int) $this->agent_interval;
        $this->last_exec_at = \Yii::$app->formatter->asTimestamp(time());
        $this->save();

        return $this;
    }
}