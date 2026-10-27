# «Состав» сделки — отложено

Блок «Состав» в карточке сделки («Сделка как услуга», шаг У3): что входит в
услугу строками — название, количество, цена, период, ресурс сделки,
описание. Бонус — строка с ценой 0 («в подарок»). Итог «По составу»
сравнивается с суммой сделки (сумма не пересчитывается).

Статус: интерфейс сделан и проверен на стенде 2026-10-27, но **убран из
выпуска У3** по решению владельца. Таблица `cms_deal_item` и модель
`CmsDealItem` уже выпущены в 6.4.9.69 и остаются в основной ветке; связь
`CmsDeal::getItems()` тоже. Здесь лежит только интерфейс.

## Что в этой ветке

- `src/controllers/AdminCmsDealItemController.php` — окно строки
  (`admin-cms-deal-item/create?CmsDealItem[cms_deal_id]=`, `update`, `delete`).
- `src/models/CmsDealItem.php` — `RESOURCE_NONE`, виртуальное `resourceKey`
  (`<type>:<id>`, одно значение для выпадающего списка), подписи и подсказки.
- этот файл — фрагменты карточки, стилей и теста, которые нужно вернуть
  в файлы У3 (они в основной ветке после выпуска У3).

## Правила, проверенные Codex

- Окно проверяет доступ к сделке (`CmsDeal::find()->forManager()`) сразу
  после загрузки модели и `cms_deal_id` из запроса — до чтения ресурсов
  сделки и построения формы — и ещё раз в `beforeValidate`. Иначе форма
  показывает ресурсы чужого клиента. Отказ — 403 «Нет доступа к этой сделке.»
- Список ресурсов — только ресурсы этой сделки (`dealAccess->resources()`).
  Ресурс, перенесённый в другую сделку, показывается как история
  («перенесён в другую сделку») **только из старых значений сохранённой
  строки** (`getOldAttribute()`), никогда из `resourceKey` запроса.
- `CmsDealItem::validateResource` отказывает чужому и неизвестному ресурсу
  («Ресурс не относится к этой сделке.»), сохранённую строку нельзя
  переключить на чужой ресурс.
- Пустой ключ «Без ресурса» — `RESOURCE_NONE = 'none'`: выпадающий список
  теряет подпись у пустого ключа.
- Поля `decimal(…, 4)` показываются как 1 и 1500, а не 1,0000.
- При переносе ресурса в другую сделку строки состава остаются в старой
  сделке как история. Вернуть в подсказку окна переноса
  (`AdminCmsDealController::actionMoveResource`) слова «и строки состава».

## Как вернуть

1. Ветку `deal-composition` перенести на ветку с выпущенным У3
   (`git rebase master` — конфликтов быть не должно: два файла).
2. В `src/views/admin-cms-deal/_client-access.php`: в начале
   `$items = $model->items;` и `use skeeks\cms\models\CmsDealType;`, в
   описании файла «, «Состав»»; блок ниже вставить между секцией
   «Доступ и уведомления» и «Ресурсы».
3. В `src/assets/admin/src/client-access.css` — стили ниже (класс
   `.sx-deal-items` остался в основной ветке: им пользуются «Ресурсы»).
4. В `tests/deal-client-access.php` — таблицу `cms_deal_item`, поставщик
   `TestSiteProvider` с `'resourceProviders' => ['site' => \TestSiteProvider::class]`,
   если его там уже нет, и проверки ниже.
5. В умении `skeeks-cms-package-development`,
   `references/deal-client-access.md`, раздел «Deal card»: вернуть абзац о
   «Составе» (текст в конце этого файла).
6. Проверить на стенде под сотрудником без доступа к сделке (403) и под
   администратором; прогнать `tests/deal-client-access.php`.

## Блок карточки сделки

