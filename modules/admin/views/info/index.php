<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 30.01.2015
 * @since 1.0.0
 */
/* @var $this yii\web\View */
use Yii;
?>

<?php
echo $this->render('table', [
    'caption' => 'Конфигурация приложения',
    'values' => [
        'Yii Version' => $application['yii'],
        'Application Name' => $application['name'],
        'Environment' => $application['env'],
        'Debug Mode' => $application['debug'] ? 'Yes' : 'No',
    ],
]);

if (!empty($extensions)) {
    echo $this->render('table', [
        'caption' => 'Установленные расширения и модули Yii',
        'values' => $extensions,
    ]);
}

echo $this->render('table', [
    'caption' => 'PHP конфигурация',
    'values' => [
        'PHP Version' => $php['version'],
        'Xdebug' => $php['xdebug'] ? 'Enabled' : 'Disabled',
        'APC' => $php['apc'] ? 'Enabled' : 'Disabled',
        'Memcache' => $php['memcache'] ? 'Enabled' : 'Disabled',
    ],
]);