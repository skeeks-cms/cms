<?php
/**
 * Ранее вы смотрели. С сохранением в сессиях.
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.04.2015
 */
namespace skeeks\cms\components;

use skeeks\cms\models\Lang;
use Yii;
use yii\base\Component;
use yii\helpers\ArrayHelper;

/**
 * Class LastViewSessionStorage
 * @package skeeks\cms\components
 */
class LastViewSessionStorage extends Component
{
    public $sessionNamespace = "lastViews";

    public function save($id, $type = 'default')
    {
        $saved = $this->getAllByType($type);
        $saved[\Yii::$app->formatter->asTimestamp(time())] =
    }

    /**
     *
     * Получение всех данных из сессии по определнному типу.
     *
     * @param string $type
     * @return array
     */
    public function getAllByType($type = 'default')
    {
        $saved = \Yii::$app->session->{$sessionNamespace};
        if (!$saved)
        {
            return [];
        }

        return (array) ArrayHelper::getValue($saved, $type);
    }
}