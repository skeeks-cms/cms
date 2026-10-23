<?php

namespace skeeks\cms\widgets\admin;

use yii\base\Event;
use yii\base\Widget;

/**
 * A place in a CRM card (lead, company, deal) where other packages put their
 * own blocks: "КП", later "Счета", "Мессенджеры". Core renders only what the
 * handlers of the class-level {@see EVENT_BLOCKS} return and knows nothing of
 * the packages.
 *
 * ```php
 * Event::on(CrmCardBlocksWidget::class, CrmCardBlocksWidget::EVENT_BLOCKS, function (CrmCardBlocksEvent $e) {
 *     if ($e->owner instanceof CmsLead && $e->position === CrmCardBlocksWidget::POSITION_ASIDE) {
 *         $e->blocks['proposals'] = BackendSurfaceWidget::widget([...]);
 *         $e->priority['proposals'] = 50;
 *     }
 * });
 * ```
 *
 * In a card view: `<?= CrmCardBlocksWidget::widget(['model' => $model]) ?>`.
 */
class CrmCardBlocksWidget extends Widget
{
    /** Class-level event: packages add blocks to a card. */
    public const EVENT_BLOCKS = 'blocks';

    /**
     * Class-level event: packages add actions of the next step of a card
     * ("Отправить презентацию", "Сделать КП") beside the core "Поставить
     * задачу", see {@see actions()}.
     */
    public const EVENT_ACTIONS = 'actions';

    /** The narrow column of the card (contacts, requisites). */
    public const POSITION_ASIDE = 'aside';
    /** The wide column of the card (tasks, feed). */
    public const POSITION_MAIN = 'main';

    /** @var \yii\db\ActiveRecord the card record */
    public $model;

    public string $position = self::POSITION_ASIDE;

    /**
     * Blocks of the card in their order.
     * @return string[] id => trusted HTML
     */
    public static function blocks($model, string $position = self::POSITION_ASIDE): array
    {
        if (!$model) {
            return [];
        }
        $event = new CrmCardBlocksEvent(['owner' => $model, 'position' => $position]);
        Event::trigger(self::class, self::EVENT_BLOCKS, $event);

        $blocks = array_filter($event->blocks, static fn($html) => is_string($html) && trim($html) !== '');
        $order = array_flip(array_keys($blocks));
        uksort($blocks, static function ($a, $b) use ($event, $order) {
            return [(int)($event->priority[$a] ?? 100), $order[$a]] <=> [(int)($event->priority[$b] ?? 100), $order[$b]];
        });

        return $blocks;
    }

    /**
     * Actions of packages for the next step of a card in their order.
     * @return array[] id => label, url, icon
     */
    public static function actions($model): array
    {
        if (!$model) {
            return [];
        }
        $event = new CrmCardBlocksEvent(['owner' => $model]);
        Event::trigger(self::class, self::EVENT_ACTIONS, $event);

        $actions = array_filter($event->actions, static fn($a) => is_array($a) && trim((string)($a['label'] ?? '')) !== '' && trim((string)($a['url'] ?? '')) !== '');
        $order = array_flip(array_keys($actions));
        uksort($actions, static function ($a, $b) use ($event, $order) {
            return [(int)($event->priority[$a] ?? 100), $order[$a]] <=> [(int)($event->priority[$b] ?? 100), $order[$b]];
        });

        return $actions;
    }

    public function run()
    {
        return implode('', self::blocks($this->model, $this->position));
    }
}
