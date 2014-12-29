<?php
/**
 * Imaging
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 11.12.2014
 * @since 1.0.0
 */

namespace skeeks\cms\components;

use skeeks\cms\components\imaging\Filter;
use skeeks\cms\components\imaging\validators\AllowExtension;
use skeeks\cms\helpers\UrlHelper;
use skeeks\sx\File;
use skeeks\sx\String;
use skeeks\sx\validate\Validate;
use yii\base\Component;
use Yii;
use yii\helpers\FileHelper;

/**
 * Class Imaging
 * @package skeeks\cms\components
 */
class Imaging extends Component
{
    public $extensions      = ["jpg", "png", "jpeg", "gif"];
    public $splitLongNames  = 100;


    public function getImagingUrl($imageSrc, Filter $filter)
    {
        $imageSrc               = (string) $imageSrc;
        $sourceOriginalFile     = File::object($imageSrc);

        $extension              = $sourceOriginalFile->getExtension();

        if ($extension)
        {
            if (Validate::validate(new AllowExtension(), $extension)->isInvalid())
            {
                return $imageSrc;
            }
        }

        $imageSrcResult = str_replace('.' . $extension, DIRECTORY_SEPARATOR . $this->_assembleParams($filter) . '.' . $extension, $imageSrc);
        return $imageSrcResult;
    }


    /**
     * @param $filterCode
     * @return string
     */
    protected function _assembleParams(Filter $filter)
    {
        $params[] = $filter->getId();

        if ($filter->getConfig())
        {
            $params[] = $filter->getConfig();
        }

        $result = String::compressBase64EncodeUrl($params);

        $result = str_split($result, $this->splitLongNames);
        $result = implode("/", $result);

        return $result;
    }
}