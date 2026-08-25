<?php
$file = 'app/Console/Commands/RunServiceBackup.php';
$content = file_get_contents($file);

$search = <<<PHP
                // Backup database
                if (!empty(\$settings['include_db']) && !empty(\$settings['db_name'])) {
                    \$this->addLog("🗄️ در حال بکاپ از پایگاه‌داده: " . \$settings['db_name']);
PHP;

$replace = <<<PHP
                // Backup database
                \$dbName = \$service->getDatabaseName();
                if (!empty(\$settings['include_db']) && !empty(\$dbName)) {
                    \$this->addLog("🗄️ در حال بکاپ از پایگاه‌داده: " . \$dbName);
PHP;

$content = str_replace($search, $replace, $content);

$search2 = "\$dumpCmd[] = \$settings['db_name'];";
$replace2 = "\$dumpCmd[] = \$dbName;";
$content = str_replace($search2, $replace2, $content);

file_put_contents($file, $content);
echo "Done";
