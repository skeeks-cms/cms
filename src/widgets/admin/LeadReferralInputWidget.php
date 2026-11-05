<?php

namespace skeeks\cms\widgets\admin;

use skeeks\cms\assets\admin\LeadReferralAsset;
use skeeks\cms\crm\LeadReferrals;
use yii\helpers\Html;
use yii\widgets\InputWidget;

/**
 * «Откуда узнали» in one click: every active item of the site is a chip (a
 * radio button); a click on the chosen chip clears it. The current inactive
 * item of a deal stays visible. A hidden empty radio keeps the field posted
 * when nothing is chosen.
 */
class LeadReferralInputWidget extends InputWidget
{
    /** @var array|null id => name; null — the active items of the site of the model */
    public $items;

    public function run()
    {
        LeadReferralAsset::register($this->view);
        $current = $this->hasModel() ? $this->model->{$this->attribute} : $this->value;
        $current = $current === null || $current === '' ? '' : (string)(int)$current;
        $siteId = $this->hasModel() && $this->model->hasAttribute('cms_site_id') && $this->model->cms_site_id ? (int)$this->model->cms_site_id : null;
        $items = $this->items ?? LeadReferrals::items($siteId, $current !== '' ? (int)$current : null);
        $name = $this->hasModel() ? Html::getInputName($this->model, $this->attribute) : $this->name;
        $id = $this->options['id'] ?? ($this->hasModel() ? Html::getInputId($this->model, $this->attribute) : $this->getId());

        if (!$items) {
            return Html::hiddenInput($name, $current, ['id' => $id])
                .Html::tag('div', 'Список «Откуда узнали» пуст: его заполняют в настройках CRM.', ['class' => 'sx-lead-referral__empty']);
        }

        $html = Html::radio($name, $current === '', [
            'value' => '',
            'class' => 'sx-lead-referral__input',
            'id' => $id,
            'hidden' => true,
        ]);
        foreach ($items as $value => $label) {
            $checked = $current === (string)$value;
            $html .= Html::tag('label',
                Html::radio($name, $checked, ['value' => (string)$value, 'class' => 'sx-lead-referral__input'])
                .Html::encode((string)$label),
                [
                    'class' => 'sx-chip sx-chip--compact'.($checked ? ' is-active' : ''),
                    'aria-pressed' => $checked ? 'true' : 'false',
                ]
            );
        }

        return Html::tag('div', $html, ['class' => 'sx-lead-referral', 'role' => 'radiogroup']);
    }
}
