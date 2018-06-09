<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link https://skeeks.com/
 * @copyright 2010 SkeekS
 * @date 05.03.2017
 */

namespace skeeks\cms;

use yii\base\BaseObject;
use yii\base\Component;
use yii\base\Model;
use yii\db\ActiveRecord;

/**
 * @property $model;
 *
 * Interface IHasModel
 * @package skeeks\cms
 */
interface IHasModel
{
    /**
     * @return Model|ActiveRecord
     */
    public function getModel();

    /**
     * @param Model|ActiveRecord|Component|Object $model
     * @return mixed
     */
    public function setModel($model);

}