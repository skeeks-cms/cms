<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 18.05.2015
 */
namespace skeeks\cms\models\behaviors\traits;
use skeeks\cms\relatedProperties\models\RelatedElementPropertyModel;
use skeeks\cms\relatedProperties\models\RelatedPropertiesModel;
use skeeks\cms\relatedProperties\models\RelatedPropertyModel;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * @method ActiveQuery getRelatedElementProperties()
 * @method ActiveQuery getRelatedProperties()
 * @method RelatedPropertiesModel getRelatedPropertiesModel()
 *
 * @property RelatedElementPropertyModel[]    relatedElementProperties
 * @property RelatedPropertyModel[]           relatedProperties
 * @property RelatedPropertiesModel           relatedPropertiesModel
 */
trait HasRelatedPropertiesTrait
{}