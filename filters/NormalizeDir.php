<?php
/**
 * NormalizeDir
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 15.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\filters;

use skeeks\sx\Filter;

class NormalizeDir extends Filter
{
    /**
     * @param string $dir
     * @return string
     */
    public function filter($dir)
    {
        $result = [];

        $data = explode(DIRECTORY_SEPARATOR, $dir);
        foreach ($data as $value)
        {
            if ($value)
            {
                $result[] = $value;
            }
        }
        return implode(DIRECTORY_SEPARATOR, $result);
    }
}