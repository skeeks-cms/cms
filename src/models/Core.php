<?php
/**
 * Базовая модель содержит поведения пользователей, кто когда обновил, и создал сущьность
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 31.10.2014
 * @since 1.0.0
 */

namespace skeeks\cms\models;

/**
 * @method string getTableCacheTag()
 *
 * @property integer $created_by
 * @property integer $updated_by
 * @property integer $created_at
 * @property integer $updated_at
 *
 * @property User    $createdBy
 * @property User    $updatedBy
 *
 * @deprecated
 */
abstract class Core extends \skeeks\cms\base\ActiveRecord
{

}