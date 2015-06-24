<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 24.06.2015
 */
namespace skeeks\cms\components\marketplace;
use skeeks\yii2\curl\Curl;
use yii\base\Component;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

/**
 * @property string $url;
 * @property string $baseUrl;
 *
 * Class MarketPlaceApi
 * @package skeeks\cms\components\marketplace
 */
class MarketplaceApi extends Component
{
    const RESPONSE_FORMAT_JSON = 'json';

    public $schema          = "http";
    public $host            = "cms.skeeks.com";
    public $version         = "v1";

    public $responseFormat  = self::RESPONSE_FORMAT_JSON;

    /**
     * Базовый путь к апи, без версии
     *
     * Пример http://cms.skeeks.com/v1/
     *
     * @return string
     */
    public function getUrl()
    {
        if ($this->version)
        {
            $this->baseUrl . $this->version . "/";
        }

        return $this->baseUrl;
    }

    /**
     * Базовый путь к апи, с версией
     *
     * Пример http://cms.skeeks.com/
     *
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->schema . "://" . $this->host . "/";
    }

    /**
     * @param string $method
     * @param string|array $route
     * @return array
     * @throws \yii\base\Exception
     */
    public function request($method, $route)
    {
        $curl = new Curl();

        $curl->setOption(CURLOPT_HTTPHEADER, [
            'Accept: application/' . $this->responseFormat. '; q=1.0, */*; q=0.1'
        ]);

        if (is_string($route))
        {
            $url = $this->url . $route;
        } else if (is_array($route))
        {
            list($route, $data) = $route;
            $url = $this->url . $route;
        }

        $curl->httpRequest($method, $url);
        if ($curl->response)
        {
            return (array) Json::decode($curl->response);
        }

        return [];
    }
}