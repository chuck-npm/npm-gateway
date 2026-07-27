<?php
declare(strict_types=1);
namespace NpmGateway\Http\Middleware;
use NpmGateway\Exceptions\Domain\InvalidSessionException;
use NpmGateway\Http\AuthenticatedRequestContext;
use NpmGateway\Http\Request;
use NpmGateway\Http\Response;
use NpmGateway\Http\SessionCookie;
use NpmGateway\Contracts\SessionServiceInterface;
use NpmGateway\ValueObjects\ClientContext;
final class RequireAuthenticationMiddleware
{
 public function __construct(private readonly SessionServiceInterface $sessions,private readonly SessionCookie $cookie){}
 /** @param callable(AuthenticatedRequestContext):Response $next */
 public function handle(Request $request,callable $next,\DateTimeImmutable $now):Response
 {
  $raw=$this->cookie->read($request);if($raw===null)return Response::redirect('/login');
  try{$result=$this->sessions->validate($raw,new ClientContext($request->ip(),$request->agent(),$now));$response=$next(new AuthenticatedRequestContext($result->user,$result->rotatedToken?->reveal()??$raw));if($result->rotatedToken)return new Response($response->status,$response->body,$response->headers,[...$response->cookies,$this->cookie->set($result->rotatedToken->reveal())]);return $response;}
  catch(InvalidSessionException){$r=Response::redirect('/login');return new Response($r->status,$r->body,$r->headers,[$this->cookie->clear()]);}
 }
}
