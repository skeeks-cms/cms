<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 18.02.2016
 */
namespace skeeks\cms\modules\admin\base;

use skeeks\cms\traits\HasComponentDescriptorTrait;
use skeeks\cms\traits\WidgetTrait;
use yii\base\Model;
use yii\base\ViewContextInterface;
use yii\widgets\ActiveForm;

/**
 * Class AdminDashboardWidgetRenderable
 * @package skeeks\cms\modules\admin\base
 */
class AdminDashboardWidgetRenderable extends AdminDashboardWidget
{
    /**
     * @var null Файл в котором будет реднериться виджет
     */
    public $viewFile    = "default";

    public function run()
    {
        if ($this->viewFile)
        {
            try
            {
                return $this->render($this->viewFile, [
                    'widget' => $this
                ]);
            } catch (\Exception $e)
            {
                return $e->getMessage();
            }

        } else
        {
            return \Yii::t('app',"Template not found");
        }
    }
}