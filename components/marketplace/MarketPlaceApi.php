<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 24.06.2015
 */
namespace skeeks\cms\components\marketplace;
use skeeks\cms\helpers\Curl;
use yii\base\Component;
use yii\helpers\Json;

/**
 * @property string $baseUrl;
 *
 * Class MarketPlaceApi
 * @package skeeks\cms\components\marketplace
 */
class MarketPlaceApi extends Component
{
    public $schema          = "http";
    public $host            = "cms.skeeks.com";
    public $version         = "v1";

    /**
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->schema . "://" . $this->host . "/" . $this->version . "/";
    }

    /**
     * @param string $method
     * @param array $data
     * @return []
     */
    public function get($method, $data = [])
    {
        $curl = new Curl();

        $curl->setOption(CURLOPT_HTTPHEADER, [
            'Accept: application/json; q=1.0, */*; q=0.1'
        ]);

        $url = $this->baseUrl . $method . "?" . http_build_query($data);
        $curl->get($url);

        return Json::decode($curl->response);
    }

}