<?php
header('Content-Type: application/json');
echo json_encode([
    'status'    => 'ok',
    'message'   => 'Nepal Election API is running',
    'endpoints' => [
        'summary'        => '/api/summary.php',
        'parties'        => '/api/parties.php',
        'constituencies' => '/api/constituencies.php',
        'constituency'   => '/api/constituency.php?id=1',
    ],
]);
