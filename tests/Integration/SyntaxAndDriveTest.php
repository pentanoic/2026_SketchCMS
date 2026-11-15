<?php

return function (TestRunner $t, string $root): void {
    $modulesDir = $root . 'app' . DIRECTORY_SEPARATOR . 'modules';
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($modulesDir));

    $prepareCount = 0;
    $dbSelectCount = 0;
    $phpFiles = 0;

    foreach ($it as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $phpFiles++;
            $content = file_get_contents($file->getPathname());
            if (strpos($content, '->db->prepare(') !== false) {
                $prepareCount++;
            }
            if (strpos($content, 'Database::select(') !== false) {
                $dbSelectCount++;
            }
        }
    }

    $t->assertTrue($phpFiles > 0, "Scanned {$phpFiles} PHP files in app/modules");
    $t->assertEquals(0, $prepareCount, '0 files in app/modules use ->db->prepare(');
    $t->assertEquals(0, $dbSelectCount, '0 files in app/modules use Database::select(');
};
