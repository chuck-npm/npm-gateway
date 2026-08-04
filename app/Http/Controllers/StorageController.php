<?php
declare(strict_types=1);
namespace NpmGateway\Http\Controllers;
use NpmGateway\Http\AuthenticatedRequestContext;
use NpmGateway\Http\Response;
use NpmGateway\Services\PublishedStorageService;
final class StorageController
{
 public function __construct(private readonly PublishedStorageService $storage){}
 public function download(string $publicId,bool $image,AuthenticatedRequestContext $context):Response{$object=$this->storage->authorized($publicId,$context,$image?'embedded_image':'attachment');if($object===null)return new Response(404,'Not Found');try{$stream=$this->storage->open($object);}catch(\Throwable){return new Response(404,'Not Found');}$filename=$this->filename((string)$object['display_filename']);return new Response(200,'',['Content-Type'=>(string)$object['mime_type'],'Content-Disposition'=>($image?'inline':'attachment').'; filename="'.$filename.'"; filename*=UTF-8\'\''.rawurlencode($filename),'X-Content-Type-Options'=>'nosniff','Cache-Control'=>$image?'private':'private, no-store'],[],$stream);}
 private function filename(string $name):string{$safe=trim((string)preg_replace('/[^A-Za-z0-9._ -]/','_',basename(str_replace('\\','/',$name))));return $safe!==''?str_replace(['"','\\'],'_',$safe):'download';}
}
