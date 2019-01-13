<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 05.08.2015
 */

namespace skeeks\cms\grid;

use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\CmsContentElement;
use yii\grid\DataColumn;
use yii\helpers\Html;

/**
 * Class CmsContentElementColumn
 * @package skeeks\cms\grid
 */
class CmsContentElementColumn extends DataColumn
{
    public $filter = false;

    public $attribute = "element_id";

    public $relation = "element";

    /**
     * @inheritdoc
     */
    protected function renderDataCellContent($model, $key, $index)
    {
        /**
         * @var $element CmsContentElement
         */
        if ($this->relation) {
            $element = $model->{$this->relation};
            if (!$element) {
                return null;
            } else {
                return Html::a($element->name . " [$element->id]", $element->url, [
                        'target' => '_blank',
                        'data-pjax' => 0,
                        'title' => 'Посмотреть на сайте (откроется в новом окне)',
                    ]) . " " .
                    Html::a('<span class="fa fa-edit"></span>',
                        UrlHelper::construct('/cms/admin-cms-content-element/update', [
                            'content_id' => $element->content_id,
                            'pk' => $element->id,
                        ]), [
                            'data-pjax' => 0,
                            'class' => 'btn btn-xs btn-default',
                        ]);
            }
        }

        return null;
    }
}