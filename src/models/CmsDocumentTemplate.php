<?php

namespace skeeks\cms\models;

use skeeks\cms\base\ActiveRecord;
use skeeks\cms\models\behaviors\HasStorageFile;
use yii\helpers\ArrayHelper;

/**
 * Company-owned presentation profile for generated documents.
 *
 * The document structure remains in code. This model stores only a selected
 * base theme and safe visual overrides for the current site/company.
 *
 * @property int $id
 * @property int $cms_site_id
 * @property string $name
 * @property string $theme
 * @property string $document_type
 * @property int $is_active
 * @property int $is_default
 * @property int|null $logo_storage_file_id
 * @property string|null $accent_color
 * @property string|null $background_color
 * @property string|null $surface_color
 * @property string|null $text_color
 * @property string|null $muted_color
 * @property string|null $border_color
 * @property string|null $footer_text
 * @property int $show_cover
 * @property int $show_footer
 * @property int $show_page_numbers
 * @property string $page_orientation
 *
 * @property CmsSite $cmsSite
 * @property CmsStorageFile|null $logoStorageFile
 */
class CmsDocumentTemplate extends ActiveRecord
{
    const THEME_DARK = 'skeeks-dark';
    const THEME_LIGHT = 'skeeks-light';

    const DOCUMENT_ALL = 'all';
    const DOCUMENT_TASK_REPORT = 'task-report';

    const ORIENTATION_PORTRAIT = 'portrait';
    const ORIENTATION_LANDSCAPE = 'landscape';

