<?php
if (isset($_GET['data']) && $_GET['data'] === 'articles') {
    header('Content-Type: application/json; charset=utf-8');
    $dataFile = __DIR__ . '/_antigravity/data-test/articles.json';
    echo file_exists($dataFile) ? file_get_contents($dataFile) : '[]';
    exit;
}

header('Content-Type: text/html; charset=utf-8');
readfile(__DIR__ . '/tests/run-normal/index.html');
