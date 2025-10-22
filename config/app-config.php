<?php
require_once __DIR__ . "/../model/service.php";
require_once __DIR__ . "/../model/house.php";

$config = array(
    'hostname'          =>          "localhost",
    'username'          =>          "u1819125_default",
    'password'          =>          "Tij0FS6PKtS39qZv",
    'database'          =>          "u1819125_3sfeed_db",
    'base_url'          =>          "https://3sfeed.rockmedia.pro/",
    'sources'           =>          [
        Service::CIAN->value  =>  [
            House::NG->value  =>  'https://pb12179.profitbase.ru/export/cian/b31f98cca0eae4ccc63c9ace72e988ab'
        ]
    ],
);