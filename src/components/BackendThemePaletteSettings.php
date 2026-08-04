<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\components;

use skeeks\cms\backend\themes\BackendThemePalette;
use skeeks\cms\backend\themes\BackendTheme;
use skeeks\cms\base\Component;

/**
 * Persistence adapter for the storage-agnostic backend theme customizer.
 *
 * CmsComponentSettings supplies the default -> site -> user cascade. The
 * namespace separates admin, UPA and future cabinet palettes.
 */
class BackendThemePaletteSettings extends Component
{
    /** @var array */
    public $palette = [];

    /** @var array */
    public $headerModes = [];

    /** @return array */
    public function rules()
    {
        return [
            [['palette'], 'validatePalette'],
            [['headerModes'], 'validateHeaderModes'],
        ];
    }

    public function validateHeaderModes($attribute)
    {
        if (!is_array($this->{$attribute})) {
            $this->addError($attribute, 'Header modes must be an array.');
            return;
        }

        foreach ($this->{$attribute} as $themeMode => $headerMode) {
            if (!in_array($themeMode, [BackendTheme::THEME_MODE_LIGHT, BackendTheme::THEME_MODE_DARK], true)
                || !in_array($headerMode, [BackendTheme::HEADER_MODE_THEME, BackendTheme::HEADER_MODE_LIGHT, BackendTheme::HEADER_MODE_DARK], true)
            ) {
                $this->addError($attribute, 'Unknown header appearance.');
                return;
            }
        }
    }

    public function validatePalette($attribute)
    {
        if (!is_array($this->{$attribute})) {
            $this->addError($attribute, 'Theme palette must be an array.');
            return;
        }

        try {
            new BackendThemePalette($this->{$attribute});
        } catch (\Throwable $exception) {
            $this->addError($attribute, $exception->getMessage());
        }
    }

    /**
     * Invalid legacy/database values must never reach inline CSS.
     *
     * @return array
     */
    public function getValidatedPalette()
    {
        try {
            return (new BackendThemePalette((array) $this->palette))->getInput();
        } catch (\Throwable $exception) {
            \Yii::error($exception->getMessage(), static::class);
            return [];
        }
    }

    /** @return array */
    public function getValidatedHeaderModes()
    {
        $result = [
            BackendTheme::THEME_MODE_LIGHT => BackendTheme::HEADER_MODE_DARK,
            BackendTheme::THEME_MODE_DARK  => BackendTheme::HEADER_MODE_DARK,
        ];

        foreach ((array) $this->headerModes as $themeMode => $headerMode) {
            if (isset($result[$themeMode]) && in_array($headerMode, [
                BackendTheme::HEADER_MODE_THEME,
                BackendTheme::HEADER_MODE_LIGHT,
                BackendTheme::HEADER_MODE_DARK,
            ], true)) {
                $result[$themeMode] = $headerMode;
            }
        }

        return $result;
    }
}
