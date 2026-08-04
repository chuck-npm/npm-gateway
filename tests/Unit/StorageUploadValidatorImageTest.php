<?php
declare(strict_types=1);
namespace Tests\Unit;
use NpmGateway\Services\StorageUploadValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class StorageUploadValidatorImageTest extends TestCase
{
 private array $paths=[];
 protected function tearDown():void{foreach($this->paths as $path)if(is_file($path))unlink($path);}
 public static function jpegNames():array{return [['photo.jpg'],['photo.jpeg'],['Flamingo Flats Old.jpg'],['UPPER.JPEG']];}
 #[DataProvider('jpegNames')]
 public function testJpegMimeAcceptsApprovedExtensionsSpacesAndCase(string $name):void
 {
  $path=tempnam(sys_get_temp_dir(),'npm-jpeg-');$this->paths[]=$path;$image=imagecreatetruecolor(2,2);imagejpeg($image,$path,90);imagedestroy($image);
  $result=(new StorageUploadValidator())->validate(['name'=>$name,'tmp_name'=>$path,'size'=>filesize($path),'error'=>UPLOAD_ERR_OK],'attachment',0,0);
  self::assertSame('image/jpeg',$result['mime_type']);self::assertSame($name,$result['display_filename']);self::assertSame(str_ends_with(strtolower($name),'.jpg')?'JPG':'JPEG',$result['type_label']);
 }
}
