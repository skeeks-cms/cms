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

    //Настройки для vk
    public $vkEnabled       = true;
    public $vkClientId      = '';
    public $vkClientSecret  = '';
    public $vkClass         = '';

    //Настройки для facebook
    public $facebookEnabled       = true;
    public $facebookClientId      = '';
    public $facebookClientSecret  = '';
    public $facebookClass         = '';

    /**
     * Можно задать название и описание компонента
     * @return array
     */
    static public function descriptorConfig()
    {
        return array_merge(parent::descriptorConfig(), [
            'name'          => \Yii::t('app','Authorization through social networks'),
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

            [['vkEnabled'], 'boolean'],
            [['vkClientId'], 'integer'],
            [['vkClientSecret'], 'string'],
            [['vkClass'], 'string'],

            [['facebookEnabled'], 'boolean'],
            [['facebookClientId'], 'integer'],
            [['facebookClientSecret'], 'string'],
            [['facebookClass'], 'string'],
        ]);
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'enabled'                       => \Yii::t('app','Enable'),

            'githubEnabled'                 => \Yii::t('app','Enable authorization through {github}',['github' => 'GitHub']),
            'githubClientId'                => 'clientId',
            'githubClientSecret'            => 'clientSecret',
            'githubClass'                   => \Yii::t('app','Handler'),

            'vkEnabled'                 => 'Включить авторизацию через vk',
            'vkClientId'                => 'clientId',
            'vkClientSecret'            => 'clientSecret',
            'vkClass'                   => 'Обработчик',

            'facebookEnabled'                 => 'Включить авторизацию через facebook',
            'facebookClientId'                => 'clientId',
            'facebookClientSecret'            => 'clientSecret',
            'facebookClass'                   => 'Обработчик',
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
     *
     * Инициализация гитхаб провайдера
     *
     * @param array $data
     * @return $this
     */
    protected function _initVkData(&$data = [])
    {
        if ($this->vkEnabled && $this->vkClientId && $this->vkClientSecret)
        {
            $data['vkontakte'] = [
                  'class'           => $this->vkClass ? $this->vkClass : 'yii\authclient\clients\VKontakte',
                  'clientId'        => $this->vkClientId,
                  'clientSecret'    => $this->vkClientSecret,
            ];
        }

        return $this;
    }
    /**
     *
     * Инициализация гитхаб провайдера
     *
     * @param array $data
     * @return $this
     */
    protected function _initFacebookData(&$data = [])
    {
        if ($this->vkEnabled && $this->vkClientId && $this->vkClientSecret)
        {
            $data['facebook'] = [
                  'class'           => $this->facebookClass ? $this->facebookClass : 'yii\authclient\clients\Facebook',
                  'clientId'        => $this->facebookClientId,
                  'clientSecret'    => $this->facebookClientSecret,
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
        $this->_initVkData($result);
        $this->_initFacebookData($result);

        return $result;
    }
}