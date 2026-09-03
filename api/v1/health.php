<?php

declare(strict_types=1);require_once __DIR__.'/../../includes/api.php';cmsApiBootstrap();require_once __DIR__.'/../../includes/database.php';require_once __DIR__.'/../../includes/version.php';try{cmsPdo()->query('SELECT 1');echo json_encode(['ok'=>true,'status'=>'UP','version'=>UNIVERSAL_CMS_VERSION],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);}catch(Throwable $e){http_response_code(503);echo json_encode(['ok'=>false,'status'=>'DOWN','version'=>UNIVERSAL_CMS_VERSION],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);}
