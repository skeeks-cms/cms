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

use Imagine\Image\ManipulatorInterface;
use skeeks\cms\models\helpers\ModelFilesGroups;
use skeeks\cms\models\StorageFile;
use yii\db\ActiveQuery;

/**
 * @method ModelFilesGroups     getFilesGroups()
 * @method ActiveQuery          findFiles()
 * @method StorageFile[]        getFiles()
 *
 * @method bool                 hasMainImage()              Есть ли изображение с меткой image
 * @method string               getMainImageSrc()           Получение первого изображения с меткой image
 * @method string               getPreviewMainImageSrc($width = 50, $height = 50, $mode = ManipulatorInterface::THUMBNAIL_OUTBOUND)    Получить стандартную превьюшку (используем для админки например)
 * @method array                getMainImagesSrc()          Получение всех изображений с меткой image
 * @method array                getImagesSrc()              Получить src[] изображений из группы images
 * @method array                getFilesSrc()               Получить src[] изображений из группы files
 *
 *
 * Class HasFiles
 * @package skeeks\cms\models\behaviors\traits
 */
trait HasFiles
{}