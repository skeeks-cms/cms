<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 24.06.2015
 *
 * @link      http://www.linslin.org
 * @link      https://github.com/linslin/Yii2-Curl
 */

namespace skeeks\cms\helpers;

use skeeks\cms\base\Component;
use Yii;
use yii\base\Exception;
use yii\helpers\Json;
use yii\web\HttpException;

/**
 * @property array $options
 *
 * Class Curl
 * @package skeeks\cms\helpers
 */
class Curl
    extends Component
{
    public function init()
    {
        if (!function_exists('curl_init'))
        {
            throw new \InvalidArgumentException('Не установлена библиотека php — curl');
        }
    }
    /**
     * @var string
     * Holds response data right after sending a request.
     */
    public $response = null;
    /**
     * @var integer HTTP-Status Code
     * This value will hold HTTP-Status Code. False if request was not successful.
     */
    public $responseCode = null;

    /**
     * @var array
     */
    private $_options = [];

    /**
     * @var array
     */
    private $_defaultOptions = [
        CURLOPT_USERAGENT      => 'SkeekS-Cms-Curl-Agent',
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_CONNECTTIMEOUT => 30,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => false,
    ];

    // ############################################### class methods // ##############################################
    /**
     * Start performing GET-HTTP-Request
     *
     * @param string  $url
     *
     * @return mixed response
     */
    public function get($url)
    {
        return $this->_httpRequest('GET', $url);
    }
    /**
     * Start performing HEAD-HTTP-Request
     *
     * @param string $url
     * @param string $body
     *
     * @return mixed response
     */
    public function head($url)
    {
        return $this->_httpRequest('HEAD', $url);
    }
    /**
     * Start performing POST-HTTP-Request
     *
     * @param string  $url
     * @param string  $body
     *
     * @return mixed response
     */
    public function post($url)
    {
        return $this->_httpRequest('POST', $url);
    }
    /**
     * Start performing PUT-HTTP-Request
     *
     * @param string  $url
     * @param string  $body
     *
     * @return mixed response
     */
    public function put($url)
    {
        return $this->_httpRequest('PUT', $url);
    }
    /**
     * Start performing DELETE-HTTP-Request
     *
     * @param string  $url
     * @param string  $body
     *
     * @return mixed response
     */
    public function delete($url)
    {
        return $this->_httpRequest('DELETE', $url);
    }
    /**
     * Set curl option
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return $this
     */
    public function setOption($key, $value)
    {
        //set value
        $this->_options[$key] = $value;
        //return self
        return $this;
    }
    /**
     * Unset a single curl option
     *
     * @param string $key
     *
     * @return $this
     */
    public function unsetOption($key)
    {
        //reset a single option if its set already
        if (isset($this->_options[$key])) {
            unset($this->_options[$key]);
        }
        return $this;
    }
    /**
     * Unset all curl option, excluding default options.
     *
     * @return $this
     */
    public function unsetOptions()
    {
        //reset all options
        if (isset($this->_options)) {
            $this->_options = array();
        }
        return $this;
    }
    /**
     * Total reset of options, responses, etc.
     *
     * @return $this
     */
    public function reset()
    {
        //reset all options
        if (isset($this->_options)) {
            $this->_options = array();
        }
        //reset response & status code
        $this->response = null;
        $this->responseCode = null;
        return $this;
    }
    /**
     * Return a single option
     *
     * @return mixed // false if option is not set.
     */
    public function getOption($key)
    {
        //get merged options depends on default and user options
        $mergesOptions = $this->getOptions();
        //return value or false if key is not set.
        return isset($mergesOptions[$key]) ? $mergesOptions[$key] : false;
    }
    /**
     * Return merged curl options and keep keys!
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->_options + $this->_defaultOptions;
    }
    /**
     * Performs HTTP request
     *
     * @param string  $method
     * @param string  $url
     *
     * @throws Exception if request failed
     * @throws HttpException
     *
     * @return mixed
     */
    private function _httpRequest($method, $url)
    {
        //Init
        $body = '';
        //set request type and writer function
        $this->setOption(CURLOPT_CUSTOMREQUEST, strtoupper($method));
        //check if method is head and set no body
        if ($method === 'HEAD') {
            $this->setOption(CURLOPT_NOBODY, true);
            $this->unsetOption(CURLOPT_WRITEFUNCTION);
        } else {
            $this->setOption(CURLOPT_WRITEFUNCTION, function ($curl, $data) use (&$body) {
                $body .= $data;
                return mb_strlen($data, '8bit');
            });
        }
        //setup error reporting and profiling
        Yii::trace('Start sending cURL-Request: '.$url.'\n', __METHOD__);
        Yii::beginProfile($method.' '.$url.'#'.md5(serialize($this->getOption(CURLOPT_POSTFIELDS))), __METHOD__);
        /**
         * proceed curl
         */
        $curl = curl_init($url);
        curl_setopt_array($curl, $this->getOptions());
        $body = curl_exec($curl);
        //check if curl was successful
        if ($body === false) {
            throw new Exception('curl request failed: ' . curl_error($curl) , curl_errno($curl));
        }
        //retrieve response code
        $this->responseCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $this->response = $body;
        //stop curl
        curl_close($curl);
        //end yii debug profile
        Yii::endProfile($method.' '.$url .'#'.md5(serialize($this->getOption(CURLOPT_POSTFIELDS))), __METHOD__);
        //check responseCode and return data/status
        if ($this->responseCode >= 200 && $this->responseCode < 300) { // all between 200 && 300 is successful
            if ($this->getOption(CURLOPT_CUSTOMREQUEST) === 'HEAD') {
                return true;
            } else {
                return $this->response;
            }
        } elseif ($this->responseCode >= 400 && $this->responseCode <= 510) { // client and server errors return false.
            return false;
        } else { //any other status code or custom codes
            return true;
        }
    }
}