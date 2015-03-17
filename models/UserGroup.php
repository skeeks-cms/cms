<?php
/**
 * UserGroup
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 06.11.2014
 * @since 1.0.0
 */

namespace skeeks\cms\models;

use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\behaviors\HasFiles;
use skeeks\cms\models\behaviors\HasRef;
use Yii;
use yii\db\BaseActiveRecord;

/**
 * Class Publication
 * @package skeeks\cms\models
 */
class UserGroup extends Core
{
    use behaviors\traits\HasFiles;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_user_group}}';
    }

    /**
     * Логины которые нельзя удалять, и нельзя менять
     * @return array
     */
    static public function getProtectedGroups()
    {
        return ['root', 'admin', 'manager', 'user'];
    }


    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        $this->on(BaseActiveRecord::EVENT_BEFORE_DELETE,    [$this, "checkDataBeforeDelete"]);
    }


    /**
     * @throws Exception
     */
    public function checkDataBeforeDelete()
    {
        if (in_array($this->groupname, static::getProtectedGroups()))
        {
            throw new Exception('Эту группу нельзя удалить');
        }

    }


    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            HasFiles::className() =>
            [
                "class"  => HasFiles::className(),
            ],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            ['groupname', 'string', 'min' => 3, 'max' => 12],

            [['groupname'], 'required'],
            [['description', 'groupname'], 'string'],
            [['groupname'], 'unique'],
            [["images", "files", "image_cover", "image"], 'safe'],
        ]);
    }


    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return  array_merge(parent::attributeLabels(), [
            'id' => Yii::t('app', 'ID'),
            'groupname' => Yii::t('app', 'Groupname'),
            'description' => Yii::t('app', 'Description'),
            'image' => Yii::t('app', 'Image'),
            'image_cover' => Yii::t('app', 'Image Cover'),
            'images' => Yii::t('app', 'Images'),
            'files' => Yii::t('app', 'Files'),
        ]);
    }

}
