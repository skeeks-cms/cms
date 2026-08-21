<?php

namespace skeeks\cms\widgets\admin;

use skeeks\cms\backend\assets\BackendAsset;
use skeeks\cms\models\CmsLead;
use yii\base\Widget;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/**
 * Semantic lead status shared by administration and customer cabinets.
 */
class CmsLeadStatusWidget extends Widget
{
    /** @var CmsLead|null */
    public $lead;

    /** @var array */
    public $options = [];

    public function run()
    {
        if (!$this->lead) {
            return '';
        }

        BackendAsset::register($this->view);

        $options = ArrayHelper::merge([
            'title' => ArrayHelper::getValue(
                CmsLead::statusDescriptions(),
                $this->lead->status,
                $this->lead->statusName
            ),
        ], (array)$this->options);
        Html::addCssClass($options, CmsLead::statusCssClass($this->lead->status));

        return Html::tag(
            'span',
            Html::tag('i', '', [
                'class' => CmsLead::statusIconClass($this->lead->status),
                'aria-hidden' => 'true',
            ]).Html::encode($this->lead->statusName),
            $options
        );
    }
}
