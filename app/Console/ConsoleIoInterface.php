<?php
declare(strict_types=1);
namespace NpmGateway\Console;
interface ConsoleIoInterface
{
    public function read(string $prompt): string;
    public function write(string $text): void;
    public function error(string $text): void;
}
