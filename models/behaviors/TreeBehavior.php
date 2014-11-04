<?php
/**
 * TreeBehavior
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 04.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\models\behaviors;

use skeeks\cms\base\behaviors\ActiveRecord as ActiveRecordBehavior;
use skeeks\cms\base\db\ActiveRecord;

/**
 * Class HasVotes
 * @package skeeks\cms\models\behaviors
 */
class TreeBehavior extends ActiveRecordBehavior
{
    public $pid         = "pid";
    public $pids        = "pids";
    public $level       = "level";
    public $dir         = "dir";

    public function events()
	{
		return [
			/*ActiveRecord::EVENT_INIT => 'afterConstruct',
			ActiveRecord::EVENT_AFTER_FIND => 'afterFind',
			ActiveRecord::EVENT_BEFORE_INSERT => 'beforeSave',
			ActiveRecord::EVENT_BEFORE_UPDATE => 'beforeSave',
			ActiveRecord::EVENT_BEFORE_DELETE => 'beforeDelete',*/
		];
	}



}