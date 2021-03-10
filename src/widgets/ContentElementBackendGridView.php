<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\widgets;

use skeeks\cms\backend\actions\BackendGridModelAction;
use skeeks\cms\backend\widgets\FiltersWidget;
use skeeks\cms\backend\widgets\GridViewWidget;
use skeeks\cms\models\CmsContent;
use skeeks\cms\models\CmsContentElement;
use skeeks\cms\models\CmsContentElementProperty;
use skeeks\cms\models\CmsContentProperty;
use skeeks\cms\models\CmsContentPropertyEnum;
use skeeks\cms\modules\admin\actions\modelEditor\AdminModelEditorAction;
use skeeks\cms\queryfilters\QueryFiltersEvent;
use skeeks\yii2\config\ConfigBehavior;
use skeeks\yii2\form\fields\BoolField;
use skeeks\yii2\form\fields\TextField;
use skeeks\yii2\form\fields\WidgetField;
use skeeks\yii2\queryfilter\QueryFilterWidget;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class ContentElementBackendGridView extends GridViewWidget
{
    public function init()
    {
        $this->columnConfigCallback = function($code) {
            if (strpos($code, "property") != -1) {

                $propertyId = (int) str_replace("property", "", $code);
                /**
                 * @var $property CmsContentProperty
                 */
                $property = CmsContentProperty::findOne($propertyId);


                if (!$property) {
                    return [];
                }


                return [
                    'headerOptions' => [
                        'style' => 'width: 150px;'
                    ],
                    'contentOptions' => [
                        'style' => 'width: 150px;'
                    ],

                    'label'  => $property ? $property->name : "Свойство удалено",
                    'format' => 'raw',
                    'value'  => function ($model, $key, $index) use ($property) {
                        if (!$property) {
                            return '';
                        }
                        /**
                         * @var $model \skeeks\cms\models\CmsContentElement
                         */
                        return $model->relatedPropertiesModel->getAttributeAsHtml($property->code);
                    },
                ];

                return $result;
            }
        };

        //$this->initFiltersModel();

        parent::init();
    }

    /**
     * @param $callableData
     * @return array
     */
    public function getAvailableColumns($callableData)
    {
        $result = parent::getAvailableColumns($callableData);


        $content_id = ArrayHelper::getValue($callableData, 'callAttributes.contextData.content_id');
        $cmsContent = CmsContent::findOne($content_id);

        $properties = $cmsContent->getCmsContentProperties();
        $properties->andWhere([
            'or',
            [CmsContentProperty::tableName().'.cms_site_id' => \Yii::$app->skeeks->site->id],
            [CmsContentProperty::tableName().'.cms_site_id' => null],
        ]);
        $properties = $properties->all();

        /**
         * @var CmsContentProperty $property
         */
        foreach ($properties as $property) {
            $result["property{$property->id}"] = $property->name;
        }

        return $result;
    }
}



