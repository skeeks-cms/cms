<?php
/**
 * Вспомогательные иструменты
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.04.2015
 */
namespace skeeks\cms\controllers;

use skeeks\cms\base\Controller;
use Yii;
use yii\web\Response;


/**
 * Class ToolsController
 * @package skeeks\cms\controllers
 */
class ToolsController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [];
    }


    /**
     * Выбор файла
     * @return string
     */
    public function actionSelectFile()
    {
        $this->layout = '@skeeks/cms/modules/admin/views/layouts/main.php';
        return $this->render($this->action->id);
    }

}
