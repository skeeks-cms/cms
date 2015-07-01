<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 07.03.2015
 */
namespace skeeks\cms\console\controllers;

use skeeks\cms\base\console\Controller;
use skeeks\cms\models\User;
use skeeks\cms\modules\admin\components\UrlRule;
use skeeks\cms\modules\admin\controllers\AdminController;
use skeeks\cms\rbac\AuthorRule;
use skeeks\sx\Dir;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Console;

/**
 * Проверки и тесты
 *
 * @package skeeks\cms\controllers
 */
class CheckController extends Controller
{
    const EMPTY_CONSOLE_TEXT = 'empty console text';

    /**
     * Чистая консоль
     * Если будут какие нибудь php warnings и notice, то на экран будут выведены лишние символы. Кроме текста.
     */
    public function actionEmptyConsole()
    {
        $this->stdout(self::EMPTY_CONSOLE_TEXT);
    }
}