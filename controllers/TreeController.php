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

use skeeks\cms\base\Controller;
use skeeks\cms\models\Tree;
use skeeks\cms\models\User;
use Yii;
use skeeks\cms\models\searchs\User as UserSearch;
use \skeeks\cms\App;

/**
 * Class UserController
 * @package skeeks\cms\controllers
 */
class TreeController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [];
    }

    /**
     * @param Tree $model
     * @return string
     */
    public function actionView(Tree $model)
    {
        $viewName = 'default';

        //Если задан тип страницы
        if ($type = $model->getType())
        {
            //Если у этого типа задан шаблон по умолчанию
            if ($template = $type->getTemplate())
            {
                $viewName = $template->id;
            }

            if ($layout = $type->getLayout())
            {
                $this->layout = $layout->path;
            }
        }



        return $this->render($viewName, [
            'model' => $model
        ]);
    }

}
