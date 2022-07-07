<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */
namespace skeeks\cms\callcheck;

use skeeks\cms\IHasConfigForm;
use skeeks\cms\models\CmsSmsMessage;
use skeeks\cms\models\CmsSmsProvider;
use skeeks\cms\shop\models\ShopOrder;
use skeeks\cms\traits\HasComponentDescriptorTrait;
use skeeks\cms\traits\TConfigForm;
use yii\base\Exception;
use yii\base\Model;
use yii\widgets\ActiveForm;

/**
 * @property Model $checkoutModel
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 */
abstract class CallcheckHandler extends Model implements IHasConfigForm
{
    use HasComponentDescriptorTrait;
    use TConfigForm;

    public function sendMessage(CmsSmsMessage $cmsSmsMessage)
    {
        $provider_message_id = $this->send($cmsSmsMessage->phone, $cmsSmsMessage->message);
        $cmsSmsMessage->provider_message_id = $provider_message_id;
    }

    /**
     * @param      $phone
     * @param      $text
     * @param null $sender
     * @return $message_id
     */
    abstract public function send($phone);

    /**
     * @return int
     */
    public function balance()
    {
        return 0;
    }

}