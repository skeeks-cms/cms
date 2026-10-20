<?php

function themePaletteResetExpect($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$controller = file_get_contents(dirname(__DIR__).'/src/controllers/ThemePaletteController.php');

themePaletteResetExpect(
    strpos($controller, "'reset-default' => ['POST']") !== false,
    'Shared palette reset must be POST-only.'
);
themePaletteResetExpect(
    strpos($controller, "'resetDefaultUrl'") !== false,
    'Theme customizer config has no shared reset endpoint.'
);
themePaletteResetExpect(
    strpos($controller, 'function actionResetDefault') !== false,
    'Shared palette reset action is missing.'
);
themePaletteResetExpect(
    substr_count($controller, 'CmsManager::PERMISSION_ROLE_ADMIN_ACCESS') >= 3,
    'Shared palette reset must use the same administrator permission as applying defaults.'
);
themePaletteResetExpect(
    strpos($controller, 'unset($palette[$mode]);') !== false
    && strpos($controller, 'unset($headerModes[$mode]);') !== false,
    'Resetting a shared mode must remove both its palette and header override.'
);
themePaletteResetExpect(
    strpos($controller, 'Component::OVERRIDE_SITE : Component::OVERRIDE_DEFAULT') !== false,
    'Shared reset must target the normal site/default settings cascade.'
);

echo "Theme palette shared reset contract: OK\n";
