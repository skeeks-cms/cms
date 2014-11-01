<?php
/**
 * Action
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 01.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\modules\admin\descriptors;

use yii\base\Component;

/**
 * Class Action
 * @package skeeks\cms\modules\admin\descriptors
 */
class Action extends Component
{
    public $code    = "";

    public $label   = "";

    public $priority    = null;
    public $icon        = null;
    public $method      = null;
    public $confirm     = null;

    /**
     * @param string $code
     * @param array $data
     * @return static
     */
    static public function create($code, array $data = [])
    {
        return new static(array_merge($data, [
            "code" => $code
        ]));
    }
}