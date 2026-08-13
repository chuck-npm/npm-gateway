<?php declare(strict_types=1);namespace NpmGateway\Support;
final class MarketingFlyerTiming{
 private int$started;private array$marks=[];
 public function __construct(private readonly string$scope,private readonly string$publicId=''){ $this->started=hrtime(true); }
 public static function enabled():bool{return filter_var($_ENV['MARKETING_FLYER_TIMING_DIAGNOSTICS']??$_SERVER['MARKETING_FLYER_TIMING_DIAGNOSTICS']??getenv('MARKETING_FLYER_TIMING_DIAGNOSTICS')?:false,FILTER_VALIDATE_BOOL);}
 public function measure(string$stage,callable$operation,array$metadata=[]):mixed{$start=hrtime(true);try{return$operation();}finally{$this->record($stage,$start,$metadata);}}
 public function mark(string$stage,int$start,array$metadata=[]):void{$this->record($stage,$start,$metadata);}
 public function finish(array$metadata=[]):void{$this->record('total_'.$this->scope,$this->started,$metadata);}
 private function record(string$stage,int$start,array$metadata):void{if(!self::enabled())return;$safe=['diagnostic'=>'marketing_flyer_timing','scope'=>$this->scope,'stage'=>$stage,'elapsed_ms'=>round((hrtime(true)-$start)/1_000_000,3)];if($this->publicId!=='')$safe['public_id']=$this->publicId;foreach(['bytes','success','count']as$key)if(array_key_exists($key,$metadata))$safe[$key]=$metadata[$key];error_log((string)json_encode($safe,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));}
}
