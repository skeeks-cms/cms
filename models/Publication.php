<?php
/**
 * Publication
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 31.10.2014
 * @since 1.0.0
 */

namespace skeeks\cms\models;

use skeeks\cms\helpers\UrlHelper;
use Yii;

/**
 * Class Publication
 * @package skeeks\cms\models
 */
class Publication extends PageAdvanced
{
    public $viewPageTemplate = "cms/publication/view";

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%publication}}';
    }


    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'type' => Yii::t('app', 'Publication type'),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['type'], 'string'],
        ]);
    }

}
