<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\controllers;

use skeeks\cms\backend\themes\BackendThemePalette;
use skeeks\cms\backend\themes\BackendTheme;
use skeeks\cms\base\Component;
use skeeks\cms\base\Controller;
use skeeks\cms\components\BackendThemePaletteSettings;
use skeeks\cms\models\CmsComponentSettings;
use skeeks\cms\rbac\CmsManager;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

/**
 * Authenticated persistence endpoint used by admin and UPA theme drawers.
 */
class ThemePaletteController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'panel' => ['GET'],
                    'save' => ['POST'],
                    'save-default' => ['POST'],
                    'reset' => ['POST'],
                    'reset-default' => ['POST'],
                ],
            ],
        ];
    }

    public function beforeAction($action)
    {
        \Yii::$app->response->format = $action->id === 'panel'
            ? Response::FORMAT_HTML
            : Response::FORMAT_JSON;
        return parent::beforeAction($action);
    }

    public function actionPanel($scope)
    {
        $component = $this->createSettings($scope);
        $customizer = [
            'scope'               => $scope,
            'saveUrl'             => Url::to(['/cms/theme-palette/save', 'scope' => $scope]),
            'resetUrl'            => Url::to(['/cms/theme-palette/reset', 'scope' => $scope]),
            'saveDefaultUrl'      => Url::to(['/cms/theme-palette/save-default', 'scope' => $scope]),
            'resetDefaultUrl'     => Url::to(['/cms/theme-palette/reset-default', 'scope' => $scope]),
            'canApplyDefault'     => \Yii::$app->user->can(CmsManager::PERMISSION_ROLE_ADMIN_ACCESS),
            'resetDefaultConfirm' => \Yii::t('skeeks/backend', 'Reset shared settings for this color scheme?'),
            'headerModes'         => $component->getValidatedHeaderModes(),
        ];

        return $this->renderPartial('@skeeks/cms/backend/widgets/views/theme-customizer-panel', [
            'customizer' => $customizer,
        ]);
    }

    public function actionSave($scope)
    {
        $mode = $this->readMode();
        $palette = $this->readPalette($mode);
        $headerMode = $this->readHeaderMode();
        $component = $this->createSettings($scope);
        $storedPalette = $this->readStoredPalette($component, Component::OVERRIDE_USER);
        $storedHeaderModes = $this->readStoredHeaderModes($component, Component::OVERRIDE_USER);
        $storedPalette[$mode] = $palette;
        $storedHeaderModes[$mode] = $headerMode;

        $component->palette = $storedPalette;
        $component->headerModes = $storedHeaderModes;
        $component->setOverride(Component::OVERRIDE_USER);

        if (!$component->save(true, ['palette', 'headerModes'])) {
            return $this->failure($component->getFirstErrors());
        }

        return ['success' => true];
    }

    public function actionSaveDefault($scope)
    {
        if (!\Yii::$app->user->can(CmsManager::PERMISSION_ROLE_ADMIN_ACCESS)) {
            throw new ForbiddenHttpException('You cannot change the default theme palette.');
        }

        $mode = $this->readMode();
        $palette = $this->readPalette($mode);
        $headerMode = $this->readHeaderMode();
        $component = $this->createSettings($scope);
        $override = $component->cmsSite ? Component::OVERRIDE_SITE : Component::OVERRIDE_DEFAULT;
        $storedPalette = $this->readStoredPalette($component, $override);
        $storedHeaderModes = $this->readStoredHeaderModes($component, $override);
        $storedPalette[$mode] = $palette;
        $storedHeaderModes[$mode] = $headerMode;

        $component->palette = $storedPalette;
        $component->headerModes = $storedHeaderModes;
        $component->setOverride($override);

        if (!$component->save(true, ['palette', 'headerModes'])) {
            return $this->failure($component->getFirstErrors());
        }

        return ['success' => true];
    }

    public function actionReset($scope)
    {
        $mode = $this->readMode();
        $component = $this->createSettings($scope);
        $model = $this->findStoredSettings($component, Component::OVERRIDE_USER);

        return $this->resetStoredMode($component, $model, $mode);
    }

    public function actionResetDefault($scope)
    {
        if (!\Yii::$app->user->can(CmsManager::PERMISSION_ROLE_ADMIN_ACCESS)) {
            throw new ForbiddenHttpException('You cannot reset the default theme palette.');
        }

        $mode = $this->readMode();
        $component = $this->createSettings($scope);
        $override = $component->cmsSite ? Component::OVERRIDE_SITE : Component::OVERRIDE_DEFAULT;
        $model = $this->findStoredSettings($component, $override);

        return $this->resetStoredMode($component, $model, $mode);
    }

    private function resetStoredMode(BackendThemePaletteSettings $component, $model, $mode)
    {

        if (!$model) {
            return ['success' => true];
        }

        $value = (array) $model->value;
        $palette = isset($value['palette']) && is_array($value['palette'])
            ? $value['palette']
            : [];
        $headerModes = isset($value['headerModes']) && is_array($value['headerModes'])
            ? $value['headerModes']
            : [];
        unset($palette[$mode]);
        unset($headerModes[$mode]);

        if (!$palette && !$headerModes) {
            if ($model->delete() === false) {
                return $this->failure($model->getFirstErrors());
            }
        } else {
            $value['palette'] = $palette;
            $value['headerModes'] = $headerModes;
            $model->value = $value;
            if (!$model->save()) {
                return $this->failure($model->getFirstErrors());
            }
        }

        $component->invalidateCache();
        return ['success' => true];
    }

    private function findStoredSettings(BackendThemePaletteSettings $component, $override)
    {
        if ($override === Component::OVERRIDE_USER) {
            return CmsComponentSettings::findByComponentUser(
                $component,
                \Yii::$app->user->identity
            )->one();
        }
        if ($override === Component::OVERRIDE_SITE && $component->cmsSite) {
            return CmsComponentSettings::findByComponentSite($component, $component->cmsSite)->one();
        }

        return CmsComponentSettings::findByComponentDefault($component)->one();
    }

    private function createSettings($scope)
    {
        if (!in_array($scope, ['admin', 'upa'], true)) {
            throw new BadRequestHttpException('Unknown theme palette scope.');
        }

        return new BackendThemePaletteSettings([
            'namespace' => 'backend-theme-'.$scope,
        ]);
    }

    private function readMode()
    {
        $mode = (string) \Yii::$app->request->post('mode');
        if (!in_array($mode, [BackendThemePalette::MODE_LIGHT, BackendThemePalette::MODE_DARK], true)) {
            throw new BadRequestHttpException('Unknown theme palette mode.');
        }

        return $mode;
    }

    private function readPalette($mode)
    {
        try {
            $palette = Json::decode((string) \Yii::$app->request->post('palette'));
        } catch (\Throwable $exception) {
            throw new BadRequestHttpException('Invalid theme palette JSON.');
        }

        if (!is_array($palette)) {
            throw new BadRequestHttpException('Theme palette must be an object.');
        }

        return (new BackendThemePalette([$mode => $palette]))->getInput()[$mode];
    }

    private function readHeaderMode()
    {
        $headerMode = (string) \Yii::$app->request->post('headerMode');
        if (!in_array($headerMode, [
            BackendTheme::HEADER_MODE_THEME,
            BackendTheme::HEADER_MODE_LIGHT,
            BackendTheme::HEADER_MODE_DARK,
        ], true)) {
            throw new BadRequestHttpException('Unknown header appearance.');
        }

        return $headerMode;
    }

    private function readStoredPalette(BackendThemePaletteSettings $component, $override)
    {
        $model = $this->findStoredSettings($component, $override);

        if (!$model) {
            return [];
        }

        $value = (array) $model->value;
        return isset($value['palette']) && is_array($value['palette'])
            ? $value['palette']
            : [];
    }

    private function readStoredHeaderModes(BackendThemePaletteSettings $component, $override)
    {
        $model = $this->findStoredSettings($component, $override);

        if (!$model) {
            return [];
        }

        $value = (array) $model->value;
        return isset($value['headerModes']) && is_array($value['headerModes'])
            ? $value['headerModes']
            : [];
    }

    private function failure(array $errors)
    {
        return [
            'success' => false,
            'message' => $errors ? reset($errors) : 'Theme settings could not be saved.',
        ];
    }
}
