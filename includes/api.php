<?php

declare(strict_types=1);
require_once __DIR__.'/config.php';

function cmsApiBootstrap(): void
{
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    $origin=(string)($_SERVER['HTTP_ORIGIN']??'');
    $allowed=(array)(cmsConfig()['allowed_origins']??[]);
    if($origin!=='' && in_array($origin,$allowed,true)){
        header('Access-Control-Allow-Origin: '.$origin);
        header('Vary: Origin');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        header('Access-Control-Max-Age: 600');
    }
    if(($_SERVER['REQUEST_METHOD']??'GET')==='OPTIONS'){
        http_response_code(204);exit;
    }
}
