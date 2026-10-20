<?php

$views = [
    'deal'       => file_get_contents(dirname(__DIR__).'/src/views/admin-cms-deal/view.php'),
    'bill'       => file_get_contents(dirname(__DIR__).'/src/views/admin-cms-bill/view.php'),
    'contractor' => file_get_contents(dirname(__DIR__).'/src/views/admin-cms-contractor/view.php'),
    'document'   => file_get_contents(dirname(__DIR__).'/src/views/admin-cms-document/view.php'),
];

function financeCardSurfaceExpect($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

foreach ($views as $name => $view) {
    financeCardSurfaceExpect(strpos($view, 'BackendSurfaceWidget::begin') !== false, ucfirst($name).' view does not use the canonical surface widget.');
    financeCardSurfaceExpect(strpos($view, "'raised'") !== false, ucfirst($name).' view does not use the raised surface variant.');
    financeCardSurfaceExpect(strpos($view, "'bodyFlush'") !== false, ucfirst($name).' view does not use the flush surface body.');
    financeCardSurfaceExpect(strpos($view, 'sx-panel') === false, ucfirst($name).' view still contains deprecated sx-panel markup.');
    financeCardSurfaceExpect(strpos($view, 'sx-block') === false, ucfirst($name).' view still contains deprecated sx-block markup.');
}

financeCardSurfaceExpect(strpos($views['deal'], 'sx-surface sx-deal-overview-item') !== false, 'Deal overview items are not canonical surfaces.');
financeCardSurfaceExpect(strpos($views['bill'], 'sx-surface sx-bill-entity') !== false, 'Bill entity cards are not canonical surfaces.');
financeCardSurfaceExpect(strpos($views['bill'], 'sx-surface sx-bill-requisite') !== false, 'Bill requisites are not canonical surfaces.');
financeCardSurfaceExpect(strpos($views['contractor'], 'sx-surface sx-contractor-info') !== false, 'Contractor details are not canonical surfaces.');
financeCardSurfaceExpect(strpos($views['document'], 'sx-surface sx-document-entity') !== false, 'Document entity cards are not canonical surfaces.');

echo "CMS finance card surface migration contract: OK\n";
