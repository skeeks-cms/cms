<?php
/**
 * TreeSeoPageName
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 07.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\validators\model;

use skeeks\cms\models\behaviors\TreeBehavior;
use skeeks\cms\models\Tree;
use skeeks\cms\validators\HasBehavior;
use skeeks\sx\validate\Validate;
use skeeks\sx\validators\Validator;
use yii\base\Behavior;
use yii\base\Component;
use yii\db\ActiveRecord;

class TreeSeoPageName
    extends Validator
{
    /**
     * @var Tree
     */
    protected $_model = null;

    public function __construct($model)
    {
        //Модель должна обладать поведением Tree
        Validate::ensure(new HasBehavior(TreeBehavior::className()), $model);
        $this->_model = $model;
    }

    /**
     * @param mixed $seoPageName
     * @return \skeeks\sx\validate\Result
     */
    public function validate($seoPageName)
    {
        if ($this->_model->isRoot())
        {
            /*$find   = $this->_model->findRoots()->where([$this->_model->pageAttrName => $seoPageName])->one();*/
            return $this->_ok();
        } else
        {
            $parent = $this->_model->findParent();
            if (!$parent)
            {
                return $this->_ok();
            }

            $find   = $parent->findChildrens()->where([
                $this->_model->pageAttrName => $seoPageName,
                $this->_model->pidAttrName => $this->_model->getPid()
            ])->one();
        }

        if ($find)
        {
            return $this->_bad(\Yii::t('app','{seoname} name is already used',['seoname' => $seoPageName]));
        }

        return $this->_ok();

    }


}