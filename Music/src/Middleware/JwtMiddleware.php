<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use App\Middleware\JwtHelper; // Import de la classe JwtHelper

class JwtMiddleware
{
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $authHeader = $request->getHeaderLine('Authorization');
        if ($authHeader) {
            // Extrait le token du header (format "Bearer <token>")
            list($jwt) = sscanf($authHeader, 'Bearer %s');

            if ($jwt) {
                // Valide le token
                $decoded = JwtHelper::validateToken($jwt);
                if ($decoded) {
                    // Ajoute les données de l'utilisateur à la requête (si besoin)
                    $request = $request->withAttribute('user', $decoded->data);
                    return $handler->handle($request);
                }
            }
        }

        $response = new \Slim\Psr7\Response(); // Token invalide ou absent : on renvoie 401 Unauthorized
        $response->getBody()->write(json_encode(['error' => 'Unauthorized']));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    }
}