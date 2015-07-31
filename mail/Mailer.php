<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 20.03.2015
 */
namespace skeeks\cms\mail;
use yii\base\Theme;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/**
 * Class Mailer
 * @package skeeks\cms\mail
 */
class Mailer extends \yii\swiftmailer\Mailer
{
    /**
     * @var string message default class name.
     */
    public $messageClass = 'skeeks\cms\mail\Message';

    /**
     * @var array
     */
    public $tagStyles =
    [
        'h1' => 'color:#1D5800;font-size:32px;font-weight:normal;margin-bottom:13px;margin-top:20px;',
        'h2' => 'color:#1D5800;font-size:28px;font-weight:normal;margin-bottom:10px;margin-top:17px;',
        'h3' => 'color:#1D5800;font-size:24px;font-weight:normal;margin-bottom:7px;margin-top:14px;',
        'h4' => 'color:#1D5800;font-size:20px;font-weight:normal;margin-bottom:6px;margin-top:11px;',
        'h5' => 'color:#1D5800;font-size:16px;font-weight:normal;margin-bottom:6px;margin-top:8px;',
        'p' => 'font:Arial,Helvetica,sans-serif;',
    ];

    public function init()
    {
        parent::init();

        foreach (\Yii::$app->cms->emailTemplates as $code => $templateData)
        {
            if ($code == \Yii::$app->cms->emailTemplate)
            {
                if ($pathMap = ArrayHelper::getValue($templateData, 'pathMap'))
                {
                    if (is_array($pathMap))
                    {
                        $this->view->theme = new Theme([
                            'pathMap' => $pathMap
                        ]);
                    }
                }
            }
        }
    }

    /**
     * @param null $view
     * @param array $params
     * @return Message
     */
    public function compose($view = null, array $params = [])
    {
        $message = parent::compose($view, $params);

        if ($message instanceof Message)
        {
            $message->view = $view;
        }

        return $message;
    }
}