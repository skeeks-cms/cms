<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\widgets\admin;

use skeeks\cms\deal\DealAccess;
use skeeks\cms\models\CmsDealRole;
use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\Json;

/**
 * Levels of every access area ("Сделка как услуга") as one select per area.
 * Areas come from DealAccess, so areas of packages appear by themselves.
 *
 * With $roleInputId the selects are filled from the chosen role; with
 * $fullInputId they are disabled while "Полный доступ" is on (or, with
 * $fullHides = false, shown only while that checkbox is on: "Настроить").
 * $maxLevels limits the offered levels (a deal narrows what its type allows).
 */
class DealAccessLevelsWidget extends Widget
{
    /** @var string input name prefix, e.g. CmsClientAccess[levels] */
    public $name;

    /** @var array area => level */
    public $levels = [];

    /** @var string|null id of the role select */
    public $roleInputId;

    /** @var string|null id of the "Полный доступ" checkbox */
    public $fullInputId;

    /** @var array|null area => level: what is open in the deal, shown as a hint */
    public $openLevels;

    /** @var bool the levels hide while the checkbox $fullInputId is on; false — they show only while it is on */
    public $fullHides = true;

    /** @var array|null area => level: higher levels are not offered (a deal narrows what its type allows) */
    public $maxLevels;

    /** @var string|null label of level 0 */
    public $noneLabel;

    public function run()
    {
        /** @var DealAccess $dealAccess */
        $dealAccess = \Yii::$app->get('dealAccess');
        $id = $this->getId();

        $rows = '';
        foreach ($dealAccess->getAreaList() as $area => $config) {
            $levels = $config['levels'];
            if ($this->maxLevels !== null) {
                $max = (int)($this->maxLevels[$area] ?? 0);
                $levels = array_filter($levels, static function ($level) use ($max) {
                    return $level <= $max;
                }, ARRAY_FILTER_USE_KEY);
            }
            $items = [0 => $this->noneLabel ?: 'Нет'] + $levels;
            $hint = $config['hint'] ? Html::tag('div', Html::encode($config['hint']), ['class' => 'sx-deal-levels__hint']) : '';
            if ($this->maxLevels !== null && !$levels) {
                $hint .= Html::tag('div', 'Закрыто в типе услуги', ['class' => 'sx-deal-levels__hint']);
            }
            if ($this->openLevels !== null && (int)($this->openLevels[$area] ?? 0) < max(array_keys($config['levels']))) {
                $open = (int)($this->openLevels[$area] ?? 0);
                $hint .= Html::tag('div', $open ? 'В услуге открыто: '.mb_strtolower($dealAccess->levelName($area, $open)) : 'В услуге закрыто', ['class' => 'sx-deal-levels__hint sx-deal-levels__hint--warning']);
            }
            $rows .= Html::tag('div',
                Html::tag('div', Html::encode($config['name']).$hint, ['class' => 'sx-deal-levels__name'])
                .Html::tag('div', Html::dropDownList($this->name.'['.$area.']', (int)($this->levels[$area] ?? 0), $items, [
                    'class'          => 'form-control sx-deal-levels__select',
                    'data-sx-area'   => $area,
                ]), ['class' => 'sx-deal-levels__value']),
                ['class' => 'sx-deal-levels__row']
            );
        }

        $roles = [];
        if ($this->roleInputId) {
            foreach (CmsDealRole::find()->with('areaRows')->all() as $role) {
                $roles[(int)$role->id] = ['full' => (int)$role->is_full, 'levels' => $role->getLevels()];
            }
        }

        $this->view->registerCss(<<<CSS
.sx-deal-levels { display: grid; gap: 8px; margin: 4px 0 16px; }
.sx-deal-levels__row { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 220px); gap: 12px; align-items: center; }
.sx-deal-levels__hint { font-size: 12px; color: var(--sx-text-muted, #6b7280); }
.sx-deal-levels__hint--warning { color: var(--sx-warning-text, #a16207); }
.sx-deal-levels--disabled { display: none; }
@media (max-width: 575px) { .sx-deal-levels__row { grid-template-columns: 1fr; gap: 4px; } }
CSS
        );

        $config = Json::encode([
            'id'     => $id,
            'role'   => $this->roleInputId,
            'full'   => $this->fullInputId,
            'hides'  => (bool)$this->fullHides,
            'roles'  => (object)$roles,
        ]);
        $this->view->registerJs(<<<JS
(function (c) {
    var box = document.getElementById(c.id);
    if (!box) { return; }
    var full = c.full ? document.getElementById(c.full) : null;
    var role = c.role ? document.getElementById(c.role) : null;
    function syncFull() {
        box.classList.toggle('sx-deal-levels--disabled', !!(full && (c.hides ? full.checked : !full.checked)));
    }
    if (full) { full.addEventListener('change', syncFull); syncFull(); }
    if (role) {
        jQuery(role).on('change', function () {
            var r = c.roles[role.value];
            if (!r) { return; }
            box.querySelectorAll('[data-sx-area]').forEach(function (select) {
                select.value = String(r.levels[select.getAttribute('data-sx-area')] || 0);
            });
            if (full) { full.checked = !!r.full; jQuery(full).trigger('change'); syncFull(); }
        });
    }
})({$config});
JS
        );

        //Inside an HtmlBlock of a backend form: the same column and spacing as ordinary fields.
        return Html::tag('div', Html::tag('div', $rows, ['id' => $id, 'class' => 'sx-deal-levels']), ['class' => 'col-12 form-group']);
    }
}
