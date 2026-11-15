<?php

return array (
  'app' => 
  array (
    'name' => 'LAB302',
    'description' => 'từng bước vững vàng, mở lối tương lai',
    'keyword' => 'lab302, huce, nuce, sketchcms',
    'site' => 
    array (
      'scheme' => 'http://',
      'host' => 'sketchcms.test',
    ),
  ),
  'url_rules' => 
  array (
    'rewrite' => 
    array (
      '/faq/terms' => 
      array (
        'target' => '/home/faq/terms',
        'method' => 'GET|POST',
      ),
      '/faq/about' => 
      array (
        'target' => '/home/faq/about',
        'method' => 'GET|POST',
      ),
      '/faq/help' => 
      array (
        'target' => '/home/faq/help',
        'method' => 'GET|POST',
      ),
    ),
    'disable' => 
    array (
    ),
  ),
);
