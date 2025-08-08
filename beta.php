<?php
// CORS headers for all responses
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$n = isset($_GET['n']) ? intval($_GET['n']) : 1;
if ($n < 1 || $n > 1000) {
    http_response_code(400);
    echo 'Invalid n';
    exit;
}

$alpha_url = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . '/alpha.php';

$multi = curl_multi_init();
$handles = [];
for ($i = 0; $i < $n; $i++) {
    $ch = curl_init($alpha_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $handles[] = $ch;
    curl_multi_add_handle($multi, $ch);
}

do {
    $status = curl_multi_exec($multi, $active);
    curl_multi_select($multi);
} while ($active && $status == CURLM_OK);

$results = [];
foreach ($handles as $ch) {
    $results[] = curl_multi_getcontent($ch);
    curl_multi_remove_handle($multi, $ch);
    curl_close($ch);
}
curl_multi_close($multi);

echo json_encode([
    'requested' => $n,
    'results' => $results,
]); 