```php
<section class="sx-deal-section sx-deal-composition">
    <div class="sx-deal-access__head">
        <h3 class="sx-deal-section-title">Состав</h3>
        <?php if ($canEdit) : ?>
            <?= $button('Добавить строку', ['/cms/admin-cms-deal-item/create', 'CmsDealItem' => ['cms_deal_id' => $model->id]]); ?>
        <?php endif; ?>
    </div>
    <?php if ($items) : ?>
        <?php
        $resourcesByKey = [];
        foreach ($resources as $resource) {
            $resourcesByKey[$resource->type.':'.$resource->id] = $resource;
        }
        $sum = 0.0;
        $periods = CmsDealType::optionsForPeriod();
        ?>
        <div class="sx-deal-items">
            <?php foreach ($items as $item) : ?>
                <?php
                $sum += (float)$item->amount;
                $period = $item->period ? ($periods[$item->period] ?? '') : ($model->is_periodic ? $model->periodAsText : '');
                $per = $period ? '/'.mb_strtolower($period) : '';
                $meta = [];
                if ($item->resource_type && $item->resource_id) {
                    $resource = $resourcesByKey[$item->resourceKey] ?? null;
                    if ($resource) {
                        $meta[] = Html::encode($resource->title);
                    } else {
                        $old = $dealAccess->resource((string)$item->resource_type, (int)$item->resource_id);
                        $meta[] = Html::encode($old ? $old->title : $item->resourceKey).' (перенесён в другую сделку)';
                    }
                }
                if (trim((string)$item->description) !== '') {
                    $meta[] = Html::encode($item->description);
                }
                $editUrl = $canEdit ? $windowUrl(['/cms/admin-cms-deal-item/update', 'pk' => $item->id]) : '';
                $quantity = (float)$item->quantity;
                ?>
                <div class="sx-deal-item"<?= $editUrl ? ' data-sx-window="'.Html::encode($editUrl).'" style="cursor: pointer;"' : ''; ?>>
                    <div class="sx-deal-item__body">
                        <div class="sx-deal-item__name">
                            <?= Html::encode($item->name); ?>
                            <?php if ($item->isBonus) : ?> <span class="sx-deal-badge sx-deal-badge--success">в подарок</span><?php endif; ?>
                        </div>
                        <?php if ($meta) : ?><div class="sx-deal-item__meta"><?= implode(' · ', $meta); ?></div><?php endif; ?>
                    </div>
                    <div class="sx-deal-item__price">
                        <?php if (!$item->isBonus && $quantity != 1.0) : ?>
                            <?= Html::encode(\Yii::$app->formatter->asDecimal($quantity).' × '.new \skeeks\cms\money\Money($item->price, $currency).$per); ?>
                        <?php endif; ?>
                    </div>
                    <div class="sx-deal-item__amount"><?= $item->isBonus ? '0' : Html::encode((string)new \skeeks\cms\money\Money($item->amount, $currency).$per); ?></div>
                    <div class="sx-deal-item__menu"><?= $canEdit ? CrmContactBlockWidget::menu('/cms/admin-cms-deal-item', (int)$item->id, 'Изменить строку') : ''; ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="sx-deal-items__total">
            <span>По составу: <b><?= Html::encode((string)new \skeeks\cms\money\Money((string)$sum, $currency)); ?></b></span>
            <?php if (abs($sum - (float)$model->amount) > 0.009) : ?>
                <span class="sx-deal-items__diff">Не совпадает с суммой сделки <?= Html::encode($model->moneyAsText); ?></span>
            <?php endif; ?>
        </div>
    <?php else : ?>
        <div class="sx-deal-access__muted">Состав не заполнен: что входит в услугу, с ценой и периодом по строкам. Бонус — строка с нулевой ценой.</div>
    <?php endif; ?>
</section>
```

## Стили

```css
/* Composition of the deal. */
.sx-deal-items {
    display: grid;
    gap: 2px;
}

.sx-deal-item {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto auto 28px;
    align-items: center;
    gap: 12px;
    margin: 0 -8px;
    padding: 8px;
    border-radius: var(--sx-radius-control);
}

.sx-deal-item:hover {
    background: var(--sx-color-surface-hover, var(--sx-color-surface-muted));
}

.sx-deal-item__name {
    font-weight: 500;
    overflow-wrap: anywhere;
}

.sx-deal-item__meta {
    margin-top: 2px;
    color: var(--sx-color-text-muted);
    font-size: 12px;
    overflow-wrap: anywhere;
}

.sx-deal-item__price {
    color: var(--sx-color-text-muted);
    font-size: 13px;
    text-align: right;
    white-space: nowrap;
}

.sx-deal-item__amount {
    font-weight: 600;
    text-align: right;
    white-space: nowrap;
}

.sx-deal-item .sx-icon-action {
    display: inline-flex;
    color: var(--sx-color-text-muted);
    cursor: pointer;
}

.sx-deal-items__total {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    gap: 4px 12px;
    margin-top: 6px;
    padding-top: 8px;
    border-top: 1px solid var(--sx-color-border);
    font-size: 13px;
}

.sx-deal-items__total b {
    font-weight: 600;
}

.sx-deal-items__diff {
    color: var(--sx-color-warning-on-surface, var(--sx-color-warning));
}

@media (hover: hover) {
    .sx-deal-item .sx-icon-action,
    .sx-deal-grant .sx-icon-action {
        opacity: 0;
        transition: opacity 0.15s ease;
    }

    .sx-deal-item:hover .sx-icon-action,
    .sx-deal-item:focus-within .sx-icon-action,
    .sx-deal-grant:hover .sx-icon-action,
    .sx-deal-grant:focus-within .sx-icon-action {
        opacity: 1;
    }
}

@media (max-width: 575px) {
    .sx-deal-item {
        grid-template-columns: minmax(0, 1fr) 28px;
        gap: 2px 8px;
    }

    .sx-deal-item__body {
        grid-column: 1;
    }

    .sx-deal-item__price,
    .sx-deal-item__amount {
        grid-column: 1;
        text-align: left;
    }

    .sx-deal-item__menu {
        grid-row: 1;
        grid-column: 2;
    }
}

```

