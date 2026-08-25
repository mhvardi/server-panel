<?php
$file = 'app/Http/Controllers/BackupTaskController.php';
$content = file_get_contents($file);

$content = str_replace(
    "'db_name' => 'nullable|string|required_if:include_db,true',",
    "",
    $content
);

$content = str_replace(
    "\$dbName = \$settings['db_name'] ?? null;",
    "\$dbName = \$service->getDatabaseName();",
    $content
);

file_put_contents($file, $content);
echo "Done";
