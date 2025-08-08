
<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
require_once __DIR__ . '/vendor/autoload.php';
use Predis\Client as PredisClient;

$db = [
    'host' => 'localhost',
    'port' => 5432,
    'dbname' => 'test',
    'user' => 'root',
    'pass' => '',
];
$redis_host = '127.0.0.1';
$redis_port = 6379;
$lock_key = 'alpha_lock';
$lock_file = __DIR__ . '/alpha.lock';
$lock_ttl = 5;

function acquire_redis_lock($redis, $key, $ttl) {
    // Predis expects options as associative array
    return $redis->set($key, 1, ['nx' => true, 'ex' => $ttl]);
}
function release_redis_lock($redis, $key) {
    $redis->del([$key]);
}

function acquire_file_lock($file) {
    $fp = fopen($file, 'c');
    if (!$fp) return false;
    if (!flock($fp, LOCK_EX | LOCK_NB)) {
        fclose($fp);
        return false;
    }
    return $fp;
}
function release_file_lock($fp, $file) {
    flock($fp, LOCK_UN);
    fclose($fp);
    @unlink($file);
}

$locked = false;
$redis = null;
try {
    $redis = new PredisClient([
        'scheme' => 'tcp',
        'host'   => $redis_host,
        'port'   => $redis_port,
    ]);
    $locked = acquire_redis_lock($redis, $lock_key, $lock_ttl);
} catch (Exception $e) {
    $redis = null;
}
if (!$locked) {
    $fp = acquire_file_lock($lock_file);
    if ($fp) {
        $locked = true;
    }
}
if (!$locked) {
    http_response_code(409);
    echo 'Alpha is already running.';
    exit;
}

sleep(1);

try {
    $pdo = new PDO("pgsql:host={$db['host']};port={$db['port']};dbname={$db['dbname']}", $db['user'], $db['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $product = $pdo->query('SELECT id FROM products ORDER BY random() LIMIT 1')->fetch(PDO::FETCH_ASSOC);
    if ($product) {
        $stmt = $pdo->prepare('INSERT INTO orders (product_id) VALUES (:pid)');
        $stmt->execute([':pid' => $product['id']]);
        echo 'Order created for product_id: ' . $product['id'];
    } else {
        echo 'No products found.';
    }
} catch (Exception $e) {
    echo 'DB error: ' . $e->getMessage();
}

if ($redis && $locked) {
    release_redis_lock($redis, $lock_key);
}
if (isset($fp) && $fp) {
    release_file_lock($fp, $lock_file);
} 