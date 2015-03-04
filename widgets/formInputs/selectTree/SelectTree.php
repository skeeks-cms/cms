<?php
/**
 * SelectTree
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 13.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\widgets\formInputs\selectTree;

use skeeks\cms\Exception;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\behaviors\HasFiles;
use skeeks\cms\models\Tree;
use skeeks\cms\modules\admin\Module;
use skeeks\cms\modules\admin\widgets\ActiveForm;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\widgets\InputWidget;
use Yii;

/**
 * Class Widget
 * @package skeeks\cms\widgets\formInputs\selectTree
 */
class SelectTree extends InputWidget
{
    /**
     * @var array the options for the Bootstrap File Input plugin. Default options have exporting enabled.
     * Please refer to the Bootstrap File Input plugin Web page for possible options.
     * @see http://plugins.krajee.com/file-input#options
     */
    public $clientOptions = [];

    public $mode = 'multi';


    /**
     * Берем поведения модели
     *
     */
    private function _initAndValidate()
    {
        if (!$this->hasModel())
        {
            throw new Exception("Этот файл рассчитан только для форм с моделью");
        }
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        try
        {
            $this->_initAndValidate();

            $valueArray     = Html::getAttributeValue($this->model, $this->attribute);
            $trees          = Tree::find()->where(['id' => $valueArray])->all();

            $select = Html::activeListBox($this->model, $this->attribute, [], [
                'multiple' => true,
                'class' => 'sx-controll-element',
                'style' => 'display: none;'
            ]);

            $src = UrlHelper::construct('/cms/admin-tree')
                            ->set('mode', $this->mode)
                            ->set('s', $valueArray)
                            ->setSystemParam(Module::SYSTEM_QUERY_EMPTY_LAYOUT, 'true')
                            ->setSystemParam(Module::SYSTEM_QUERY_NO_ACTIONS_MODEL, 'true')
                            ->enableAdmin()->toString();

            $id = "sx-id-" . Yii::$app->security->generateRandomString(6);

            $selected = [];
            foreach ($trees as $tree)
            {
                $selected[] = $tree->id;
            }

            return $this->render('widget', [
                'widget'            => $this,
                'id'                => $id,
                'select'            => $select,
                'src'               => $src,
                'clientOptions'     => Json::encode(
                    [
                        'src'       => $src,
                        'name'      => $id,
                        'selected'  => $selected
                    ]
                )

            ]);

            //$this->registerClientScript();

        } catch (Exception $e)
        {
            echo $e->getMessage();
        }

    }

    /**
     * Registers Bootstrap File Input plugin
     */
    public function registerClientScript()
    {
        $view = $this->getView();
        Asset::register($view);
    }
}
