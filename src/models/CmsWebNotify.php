<?php

namespace skeeks\cms\models;

use skeeks\cms\base\ActiveRecord;
use skeeks\cms\models\queries\CmsWebNotifyQuery;
use skeeks\cms\backend\widgets\BackendEntityLink;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
/**
 * @property int         $id
 *
 * @property int|null    $created_at
 *
 * @property string      $name
 * @property string|null $comment
 * @property string|null $url
 *
 * @property string|null $model_code
 * @property int|null    $model_id
 *
 * @property int         $cms_user_id
 *
 * @property bool        $is_read
 * @property bool        $is_seen     the recipient saw it in the bell (the badge counts the rest)
 * @property int|null    $updated_at  the last event of the row (order of the bell)
 *
 * @property string|null $kind        kind of event (notify\Notifications), null — created directly
 * @property string|null $category
 * @property int         $importance  0 normal, 1 important, 2 urgent
 * @property string|null $group_key   related events shown as one entry
 * @property string|null $event_key   one event is stored once per recipient
 * @property int         $count       events collapsed into this row
 *
 * @property CmsUser     $cmsUser
 */
class CmsWebNotify extends ActiveRecord
{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_web_notify}}';
    }


    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = [

            [
                [
                    'created_at',
                    'cms_user_id',
                ],
                'integer',
            ],

            [['name', 'comment'], "string"],
            [['url'], 'string', 'max' => 1000],

            [['model_code'], "string"],
            [['model_id'], "integer"],

            [['is_read'], "integer"],


            [['comment', 'url', 'model_code', 'model_id'], 'default', 'value' => null],
            [['is_read'], 'default', 'value' => 0],

            [
                ['name'],
                "required",
            ],
        ];
        // Columns of step У4a: code may run before its migration, an older schema validates as before.
        if (static::getTableSchema()->getColumn('kind')) {
            $rules[] = [['is_seen', 'importance', 'count', 'updated_at'], "integer"];
            $rules[] = [['kind', 'category'], 'string', 'max' => 64];
            $rules[] = [['group_key', 'event_key'], 'string', 'max' => 128];
            $rules[] = [['kind', 'group_key', 'event_key'], 'default', 'value' => null];
            $rules[] = [['is_seen', 'importance'], 'default', 'value' => 0];
            $rules[] = [['count'], 'default', 'value' => 1];
        }

        return ArrayHelper::merge(parent::rules(), $rules);
    }


    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
        ]);
    }

    /**
     * The order of the bell is the last event of a row: reading or marking a
     * row must not lift it, so updated_at is written only by Notifications
     * (and on insert), not by the timestamp behavior.
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        unset($behaviors[\yii\behaviors\TimestampBehavior::class]);
        $behaviors['createdAt'] = [
            'class'      => \yii\behaviors\TimestampBehavior::class,
            'attributes' => [self::EVENT_BEFORE_INSERT => ['created_at']],
        ];

        return $behaviors;
    }

    /**
     * A row created directly (without Notifications::notify()) gets the
     * category of its record, otherwise "Прочее".
     */
    public function beforeSave($insert)
    {
        // Code may run before the migration of step У4a: the columns are optional then.
        if ($this->hasAttribute('category') && !$this->category) {
            $this->category = \Yii::$app->has('notifications')
                ? \Yii::$app->notifications->categoryOfModel($this->model_code)
                : 'other';
        }
        if ($insert && $this->hasAttribute('updated_at') && !$this->updated_at) {
            $this->updated_at = time();
        }

        return parent::beforeSave($insert);
    }

    public function getCategoryName(): string
    {
        return \Yii::$app->has('notifications') ? \Yii::$app->notifications->categoryName($this->category) : '';
    }

    /** "Не уведомлять о таком" is possible: a known kind that may be switched off. */
    public function getIsMutable(): bool
    {
        $kind = $this->kind && \Yii::$app->has('notifications') ? \Yii::$app->notifications->kind((string)$this->kind) : null;

        return $kind && $kind['mutable'];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsUser()
    {
        return $this->hasOne(\Yii::$app->user->identityClass, ['id' => 'cms_user_id']);
    }

    /**
     * One broken notification (a model without a label, a missing controller,
     * a failing widget) must not break the whole header notification list:
     * it is rendered without a link and the error goes to the log.
     */
    public function getHtml()
    {
        try {
            return $this->renderHtml();
        } catch (\Throwable $e) {
            \Yii::error("Web notification #{$this->id} ({$this->model_code}#{$this->model_id}) render failed: ".$e->getMessage()."\n".$e->getTraceAsString(), self::class);
        }

        return $this->renderHtml(false);
    }

    /**
     * Link label of the notification model: the asText property of CMS
     * records, the asText() method of plain Yii records, otherwise the id.
     */
    public function getModelLabel($model): string
    {
        if ($model->canGetProperty('asText')) {
            return (string)$model->asText;
        }
        if (method_exists($model, 'asText')) {
            return (string)$model->asText();
        }

        return '#'.(int)$this->model_id;
    }

    protected function renderHtml(bool $withLinks = true): string
    {
        [$name, $modelHtml] = $this->renderParts($withLinks);
        $class = $this->is_read ? "" : "sx-not-read";
        $time = \Yii::$app->formatter->asRelativeTime($this->created_at);

        return <<<HTML
<div class="sx-item {$class}">
<div class="sx-item-inner">
<div class="sx-time">{$time}</div>
<div class="sx-name">{$name}</div>
<div class="sx-model">{$modelHtml}</div>
</div>
</div>
HTML;
    }

    /**
     * The text of the notification (a link when it has only a URL) and the link
     * to its record, as HTML. Used by the header list and notification entries;
     * a failing record link falls back to plain text.
     *
     * @return string[] [name, model]
     */
    public function renderParts(bool $withLinks = true): array
    {
        if ($withLinks) {
            try {
                return $this->buildParts(true);
            } catch (\Throwable $e) {
                \Yii::error("Web notification #{$this->id} ({$this->model_code}#{$this->model_id}) render failed: ".$e->getMessage()."\n".$e->getTraceAsString(), self::class);
            }
        }

        return $this->buildParts(false);
    }

    private function buildParts(bool $withLinks): array
    {
        $model = $withLinks ? $this->model : null;
        $url = $withLinks ? $this->getTargetUrl() : null;
        $name = Html::encode($this->name);
        if (!$model && $url) {
            $name = Html::a($name, $url, [
                'class'      => 'sx-action-trigger',
                'aria-label' => (string)$this->name,
            ]);
        }
        $modelHtml = '';
        if ($model) {
            $controllerId = ArrayHelper::getValue(\Yii::$app->skeeks->modelsConfig, [$this->model_code, 'controller']);
            $label = $this->getModelLabel($model);
            $modelHtml = $url
                ? Html::a(Html::encode($label), $url, [
                    'class'      => 'sx-action-trigger',
                    'aria-label' => $label,
                ])
                : ($controllerId
                    ? BackendEntityLink::widget([
                        'controllerId' => $controllerId,
                        'modelId'      => $this->model_id,
                        'label'        => $label,
                        'options'      => [
                            'class'      => 'sx-action-trigger',
                            'aria-label' => $label,
                        ],
                    ])
                    : Html::encode($label));
        }

        return [$name, $modelHtml];
    }

    public function getModel()
    {
        $classData = (array)ArrayHelper::getValue(\Yii::$app->skeeks->modelsConfig, $this->model_code);

        if ($classData) {
            $class = (string)ArrayHelper::getValue($classData, 'class', $this->model_code);
            if (class_exists($class)) {
                return $class::find()->andWhere(['id' => $this->model_id])->one();
            }
        }

        return;
    }

    /**
     * Where `/~n/<id>` leads in a web request: the explicit link, otherwise
     * the record's page in the administration (view, else update), otherwise
     * the administration start page.
     */
    public function getOpenUrl(): string
    {
        $url = $this->getTargetUrl();
        // The short link never leads to another site, whatever a stored link says.
        if ($url && self::isLocalUrl($url)) {
            return $url;
        }
        $controllerId = $this->model_code ? ArrayHelper::getValue(\Yii::$app->skeeks->modelsConfig, [$this->model_code, 'controller']) : null;
        if ($controllerId && $this->model_id && $this->model) {
            $created = \Yii::$app->createController((string)$controllerId);
            $controller = $created ? $created[0] : null;
            if ($controller) {
                foreach (['view', 'update'] as $action) {
                    if ($controller->createAction($action)) {
                        return \yii\helpers\Url::to(['/'.trim((string)$controllerId, '/').'/'.$action, 'pk' => (int)$this->model_id]);
                    }
                }
            }
        }

        return \yii\helpers\Url::to(['/cms/admin-profile/notifications']);
    }

    /** A path of this site, or an absolute link to the host of the current request. */
    public static function isLocalUrl(string $url): bool
    {
        // Browsers read a backslash as a slash and drop control characters and spaces, so a link with
        // them may reach another host than parse_url() sees ("http://example.org\@localhost/").
        if ($url === '' || preg_match('~[\\\\\x00-\x20\x7f]~', $url)) {
            return false;
        }
        if (preg_match('~^/(?!/)~', $url)) {
            return true;
        }
        $host = parse_url($url, PHP_URL_HOST);
        $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));
        $current = \Yii::$app->has('request') && \Yii::$app->request instanceof \yii\web\Request ? (string)\Yii::$app->request->hostName : '';

        return $host && $current !== '' && in_array($scheme, ['http', 'https'], true) && strcasecmp((string)$host, $current) === 0;
    }

    /**
     * Explicit links are used when the recipient must open an entity in a
     * different application surface than the model's configured controller.
     * The CmsLead fallback keeps notifications created before the URL column
     * was introduced usable for a partner.
     */
    public function getTargetUrl(): ?string
    {
        if ($this->url) {
            return (string)$this->url;
        }

        $model = $this->model;
        if ($model instanceof CmsLead
            && \Yii::$app->has('user')
            && !\Yii::$app->user->isGuest
            && (int)$model->partner_id === (int)\Yii::$app->user->id
        ) {
            return $model->getPartnerViewUrl();
        }

        if ($model instanceof CmsTask
            && \Yii::$app->has('user')
            && !\Yii::$app->user->isGuest
            && (int)$model->created_by === (int)\Yii::$app->user->id
            && $model->isClientAuthored()
        ) {
            return $model->getClientViewUrl();
        }

        return null;
    }

    /**
     * @return CmsWebNotifyQuery
     */
    public static function find()
    {
        return new CmsWebNotifyQuery(get_called_class());
    }
}
