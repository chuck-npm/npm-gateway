<?php
declare(strict_types=1);
namespace NpmGateway\Console;
final class StreamConsoleIo implements ConsoleIoInterface
{
    public function read(string $prompt): string
    {
        $this->write($prompt);
        $value = fgets(STDIN);
        return $value === false ? '' : rtrim($value, "\r\n");
    }
    public function write(string $text): void { fwrite(STDOUT, $text); }
    public function error(string $text): void { fwrite(STDERR, $text); }
}
