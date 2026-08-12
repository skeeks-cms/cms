<?php

$workerView = file_get_contents(dirname(__DIR__).'/src/widgets/admin/views/worker-view.php');
$userView = file_get_contents(dirname(__DIR__).'/src/widgets/admin/views/user-view.php');

function personViewLinkRoutingExpect($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

personViewLinkRoutingExpect(
    strpos($workerView, "\$cmsUser->is_worker ? '/cms/admin-worker' : '/cms/admin-user'") !== false,
    'Worker widget does not route clients to the client card.'
);
personViewLinkRoutingExpect(
    substr_count($workerView, "'controllerId' => \$entityControllerId") === 2,
    'Worker widget does not use the resolved card type for both person links.'
);
personViewLinkRoutingExpect(
    substr_count($userView, "'controllerId' => '/cms/admin-user'") === 2,
    'Client widget does not consistently target the client card.'
);

echo "CMS person-view link routing contract: OK\n";
