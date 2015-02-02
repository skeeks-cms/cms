<?php
/**
 * WidgetHasModels
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 02.02.2015
 * @since 1.0.0
 */
namespace skeeks\cms\widgets\base\hasModels;

use skeeks\cms\base\Widget;
use skeeks\cms\models\Publication;
use skeeks\cms\models\Search;
use skeeks\cms\models\Tree;
use skeeks\cms\widgets\base\hasTemplate\WidgetHasTemplate;
use Yii;
use yii\data\ActiveDataProvider;
use yii\data\Pagination;

/**
 * Class WidgetHasModels
 * @package skeeks\cms\widgets\base\hasModels
 */
class WidgetHasModels extends WidgetHasTemplate
{
    public $modelClassName          = null;

    public $sort                    = [];
    public $pagination              = [];


    public function buildSearch()
    {
        $search         = new Search($modelClassName);
        $dataProvider   = $search->search(\Yii::$app->request->queryParams);

        $this->_data->set('dataProvider',   $dataProvider);
        $this->_data->set('search',         $search);
    }
    /**
     * Подготовка данных для шаблона
     * @return $this
     */
    public function bind()
    {
        $this->buildSearch();
        die;
        return $this;
    }

}
