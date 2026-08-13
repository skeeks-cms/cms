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
    strpos($controller, 'if (!$cmsDeal->is_active)') !== false,
    'Only disabled deals must be treated as inactive rows.'
);
cmsDealInactiveRowExpect(
    strpos($controller, "'style' => 'opacity: 0.5;'") !== false,
    'Inactive deal rows must be visually dimmed.'
);
cmsDealInactiveRowExpect(
    strpos($controller, "'class' => 'sx-collection-item--danger'") !== false,
    'Active expired deal rows must use the shared semantic danger state.'
);
cmsDealInactiveRowExpect(
    strpos($controller, "addClass('sx-tr-red')") === false,
    'Expired deal state must not depend on column-rendered JavaScript.'
);
cmsDealInactiveRowExpect(
    strpos($controller, "addClass('sx-tr-gray')") === false,
    'Inactive deal state must not depend on column-rendered JavaScript.'
);
cmsDealInactiveRowExpect(
    strpos($controller, "'client',") !== false && strpos($controller, "'label'  => 'Клиент'") !== false,
    'The deals grid must expose the combined Client column.'
);

echo "CMS deal inactive row contract: OK\n";
