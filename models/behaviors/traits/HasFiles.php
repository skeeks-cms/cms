<?php
/**
 * HasFiles
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 21.10.2014
 * @since 1.0.0
 */
namespace skeeks\cms\models\behaviors\traits;

use skeeks\cms\models\helpers\ModelFilesGroups;
use skeeks\cms\models\StorageFile;
use yii\db\ActiveQuery;

/**
 * @method ModelFilesGroups     getFilesGroups()
 * @method ActiveQuery          findFiles()
 * @method StorageFile[]        getFiles()
 *
 * Class HasFiles
 * @package skeeks\cms\models\behaviors\traits
 */
trait HasFiles
{}