    public static function tableName()
    {
        return '{{%cms_document_template}}';
    }

    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            HasStorageFile::class => [
                'class'  => HasStorageFile::class,
                'fields' => ['logo_storage_file_id'],
            ],
        ]);
    }

    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [['cms_site_id', 'name', 'theme', 'document_type'], 'required'],
            [['cms_site_id', 'logo_storage_file_id'], 'integer'],
            [['is_active', 'is_default', 'show_cover', 'show_footer', 'show_page_numbers'], 'boolean'],
            [['footer_text'], 'string'],
            [['name'], 'string', 'max' => 255],
            [['theme', 'document_type', 'page_orientation'], 'string', 'max' => 32],
            ['theme', 'in', 'range' => array_keys(static::themes())],
            ['document_type', 'in', 'range' => array_keys(static::documentTypes())],
            ['page_orientation', 'in', 'range' => array_keys(static::pageOrientations())],
            [['name'], 'unique', 'targetAttribute' => ['cms_site_id', 'name']],
            [['cms_site_id'], 'exist', 'skipOnError' => true, 'targetClass' => CmsSite::class, 'targetAttribute' => ['cms_site_id' => 'id']],
            [['logo_storage_file_id'], 'exist', 'skipOnError' => true, 'targetClass' => CmsStorageFile::class, 'targetAttribute' => ['logo_storage_file_id' => 'id']],
            [[
                'accent_color',
                'background_color',
                'surface_color',
                'text_color',
                'muted_color',
                'border_color',
            ], 'match', 'pattern' => '/^#[0-9a-f]{6}$/i', 'message' => 'Укажите цвет в формате #RRGGBB.'],
            [[
                'accent_color',
                'background_color',
                'surface_color',
                'text_color',
                'muted_color',
                'border_color',
                'footer_text',
                'logo_storage_file_id',
            ], 'default', 'value' => null],
            ['theme', 'default', 'value' => self::THEME_DARK],
            ['document_type', 'default', 'value' => self::DOCUMENT_ALL],
            ['page_orientation', 'default', 'value' => self::ORIENTATION_PORTRAIT],
            [['is_active', 'show_cover', 'show_footer', 'show_page_numbers'], 'default', 'value' => 1],
            ['is_default', 'default', 'value' => 0],
        ]);
    }

    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'name'                 => 'Название',
            'theme'                => 'Готовая основа',
            'document_type'        => 'Тип документа',
            'is_active'            => 'Активен',
            'is_default'           => 'Использовать по умолчанию',
            'logo_storage_file_id' => 'Логотип',
            'accent_color'         => 'Акцентный цвет',
            'background_color'     => 'Фон документа',
            'surface_color'        => 'Фон карточек',
            'text_color'           => 'Основной текст',
            'muted_color'          => 'Дополнительный текст',
            'border_color'         => 'Границы',
            'footer_text'          => 'Текст в подвале',
            'show_cover'           => 'Показывать обложку',
            'show_footer'          => 'Показывать подвал',
            'show_page_numbers'    => 'Показывать номера страниц',
            'page_orientation'     => 'Ориентация страницы',
        ]);
    }

    public function attributeHints()
    {
        return ArrayHelper::merge(parent::attributeHints(), [
            'logo_storage_file_id' => 'Если не задан, используется подходящий логотип из раздела «Моя компания».',
            'document_type'        => 'Профиль для всех документов можно переопределить более конкретным профилем.',
            'is_default'           => 'Для сайта и выбранного типа документа может быть только один профиль по умолчанию.',
        ]);
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        if ($this->is_default) {
            $this->is_active = 1;
        }

        return true;
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        if ($this->is_default) {
            static::updateAll([
                'is_default' => 0,
            ], [
                'and',
                ['cms_site_id' => $this->cms_site_id, 'document_type' => $this->document_type],
                ['!=', 'id', $this->id ?: 0],
            ]);
        }
    }

    public function getCmsSite()
    {
        return $this->hasOne(CmsSite::class, ['id' => 'cms_site_id']);
    }

    public function getLogoStorageFile()
    {
        return $this->hasOne(CmsStorageFile::class, ['id' => 'logo_storage_file_id']);
    }

    public static function themes()
    {
        return [
            self::THEME_DARK  => 'SkeekS Dark',
            self::THEME_LIGHT => 'SkeekS Light',
        ];
    }

    public static function documentTypes()
    {
        return [
            self::DOCUMENT_ALL         => 'Все документы',
            self::DOCUMENT_TASK_REPORT => 'Отчёт по задачам',
        ];
    }

    public static function pageOrientations()
    {
        return [
            self::ORIENTATION_LANDSCAPE => 'Альбомная',
            self::ORIENTATION_PORTRAIT  => 'Книжная',
        ];
    }

    public static function preset($theme)
    {
        $presets = [
            self::THEME_DARK => [
                'id'                => 'preset:'.self::THEME_DARK,
                'name'              => 'SkeekS Dark',
                'theme'             => self::THEME_DARK,
                'accent_color'      => '#ff8a4c',
                'accent_alt_color'  => '#f5326b',
                'success_color'     => '#35d0a0',
                'warning_color'     => '#ffd166',
                'gradient_from'     => '#191d2b',
                'gradient_to'       => '#0e1017',
                'background_color'  => '#12141d',
                'surface_color'     => '#1f2330',
                'text_color'        => '#e8ecf3',
                'muted_color'       => '#98a0b3',
                'border_color'      => '#272c3a',
                'show_cover'        => true,
                'show_footer'       => true,
                'show_page_numbers' => true,
                'page_orientation'  => self::ORIENTATION_PORTRAIT,
                'footer_text'       => null,
            ],
            self::THEME_LIGHT => [
                'id'                => 'preset:'.self::THEME_LIGHT,
                'name'              => 'SkeekS Light',
                'theme'             => self::THEME_LIGHT,
                'accent_color'      => '#e85f3c',
                'accent_alt_color'  => '#df326d',
                'success_color'     => '#1f9d70',
                'warning_color'     => '#b47b00',
                'gradient_from'     => '#ffffff',
                'gradient_to'       => '#eef2f8',
                'background_color'  => '#f4f6fa',
                'surface_color'     => '#ffffff',
                'text_color'        => '#172033',
                'muted_color'       => '#667085',
                'border_color'      => '#d9e2ef',
                'show_cover'        => true,
                'show_footer'       => true,
                'show_page_numbers' => true,
                'page_orientation'  => self::ORIENTATION_PORTRAIT,
                'footer_text'       => null,
            ],
        ];

        return ArrayHelper::getValue($presets, $theme, $presets[self::THEME_LIGHT]);
    }

    public function getThemeConfig()
    {
        $config = static::preset($this->theme);
        $config['id'] = 'id:'.$this->id;
        $config['name'] = $this->name;

        foreach ([
            'accent_color',
            'background_color',
            'surface_color',
            'text_color',
            'muted_color',
            'border_color',
            'footer_text',
        ] as $attribute) {
            if ($this->{$attribute} !== null && $this->{$attribute} !== '') {
                $config[$attribute] = $this->{$attribute};
            }
        }

        foreach (['show_cover', 'show_footer', 'show_page_numbers'] as $attribute) {
            $config[$attribute] = (bool)$this->{$attribute};
        }
        $config['page_orientation'] = $this->page_orientation;
        $config['model'] = $this;

        return $config;
    }

    public static function findDefault($cmsSiteId, $documentType)
    {
        $specific = static::find()
            ->andWhere([
                'cms_site_id'  => (int)$cmsSiteId,
                'document_type' => $documentType,
                'is_active'     => 1,
                'is_default'    => 1,
            ])
            ->one();

        if ($specific) {
            return $specific;
        }

        return static::find()
            ->andWhere([
                'cms_site_id'   => (int)$cmsSiteId,
                'document_type' => self::DOCUMENT_ALL,
                'is_active'     => 1,
                'is_default'    => 1,
            ])
            ->one();
    }

    public static function resolveTheme($selection, CmsSite $site, $documentType)
    {
        if ((string)$selection === '') {
            return null;
        }

        if (strpos((string)$selection, 'preset:') === 0) {
            $theme = substr((string)$selection, 7);
            if (isset(static::themes()[$theme])) {
                return static::preset($theme);
            }
        }

        if (strpos((string)$selection, 'id:') === 0) {
            $id = (int)substr((string)$selection, 3);
            $model = static::find()
                ->andWhere(['id' => $id, 'cms_site_id' => $site->id, 'is_active' => 1])
                ->andWhere(['document_type' => [self::DOCUMENT_ALL, $documentType]])
                ->one();
            if ($model) {
                return $model->themeConfig;
            }

            // Saved report links may contain an ID from a database that has
            // since been replaced. Keep the explicit unbranded option empty,
            // but recover stale/inaccessible IDs with this site's default.
            $model = static::findDefault($site->id, $documentType);
            if ($model) {
                return $model->themeConfig;
            }
        }

        return null;
    }
}
