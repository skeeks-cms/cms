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
     * @param $seo_page_name
     * @return string
     */
    public function run($id)
    {
        $this->_model   = \Yii::$app->cms->getCurrentTree();
        return $this->_go();
    }

}