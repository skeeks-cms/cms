<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 15.07.2015
 */
namespace skeeks\cms\models;

use Yii;

/**
 * This is the model class for table "{{%cms_agent}}".
 *
 * @property integer $id
 * @property integer $last_exec_at
 * @property integer $next_exec_at
 * @property string $name
 * @property integer $agent_interval
 * @property integer $priority
 * @property string $active
 * @property string $is_period
 * @property string $is_running
 */
class CmsAgent extends \yii\db\ActiveRecord
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
            [['name', 'next_exec_at'], 'required'],
            [['name'], 'string'],
            [['active', 'is_period', 'is_running'], 'string', 'max' => 1]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'last_exec_at' => Yii::t('app', 'Last Exec At'),
            'next_exec_at' => Yii::t('app', 'Next Exec At'),
            'name' => Yii::t('app', 'Name'),
            'agent_interval' => Yii::t('app', 'Agent Interval'),
            'priority' => Yii::t('app', 'Priority'),
            'active' => Yii::t('app', 'Active'),
            'is_period' => Yii::t('app', 'Is Period'),
            'is_running' => Yii::t('app', 'Is Running'),
        ];
    }
}