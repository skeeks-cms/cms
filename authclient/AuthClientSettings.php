<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 31.07.2015
 */
namespace skeeks\cms\authclient;

use skeeks\cms\components\Cms;
use yii\helpers\ArrayHelper;

/**
 * @property array $clients
 * Class AuthClientSettings
 * @package skeeks\cms\authclient
 */
class AuthClientSettings extends \skeeks\cms\base\Component
{

    public $enabled = false;

    //Настройки для гитхаба
    public $githubEnabled       = true;
    public $githubClientId      = '';
    public $githubClientSecret  = '';
    public $githubClass         = '';

    /**
     * Можно задать название и описание компонента
     * @return array
     */
    static public function descriptorConfig()
    {
        return array_merge(parent::descriptorConfig(), [
            'name'          => 'Настройки авторизации через социальные сети',
        ]);
    }

    /**
     * Файл с формой настроек, по умолчанию
     *
     * @return string
     */
    public function getConfigFormFile()
    {
        $class = new \ReflectionClass($this->className());
        return dirname($class->getFileName()) . DIRECTORY_SEPARATOR . '/_settingsFrom.php';
    }


    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [['enabled'], 'boolean'],

            [['githubEnabled'], 'boolean'],
            [['githubClientId'], 'string'],
            [['githubClientSecret'], 'string'],
            [['githubClass'], 'string'],
        ]);
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'enabled'                       => 'Включить',

            'githubEnabled'                 => 'Включить авторизацию через GitHub',
            'githubClientId'                => 'clientId',
            'githubClientSecret'            => 'clientSecret',
            'githubClass'                   => 'Обработчик',
        ]);
    }


    /**
     *
     * Инициализация гитхаб провайдера
     *
     * @param array $data
     * @return $this
     */
    protected function _initGitHubData(&$data = [])
    {
        if ($this->githubEnabled && $this->githubClientId && $this->githubClientSecret)
        {
            $data['github'] = [
                  'class'           => $this->githubClass ? $this->githubClass : 'yii\authclient\clients\GitHub',
                  'clientId'        => $this->githubClientId,
                  'clientSecret'    => $this->githubClientSecret,
            ];
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getClients()
    {
        $result = [];

        $this->_initGitHubData($result);

        return $result;
    }
}