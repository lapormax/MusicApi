<?php

declare(strict_types=1);

use App\Application\Actions\User\ListUsersAction;
use App\Application\Actions\User\ViewUserAction;
use App\Repository\ArtistRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

return function (App $app) {
    $app->options('/{routes:.*}', function (Request $request, Response $response) {
        // CORS Pre-Flight OPTIONS Request Handler
        return $response;
    });

    $app->get('/', function (Request $request, Response $response) {
        $response->getBody()->write('Hello world!');
        return $response;
    });

    $app->group('/users', function (Group $group) {
        $group->get('', ListUsersAction::class);
        $group->get('/{id}', ViewUserAction::class);
    });

    // Tous les artistes
    $app->get('/GetAllArtist', function (Request $request, Response $response) {
        $db = $this->get(PDO::class);
        $sth = $db->prepare("SELECT * FROM `artists`");
        $sth->execute();
        $data = $sth->fetchAll(PDO::FETCH_ASSOC);
        $payload = json_encode($data);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Un artiste par id
    $app->get('/getArtistById/{id}', function (Request $request, Response $response, array $args) {
        $id = $args['id'];
        $db = $this->get(PDO::class);
        $sth = $db->prepare("SELECT * FROM `artists` WHERE idArtist = :idartist");
        $sth->bindParam(':idartist', $id);
        $sth->execute();
        $data = $sth->fetch(PDO::FETCH_ASSOC);
        $payload = json_encode($data);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Ajouter un artiste
    $app->post('/AddArtist', function (Request $request, Response $response, array $args) {
        $input = $request->getParsedBody();
        $Name = $input['Name'];
        $Annee = $input['Annee'];
        $Description = $input['Description'];

        $db = $this->get(PDO::class);
        $sth = $db->prepare("INSERT INTO `artists` (`Name`, `Annee`, `Description`) VALUES (:name, :annee, :description)");

        $sth->bindParam(':name', $Name);
        $sth->bindParam(':annee', $Annee);
        $sth->bindParam(':description', $Description);
        $sth->execute();

        $insertedID = $db->lastInsertId();
        $payload = json_encode(['message' => 'Artiste ajouté avec succès', 'idArtist' => $insertedID]);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    });
    $app->group('/api', function (Group $group) {

        $group->get('/artists', function (
            Request $request,
            Response $response
        ) {
            $repo = $this->get(ArtistRepository::class);

            $response->getBody()->write(
                json_encode($repo->findAll())
            );

            return $response->withHeader(
                'Content-Type',
                'application/json'
            );
        });

        $group->get('/artists/{id}', function (
            Request $request,
            Response $response,
            array $args
        ) {
            $artist = $this->get(ArtistRepository::class)
                ->findById((int) $args['id']);

            $response->getBody()->write(
                json_encode(
                    $artist ?? ['error' => 'Artiste introuvable']
                )
            );

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus($artist ? 200 : 404);
        });

        $group->post('/artists', function (
            Request $request,
            Response $response
        ) {
            $id = $this->get(ArtistRepository::class)
                ->insert((array) $request->getParsedBody());

            $response->getBody()->write(
                json_encode(['idArtist' => $id])
            );

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);
        });

    })->add(new \App\Middleware\JwtMiddleware());
};
