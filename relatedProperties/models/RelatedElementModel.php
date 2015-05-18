<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 18.05.2015
 */

namespace skeeks\cms\relatedProperties\models;

use skeeks\cms\models\behaviors\HasRelatedProperties;
use skeeks\cms\models\behaviors\traits\HasRelatedPropertiesTrait;
use skeeks\cms\models\Core;
use Yii;
use yii\web\ErrorHandler;

/**
 * @property integer $id
 * @property integer $created_by
 * @property integer $updated_by
 * @property integer $created_at
 * @property integer $updated_at
 * @property integer $published_at
 * @property integer $published_to
 * @property integer $priority
 * @property string $active
 * @property string $name
 * @property string $code
 * @property string $description_short
 * @property string $description_full
 * @property string $files
 * @property integer $content_id
 * @property integer $tree_id
 * @property integer $show_counter
 * @property integer $show_counter_start
 */
abstract class RelatedElementModel extends Core
{
    use HasRelatedPropertiesTrait;

    /**
     * @return array
     */
    /*public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            //TODO: необходимо настроить поведение содержания связанных свойств.
            HasRelatedProperties::className() =>
            [
                'class'                             => HasRelatedProperties::className(),
                'valuesRelatedPropertiesClassName'  => CmsContentElementProperty::className(),
                'relatedPropertiesClassName'        => CmsContentProperty::className(),
            ],
        ]);
    }*/
}