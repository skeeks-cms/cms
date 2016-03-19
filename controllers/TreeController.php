<?php
/**
 * TreeController
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 23.10.2014
 * @since 1.0.0
 */
namespace skeeks\cms\controllers;

use skeeks\cms\actions\ViewModelAction;
use skeeks\cms\actions\ViewModelActionSeo;
use skeeks\cms\base\Controller;
use skeeks\cms\models\Tree;
use skeeks\cms\models\User;
use Yii;
use skeeks\cms\models\searchs\User as UserSearch;

/**
 * Class UserController
 * @package skeeks\cms\controllers
 */
class TreeController extends Controller
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
                'class'     => 'skeeks\cms\actions\ViewModelActionTree',
                'view'      => 'default',
                'callback'  =>  [$this, 'viewTree']
            ],
        ];
    }

    /**
     * @param ViewModelActionSeo $action
     */
    public function viewTree(ViewModelAction $action)
    {
        \Yii::$app->cms->setCurrentTree($action->model);
        \Yii::$app->breadcrumbs->setPartsByTree($action->model);
    }
}
