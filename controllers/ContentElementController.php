<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 24.05.2015
 */
namespace skeeks\cms\controllers;

use skeeks\cms\actions\ViewModelAction;
use skeeks\cms\actions\ViewModelContentElement;
use skeeks\cms\base\Controller;
use skeeks\cms\models\Publication;
use skeeks\cms\models\searchs\Publication as PublicationSearch;
use Yii;


/**
 * Class ContentElementController
 * @package skeeks\cms\controllers
 */
class ContentElementController extends Controller
{
    /**
     * @inheritdoc
     */
    public function actions()
    {
        return
        [
            'view' =>
            [
                'class'                 => 'skeeks\cms\actions\ViewModelContentElement',
                'callback'              =>  [$this, 'viewContentElement'],
                'view'                  =>  'default'
            ],
        ];
    }

    /**
     * @param ViewModelContentElement $action
     */
    public function viewContentElement(ViewModelContentElement $action)
    {
        $contentElement     = $action->model;
        $tree               = $contentElement->cmsTree;

        if ($tree)
        {
            \Yii::$app->cms->setCurrentTree($tree);
            \Yii::$app->breadcrumbs->setPartsByTree($tree);

            \Yii::$app->breadcrumbs->append([
                'url'   => $contentElement->url,
                'name'  => $contentElement->name
            ]);
        }
    }
}
