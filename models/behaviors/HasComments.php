<?php
/**
 * Поведение которое перед удалением сущьности, собирается удалить комментарии связанные с ней.
 * TODO: добавить опции наподобии как в БД
 * ON_DELETE ON_UPDATE
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 20.10.2014
 * @since 1.0.0
 */
namespace skeeks\cms\models\behaviors;

use skeeks\cms\models\Comment;
use yii\db\BaseActiveRecord;
use \yii\base\Behavior;

/**
 * Class HasComments
 * @package skeeks\cms\models\behaviors
 */
class HasComments extends HasLinkedModels
{
    public $canBeLinkedModels       = ['skeeks\cms\models\Comment'];
    public $restrictMessageError    = "Невозможно удалить запись, для начала необходимо удалить все связанные комментарии";
}