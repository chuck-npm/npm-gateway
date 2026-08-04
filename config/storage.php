<?php
declare(strict_types=1);
$env=static fn(string $name):string=>(string)($_ENV[$name]??$_SERVER[$name]??getenv($name)?:'');
$testPrefix=$env('WASABI_TEST_PREFIX');
return ['provider'=>'wasabi','endpoint'=>$env('WASABI_ENDPOINT'),'region'=>$env('WASABI_REGION'),'container'=>$env('WASABI_BUCKET'),'access_key'=>$env('WASABI_ACCESS_KEY'),'secret_key'=>$env('WASABI_SECRET_KEY'),'attachment_prefix'=>$env('WASABI_COMPANY_NOTICE_ATTACHMENTS_PREFIX'),'image_prefix'=>$env('WASABI_COMPANY_NOTICE_IMAGES_PREFIX'),'test_prefix'=>$testPrefix!==''?$testPrefix:'company_notices/test/'];
