<?php

require_once 'vendor/autoload.php';

// Load the controller file
$class = 'App\Http\Controllers\Api\V1\AppointmentController';
$file = 'app/Http/Controllers/Api/V1/AppointmentController.php';

echo "File contents (first 20 lines):\n";
$lines = file($file);
foreach (array_slice($lines, 0, 20) as $i => $line) {
    echo ($i + 1) . ': ' . $line;
}

echo "\n\nChecking class exists: ";
echo class_exists($class) ? "YES\n" : "NO\n";

echo "Getting file via reflection: ";
$r = new ReflectionClass($class);
echo $r->getFileName() . "\n";

echo "Getting store() method:\n";
$method = $r->getMethod('store');
$startLine = $method->getStartLine();
$endLine = $method->getEndLine();
echo "Lines $startLine to $endLine\n";

$allLines = file($r->getFileName());
for ($i = $startLine - 1; $i < min($startLine + 4, $endLine); $i++) {
    echo ($i + 1) . ': ' . $allLines[$i];
}
