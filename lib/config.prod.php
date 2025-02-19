<?php
return [
    'db' => [
        'host' => getenv('MYSQL_HOST') ?: 'mysql-rogez.alwaysdata.net',
        'name' => getenv('MYSQL_DATABASE') ?: 'rogez_ecoride',
        'user' => getenv('MYSQL_USER') ?: 'rogez',
        'pass' => getenv('MYSQL_PASSWORD') ?: '120892Arras!',
        'charset' => 'utf8mb4'
    ],
'mongodb' => [
    'uri' => getenv('MONGODB_URI') ?: 'mongodb+srv://rogezaurore01:brXHv3MAcCFCz34C@cluster0.xxxxx.mongodb.net/',
    'db' => getenv('MONGODB_DB') ?: 'creditsPlateforme'
],
    'smtp' => [
        'host' => getenv('SMTP_HOST') ?: 'smtp.gmail.com',
        'user' => getenv('SMTP_USER') ?: 'testing.projets.siteweb@gmail.com',
        'pass' => getenv('SMTP_PASS') ?: 'sljw jlop qtyy mqae'
    ],
    'debug' => false
];