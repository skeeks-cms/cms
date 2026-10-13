<?php

namespace skeeks\cms\widgets\admin;

use skeeks\cms\backend\assets\BackendAsset;
use skeeks\cms\models\CmsLead;
use skeeks\cms\models\CmsLeadStage;
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

    /**
     * Also show the internal work stage of a lead in work. Stages are an
     * employee tool: keep it off for partner and customer cabinets.
     *
     * @var bool
     */
    public $showStage = false;

    /** Stage under the status (grid cells) or beside it (headers). */
    public $stageInline = false;

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

        $status = Html::tag(
            'span',
            Html::tag('i', '', [
                'class' => CmsLead::statusIconClass($this->lead->status),
                'aria-hidden' => 'true',
            ]).Html::encode($this->lead->statusName),
            $options
        );

        $stage = $this->showStage && $this->lead->status === CmsLead::STATUS_IN_WORK ? $this->lead->stage : null;
        if (!$stage) {
            return $status;
        }

        return Html::tag('span', $status.self::renderStage($stage), [
            'class' => 'sx-collection-cell '.($this->stageInline ? 'sx-collection-cell--inline' : 'sx-collection-cell--stack'),
        ]);
    }

    /** Neutral stage chip; the optional stage color marks its dot. */
    public static function renderStage(CmsLeadStage $stage): string
    {
        $dot = Html::tag('i', '', array_filter([
            'class' => 'fas fa-circle',
            'aria-hidden' => 'true',
            'style' => $stage->color ? 'color: '.$stage->color : null,
        ]));

        return Html::tag('span', $dot.Html::encode($stage->name), [
            'class' => 'sx-status',
            'title' => 'Этап работы с лидом',
        ]);
    }
}
