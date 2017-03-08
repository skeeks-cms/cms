<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link https://skeeks.com/
 * @copyright 2010 SkeekS
 * @date 05.03.2017
 */
namespace skeeks\cms;
use yii\base\Model;

/**
 * @property Model $model;
 *
 * Interface IHasModel
 * @package skeeks\cms
 */
interface IHasModel
{
    /**
     * @return Model
     */
    public function getModel();
}