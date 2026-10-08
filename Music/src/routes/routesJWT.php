<?php

declare(strict_types=1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Middleware\JwtMiddleware;
use App\Middleware\JwtHelper;
use Slim\App;

return function (App $app) {
    // Route publique (connexion)
    $app->post('/login', function (Request $request, Response $response) {
        $params = (array)$request->getParsedBody();
        $username = $params['username'];
        $password = $params['password'];

        // Vérifie les identifiants (exemple simple, en production on utilise une méthode sécurisée)
        if ($username === 'SaintMichel' && $password === 'ITcampus') {
            $userData = ['id' => 1, 'username' => $username];

            // Génère le token JWT
            $token = JwtHelper::generateToken($userData);

            // Renvoie le token dans la réponse
            $response->getBody()->write(json_encode(['token' => $token]));
            return $response->withHeader('Content-Type', 'application/json');
        }

        // Identifiants invalides : 401 Unauthorized
        $response->getBody()->write(json_encode(['error' => 'Invalid credentials']));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    });

    // Route protégée (exemple)
    $app->get('/protected', function (Request $request, Response $response) {
        // Accède aux données de l'utilisateur depuis le token
        $user = $request->getAttribute('user');
        $response->getBody()->write(json_encode(['message' => 'Hello, ' . $user->username]));
        return $response->withHeader('Content-Type', 'application/json');
    })->add(new JwtMiddleware());
};