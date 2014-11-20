<?php
/**
 * ViewModelActionSeo
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 20.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\actions;


use Yii;

/**
 * Class ViewModelActionSeo
 * @package skeeks\cms\actions
 */
class ViewModelActionSeo extends ViewModelAction
{
    public $modelClassName;

    /**
     * @param $seo_page_name
     * @return string
     */
    public function run($seo_page_name)
    {
        $className      = $this->modelClassName;
        $this->_model   = $className::findOne(['seo_page_name' => $seo_page_name]);;

        return $this->_go();
    }

}