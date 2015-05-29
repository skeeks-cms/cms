<?php
/**
 * ViewModelActionTree
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 16.01.2015
 * @since 1.0.0
 */
namespace skeeks\cms\actions;


use skeeks\cms\models\Tree;
use Yii;

/**
 * Class ViewModelActionSeo
 * @package skeeks\cms\actions
 */
class ViewModelActionTree extends ViewModelAction
{
    /**
     * @var Tree
     */
    public $model;


    /**
     * @param $id
     * @return string
     * @throws InvalidConfigException
     * @throws \yii\web\HttpException
     */
    public function run($id)
    {
        $this->model   = \Yii::$app->cms->getCurrentTree();

        if (!$this->model)
        {
            $treeNode           = Tree::find()->where([
                'id' => $id
            ])->one();

            \Yii::$app->cms->setCurrentTree($treeNode);
            $this->model   = \Yii::$app->cms->getCurrentTree();
        }

        //Пробуем рендерить view для текущего типа страницы
        if ($this->model)
        {
            if ($this->model->treeType)
            {
                $this->view = $this->model->treeType->code;
            }
        }

        return $this->_go();
    }

}