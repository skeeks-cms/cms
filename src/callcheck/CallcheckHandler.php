<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\callcheck;

use skeeks\cms\IHasConfigForm;
use skeeks\cms\models\CmsCallcheckMessage;
use skeeks\cms\traits\HasComponentDescriptorTrait;
use skeeks\cms\traits\TConfigForm;
use yii\base\Model;

/**
 * @property Model $checkoutModel
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 */
abstract class CallcheckHandler extends Model implements IHasConfigForm
{
    use HasComponentDescriptorTrait;
    use TConfigForm;

    /**
     * @param $phone
     * @return mixed
     */
    abstract public function callcheck($phone);


    abstract public function callcheckMessage(CmsCallcheckMessage $callcheckMessage);

    /**
     * @return int
     */
    public function balance()
    {
        return 0;
    }

}