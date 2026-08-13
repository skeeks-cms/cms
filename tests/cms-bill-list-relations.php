<?php

$controller = file_get_contents(dirname(__DIR__).'/src/controllers/AdminCmsBillController.php');

function cmsBillListRelationsExpect($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

cmsBillListRelationsExpect(
    strpos($controller, "'relations',") !== false,
    'The default bills grid must expose the combined relations column.'
);
cmsBillListRelationsExpect(
    strpos($controller, "'relations' => [") !== false && strpos($controller, "'label'          => 'Связь'") !== false,
    'The combined bill column must be labelled Connection.'
);
cmsBillListRelationsExpect(
    strpos($controller, 'foreach ($shopBill->deals as $cmsDeal)') !== false,
    'The combined bill column must render linked deals.'
);
cmsBillListRelationsExpect(
    strpos($controller, 'Html::encode($shopBill->description)') !== false,
    'The combined bill column must render the encoded bill comment.'
);
cmsBillListRelationsExpect(
    strpos($controller, "'style' => 'white-space: nowrap;'") === false,
    'Bill titles must be allowed to wrap in narrow grids.'
);

echo "CMS bill list relations contract: OK\n";
