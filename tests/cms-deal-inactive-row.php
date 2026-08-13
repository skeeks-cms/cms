<?php

$controller = file_get_contents(dirname(__DIR__).'/src/controllers/AdminCmsDealController.php');

function cmsDealInactiveRowExpect($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

cmsDealInactiveRowExpect(
    strpos($controller, "'rowOptions' => function (CmsDeal \$cmsDeal)") !== false,
    'The deals grid must configure inactive state on the complete row.'
);
cmsDealInactiveRowExpect(
    strpos($controller, "'class' => 'sx-cms-deal-row--inactive'") !== false,
    'Inactive deal rows must have a semantic CSS class.'
);
cmsDealInactiveRowExpect(
    strpos($controller, 'if (!$cmsDeal->is_active || $isExpired)') !== false,
    'Disabled and expired deals must both be treated as inactive rows.'
);
cmsDealInactiveRowExpect(
    strpos($controller, "'style' => 'opacity: 0.5;'") !== false,
    'Inactive deal rows must be visually dimmed.'
);
cmsDealInactiveRowExpect(
    strpos($controller, "addClass('sx-tr-gray')") === false,
    'Inactive deal state must not depend on column-rendered JavaScript.'
);

echo "CMS deal inactive row contract: OK\n";
