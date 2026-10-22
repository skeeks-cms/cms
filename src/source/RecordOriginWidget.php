<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\source;

use yii\base\Widget;
use yii\db\ActiveRecord;
use yii\helpers\Html;

/**
 * "Создано из письма «…»" at the top of a CRM card: asks every registered
 * record source that implements RecordOriginInterface. Renders nothing when
 * the record has no known origin.
 */
class RecordOriginWidget extends Widget
{
    /** @var ActiveRecord */
    public $model;

    /** @var array HTML attributes of the block; class `sx-record-origin--spaced` adds space below it outside a surface stack */
    public $options = [];

    /** @return array[] label, html of every source */
    public static function origins(ActiveRecord $record): array
    {
        if ($record->getIsNewRecord() || !\Yii::$app->has('skeeks')) {
            return [];
        }
        $skeeks = \Yii::$app->skeeks;
        $result = [];
        foreach (array_keys((array)$skeeks->recordSources) as $type) {
            try {
                $source = $skeeks->getRecordSource($type);
            } catch (\yii\base\InvalidConfigException $e) {
                continue;
            }
            if ($source instanceof RecordOriginInterface) {
                foreach ($source->origins($record) as $origin) {
                    if (!empty($origin['html'])) {
                        $result[] = ['label' => (string)($origin['label'] ?? 'Создано из'), 'html' => (string)$origin['html']];
                    }
                }
            }
        }

        return $result;
    }

    public function run()
    {
        if (!$this->model instanceof ActiveRecord) {
            return '';
        }
        $items = '';
        foreach (self::origins($this->model) as $origin) {
            $items .= Html::tag('div',
                Html::tag('span', Html::encode($origin['label']), ['class' => 'sx-record-origin__label']).' '.$origin['html'],
                ['class' => 'sx-record-origin__item']);
        }
        if ($items === '') {
            return '';
        }
        $this->view->registerCss(<<<CSS
.sx-record-origin {
    display: flex;
    flex-direction: column;
    gap: 4px;
    padding: 10px 14px;
    border: 1px solid var(--sx-color-accent-border, #f8b4c8);
    border-radius: var(--sx-radius, 8px);
    background: var(--sx-color-accent-soft, #fdedf2);
    color: var(--sx-color-text, #262626);
    font-size: 14px;
    line-height: 1.5;
    overflow-wrap: anywhere;
}
.sx-record-origin__label {
    font-weight: 600;
}
.sx-record-origin--spaced {
    margin-bottom: 16px;
}
.sx-record-origin a {
    font-weight: 600;
}
CSS
        );
        $options = $this->options;
        Html::addCssClass($options, 'sx-record-origin');

        return Html::tag('div', $items, $options);
    }
}
