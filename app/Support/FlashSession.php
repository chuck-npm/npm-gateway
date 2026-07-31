<?php
declare(strict_types=1);
namespace NpmGateway\Support;
final class FlashSession
{
    private array $session;
    public function __construct(array &$session){$this->session=&$session;}
    public function put(string $key,mixed $value):void{$this->session['_flash'][$key]=$value;}
    public function pull(string $key,mixed $default=null):mixed{$value=$this->session['_flash'][$key]??$default;unset($this->session['_flash'][$key]);return $value;}
}
