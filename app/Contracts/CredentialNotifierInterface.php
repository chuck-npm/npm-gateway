<?php
declare(strict_types=1);
namespace NpmGateway\Contracts;
use NpmGateway\ValueObjects\CredentialNotice;
interface CredentialNotifierInterface { public function notify(CredentialNotice $notice): void; }
