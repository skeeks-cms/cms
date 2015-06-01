<?php
/**
 * ErrorAction
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 04.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\actions;


use skeeks\cms\App;
use skeeks\cms\exceptions\NotConnectedToDbException;
use skeeks\cms\rbac\CmsManager;
use Yii;
use yii\base\Action;
use yii\base\Exception;
use yii\base\UserException;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * Class ErrorAction
 * @package skeeks\cms\actions
 */
class ErrorAction extends \yii\web\ErrorAction
{
    /**
     * Runs the action
     *
     * @return string result content
     */
    public function run()
    {
        if (($exception = Yii::$app->getErrorHandler()->exception) === null)
        {
            return '';
        }

        if ($exception instanceof \HttpException)
        {
            $code = $exception->statusCode;
        } else {
            $code = $exception->getCode();
        }

        if ($exception instanceof NotConnectedToDbException)
        {
            return \Yii::$app->response->redirect(Url::toRoute('cms/install'));
        }

        if ($exception instanceof Exception)
        {
            $name = $exception->getName();
        } else
        {
            $name = $this->defaultName ?: Yii::t('yii', 'Error');
        }

        if ($code)
        {
            $name .= " (#$code)";
        }

        if ($exception instanceof UserException)
        {
            $message = $exception->getMessage();
        } else
        {
            $message = $this->defaultMessage ?: Yii::t('yii', 'An internal server error occurred.');
        }


        if (Yii::$app->getRequest()->getIsAjax())
        {
            return "$name: $message";
        } else
        {
            if (\Yii::$app->cms->moduleAdmin()->requestIsAdmin())
            {
                if (\Yii::$app->user->can(CmsManager::PERMISSION_ADMIN_ACCESS))
                {
                    $this->controller->layout = \Yii::$app->cms->moduleAdmin()->layout;
                    return $this->controller->output(nl2br(Html::encode($message)));
                } else
                {

                    $this->controller->layout = '@skeeks/cms/modules/admin/views/layouts/unauthorized.php';
                    return $this->controller->render('@skeeks/cms/modules/admin/views/error/unauthorized-403', [
                        'message' => nl2br(Html::encode($message))
                    ]);
                }

            } else
            {
                return $this->controller->render($this->view ?: $this->id, [
                    'name' => $name,
                    'message' => $message,
                    'exception' => $exception,
                ]);
            }

        }
    }
}