<?php
/**
 * WidgetHasModelsSmart
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 02.02.2015
 * @since 1.0.0
 */
namespace skeeks\cms\widgets\base\hasModelsSmart;

use skeeks\cms\base\Widget;
use skeeks\cms\models\Publication;
use skeeks\cms\models\Search;
use skeeks\cms\models\Tree;
use skeeks\cms\widgets\base\hasModels\WidgetHasModels;
use skeeks\cms\widgets\base\hasTemplate\WidgetHasTemplate;
use Yii;
use yii\data\ActiveDataProvider;
use yii\data\Pagination;

/**
 * Class WidgetHasModelsSmart
 * @package skeeks\cms\widgets\base\hasModelsSmart
 */
class WidgetHasModelsSmart extends WidgetHasModels
{
    public $defaultSortField        = 'id';
    public $defaultSort             = SORT_DESC;

    public $defaultPageSize         = 10;

    public $createdBy               = [];
    public $updatedBy               = [];

    /**
     * @var bool
     */
    public $applySearchParams       = 1;


    /**
     * Подготовка данных для шаблона
     * @return $this
     */
    public function bind()
    {
        $this->buildSearch();
        if ($this->applySearchParams)
        {
            $this->_data->search->search(\Yii::$app->request->queryParams);
        }
        return $this;
    }

}
