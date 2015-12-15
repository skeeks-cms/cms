<?php
/**
 * AuthItem
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 21.11.2014
 * @since 1.0.0
 */

namespace skeeks\cms\models;
use Yii;
use yii\db\ActiveRecord;
use yii\rbac\Item;
use yii\helpers\Json;
/**
 * This is the model class for table "tbl_auth_item".
 *
 * @property string $name
 * @property integer $type
 * @property string $description
 * @property string $ruleName
 * @property string $data
 *
 * @property CmsAuthAssignment[] $authAssignments
 * @property CmsUser[] $users
 */
class CmsAuthItem extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%auth_item}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['ruleName'], 'in',
                'range' => array_keys(Yii::$app->authManager->getRules()),
                'message' => 'Rule not exists'],
            [['name', 'type'], 'required'],
            [['type'], 'integer'],
            [['description', 'data', 'ruleName'], 'default'],
            [['name'], 'string', 'max' => 64]
        ];
    }
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'name' =>  Yii::t('app', 'Name'),
            'type' => Yii::t('app', 'Type'),
            'description' => Yii::t('app', 'Description'),
            'ruleName' => Yii::t('app', 'Rule'),
            'data' => Yii::t('app', 'Data'),
        ];
    }




    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAuthAssignments()
    {
        return $this->hasMany(CmsAuthAssignment::className(), ['item_name' => 'name']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUsers()
    {
        return $this->hasMany(CmsUser::className(), ['id' => 'user_id'])->viaTable('auth_assignment', ['item_name' => 'name']);
    }
}