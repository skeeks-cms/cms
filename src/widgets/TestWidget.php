<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link https://skeeks.com/
 * @copyright (c) 2010 SkeekS
 * @date 11.03.2018
 */

namespace skeeks\cms\widgets;

use skeeks\yii2\config\ConfigBehavior;
use skeeks\yii2\config\ConfigTrait;
use yii\base\Widget;
use yii\helpers\ArrayHelper;

/**
 * Class GridView
 * @package skeeks\cms
 */
class TestWidget extends Widget
{
    use ConfigTrait;

    public $test = '22';

    public $config = [];

    public function init()
    {
        parent::init();
    }

    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [

            ConfigBehavior::class => ArrayHelper::merge([
                'class' => ConfigBehavior::class,
                'configModel' => [
                    'fields' => [
                        'test'
                    ],
                    'defineAttributes' => [
                        'test',
                    ],
                    'attributeLabels' => [
                        'test' => '111',
                    ],
                    'attributeHints' => [
                        'test' => '111',
                    ],
                    'rules' => [
                        ['test', 'string']
                    ]
                ]
            ], (array) $this->config),

        ]);
    }

    public function run()
    {
        return $this->test;
    }
}