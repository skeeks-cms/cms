<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 02.06.2015
 */
namespace skeeks\cms\modules\admin\widgets\gridView;
use skeeks\cms\base\Component;
use skeeks\cms\components\Cms;
use yii\helpers\ArrayHelper;

/**
 * Class GridViewSettings
 * @package skeeks\cms\modules\admin\widgets\gridView
 */
class GridViewSettings extends Component
{
    static public function descriptorConfig()
    {
        return array_merge(parent::descriptorConfig(), [
            'name' => 'Настройки таблицы'
        ]);
    }

    public $enabledPjaxPagination       = Cms::BOOL_Y;
    public $pageSize                    = 10;
    public $pageParamName               = 'page';

    //Сортировка
    public $orderBy                     = "published_at";
    public $order                       = SORT_DESC;

    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(),
        [
            'pageParamName'             => 'Названия парамтера страниц, при постраничной навигации',
            'pageSize'                  => 'Количество записей на одной странице',

            'orderBy'                   => 'По какому параметру сортировать',
            'order'                     => 'Направление сортировки',
        ]);
    }

    public function rules()
    {
        return ArrayHelper::merge(parent::rules(),
        [
            [['enabledPaging'], 'string'],
            [['enabledPjaxPagination'], 'string'],
            [['pageParamName'], 'string'],
            [['pageSize'], 'string'],
            [['orderBy'], 'string'],
            [['order'], 'integer'],
            [['label'], 'string'],
            [['label'], 'string'],
            [['enabledSearchParams'], 'string'],
            [['limit'], 'integer'],
            [['active'], 'string'],
            [['createdBy'], 'safe'],
            [['content_ids'], 'safe'],
            [['enabledCurrentTree'], 'string'],
            [['enabledCurrentTreeChild'], 'string'],
            [['tree_ids'], 'safe'],
        ]);
    }
}