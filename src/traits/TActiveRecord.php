<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\traits;

/**
 * @property string $asText;
 * @property string $asHtml;
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 */
trait TActiveRecord
{
    /**
     * @return string
     */
    public function __toString()
    {
        return $this->asText();
    }
    /**
     * @return string
     */
    public function asText()
    {
        $result = [];
        $result[] = "#".$this->id;

        if (isset($this->name) && is_string($this->name)) {
            $result[] = $this->name;
        } else if (isset($this->label) && is_string($this->label)) {
            $result[] = $this->label;
        }

        return implode("#", $result);
    }

    /**
     * @return string
     */
    public function getAsText()
    {
        return $this->asText();
    }

    /**
     * @return string
     */
    public function getAsHtml()
    {
        return $this->asHtml();
    }

    /**
     * @return string
     */
    public function asHtml()
    {
        return $this->asText();
    }
}