## Тест: поставщик ресурсов

```php

    // Feed entries are collected instead of written into cms_log.
    class TestDealAccess extends DealAccess
    {
        public $log = [];
        public function writeDealLog(\skeeks\cms\models\CmsDeal $deal, string $comment): void { $this->log[] = $comment; }
    }

    // Sites: 1 in deal 11, 2 in the foreign deal 20.
    class TestSiteProvider implements \skeeks\cms\deal\DealResourceProviderInterface
    {
        public static $deals = [1 => 11, 2 => 20];
        public function label(): string { return 'Сайт'; }
        public function findByDeal(\skeeks\cms\models\CmsDeal $deal): array
        {
            return array_values(array_filter(array_map([$this, 'findOne'], array_keys(self::$deals)), function ($r) use ($deal) { return (int)$r->dealId === (int)$deal->id; }));
        }
        public function findOne(int $id): ?\skeeks\cms\deal\DealResource
        {
            return isset(self::$deals[$id]) ? new \skeeks\cms\deal\DealResource(['type' => 'site', 'id' => $id, 'title' => 'site'.$id, 'dealId' => self::$deals[$id]]) : null;
        }
        public function move(int $id, \skeeks\cms\models\CmsDeal $toDeal): void { self::$deals[$id] = (int)$toDeal->id; }
    }

    $app =new \yii\console\Application(['id' => 'deal-access-test', 'basePath' => __DIR__, 'components' => [
        'db'         => ['class' => \yii\db\Connection::class, 'dsn' => 'sqlite::memory:'],
```

Таблица для SQLite:

```php
'cms_deal_item' => 'id INTEGER PRIMARY KEY, cms_deal_id INTEGER, name TEXT, quantity REAL, price REAL, amount REAL, period TEXT, resource_type TEXT, resource_id INTEGER, description TEXT, priority INTEGER',
```

## Тест: проверки строк состава

```php
    // Composition lines: a line links only a resource of its own deal; a stored line keeps a moved one as history.
    $item = function (array $attributes, bool $stored = false) {
        $model = new \skeeks\cms\models\CmsDealItem();
        if ($stored) {
            \skeeks\cms\models\CmsDealItem::populateRecord($model, $attributes + ['id' => 1]);
        } else {
            foreach ($attributes as $name => $value) {
                $model->$name = $value;
            }
        }
        return $model;
    };
    $resourceErrors = function ($model) { $model->validate(['resource_id']); return $model->getErrors('resource_id'); };
    $check('own resource', $resourceErrors($item(['cms_deal_id' => 11, 'name' => 'x', 'resourceKey' => 'site:1'])), []);
    $check('foreign resource refused', $resourceErrors($item(['cms_deal_id' => 11, 'name' => 'x', 'resourceKey' => 'site:2'])), ['Ресурс не относится к этой сделке.']);
    $check('unknown resource refused', $resourceErrors($item(['cms_deal_id' => 11, 'name' => 'x', 'resourceKey' => 'site:99'])), ['Ресурс не относится к этой сделке.']);
    TestSiteProvider::$deals[1] = 20;
    $check('moved resource stays history', $resourceErrors($item(['cms_deal_id' => 11, 'name' => 'x', 'resource_type' => 'site', 'resource_id' => 1], true)), []);
    $stored = $item(['cms_deal_id' => 11, 'name' => 'x', 'resource_type' => 'site', 'resource_id' => 1], true);
    $stored->resourceKey = 'site:2';
    $check('stored line cannot switch to a foreign resource', $resourceErrors($stored), ['Ресурс не относится к этой сделке.']);
    TestSiteProvider::$deals[1] = 11;
```

## Абзац для умения

> **«Состав»**: lines of `cms_deal_item` through `admin-cms-deal-item`
> (resource as `resourceKey` `<type>:<id>`, `CmsDealItem::RESOURCE_NONE` for
> none — a select drops the label of an empty key; price 0 is a gift), the
> total against the deal amount (never recounted). The window checks
> `forManager()` for `?CmsDealItem[cms_deal_id]=` right after loading it,
> before reading resources, and again before saving. A moved resource is
> offered as history only from the stored line's old attributes
> (`getOldAttribute()`), never from a key in the request.
