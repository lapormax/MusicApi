<?php

declare(strict_types=1);

use App\Application\Actions\User\ListUsersAction;
use App\Application\Actions\User\ViewUserAction;
use App\Repository\AlbumRepository;
use App\Repository\ArtistRepository;
use App\Repository\RatingRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

return function (App $app): void {
    $app->options('/{routes:.*}', function (
        Request $request,
        Response $response
    ): Response {
        return $response;
    });

    $app->get('/', function (
        Request $request,
        Response $response
    ): Response {
        $response->getBody()->write('Hello world!');

        return $response;
    });

    $app->group('/users', function (Group $group): void {
        $group->get('', ListUsersAction::class);
        $group->get('/{id}', ViewUserAction::class);
    });

    // Anciennes routes artistes
    $app->get('/GetAllArtist', function (
        Request $request,
        Response $response
    ): Response {
        $db = $this->get(PDO::class);
        $statement = $db->query('SELECT * FROM `artists`');
        $response->getBody()->write(
            json_encode($statement->fetchAll(PDO::FETCH_ASSOC)) ?: '[]'
        );

        return $response->withHeader('Content-Type', 'application/json');
    });

    $app->get('/getArtistById/{id}', function (
        Request $request,
        Response $response,
        array $args
    ): Response {
        $db = $this->get(PDO::class);
        $statement = $db->prepare(
            'SELECT * FROM `artists` WHERE `idArtist` = :id'
        );
        $statement->execute(['id' => (int) $args['id']]);
        $artist = $statement->fetch(PDO::FETCH_ASSOC);

        $response->getBody()->write(
            json_encode($artist ?: ['error' => 'Artiste introuvable']) ?: '{}'
        );

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($artist ? 200 : 404);
    });

    $app->post('/AddArtist', function (
        Request $request,
        Response $response
    ): Response {
        $data = (array) $request->getParsedBody();

        if (empty($data['Name']) || empty($data['Annee'])) {
            $response->getBody()->write(json_encode([
                'error' => 'Name et Annee sont obligatoires',
            ]) ?: '{}');

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }

        $db = $this->get(PDO::class);
        $statement = $db->prepare(
            'INSERT INTO `artists` (`Name`, `Annee`, `Description`)
             VALUES (:name, :annee, :description)'
        );
        $statement->execute([
            'name' => $data['Name'],
            'annee' => $data['Annee'],
            'description' => $data['Description'] ?? null,
        ]);

        $response->getBody()->write(json_encode([
            'message' => 'Artiste ajouté avec succès',
            'idArtist' => (int) $db->lastInsertId(),
        ]) ?: '{}');

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(201);
    });

    $app->group('/api', function (Group $group): void {
        /*
         * Routes artistes statiques : elles doivent apparaître
         * avant /artists/{id}.
         */
        $group->get('/artists/search/name', function (
            Request $request,
            Response $response
        ): Response {
            $name = (string) ($request->getQueryParams()['name'] ?? '');
            $artists = $this->get(ArtistRepository::class)->findByName($name);

            $response->getBody()->write(json_encode($artists) ?: '[]');

            return $response->withHeader('Content-Type', 'application/json');
        });

        $group->get('/artists/search', function (
            Request $request,
            Response $response
        ): Response {
            $query = (string) ($request->getQueryParams()['q'] ?? '');
            $artists = $this->get(ArtistRepository::class)
                ->searchByName($query);

            $response->getBody()->write(json_encode($artists) ?: '[]');

            return $response->withHeader('Content-Type', 'application/json');
        });

        $group->get('/artists/year/{year}', function (
            Request $request,
            Response $response,
            array $args
        ): Response {
            $artists = $this->get(ArtistRepository::class)
                ->findByYear((int) $args['year']);

            $response->getBody()->write(json_encode($artists) ?: '[]');

            return $response->withHeader('Content-Type', 'application/json');
        });

        $group->get('/artists/years/{from}/{to}', function (
            Request $request,
            Response $response,
            array $args
        ): Response {
            $artists = $this->get(ArtistRepository::class)->findByYearRange(
                (int) $args['from'],
                (int) $args['to']
            );

            $response->getBody()->write(json_encode($artists) ?: '[]');

            return $response->withHeader('Content-Type', 'application/json');
        });

        // Liste des artistes
        $group->get('/artists', function (
            Request $request,
            Response $response
        ): Response {
            $artists = $this->get(ArtistRepository::class)->findAll();

            $response->getBody()->write(json_encode($artists) ?: '[]');

            return $response->withHeader('Content-Type', 'application/json');
        });

        // Un artiste par identifiant
        $group->get('/artists/{id}', function (
            Request $request,
            Response $response,
            array $args
        ): Response {
            $artist = $this->get(ArtistRepository::class)
                ->findById((int) $args['id']);

            $response->getBody()->write(json_encode(
                $artist ?? ['error' => 'Artiste introuvable']
            ) ?: '{}');

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus($artist ? 200 : 404);
        });

        // Ajouter un artiste
        $group->post('/artists', function (
            Request $request,
            Response $response
        ): Response {
            $data = (array) $request->getParsedBody();

            if (empty($data['Name']) || empty($data['Annee'])) {
                $response->getBody()->write(json_encode([
                    'error' => 'Name et Annee sont obligatoires',
                ]) ?: '{}');

                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(400);
            }

            $id = $this->get(ArtistRepository::class)->addArtist(
                (string) $data['Name'],
                (int) $data['Annee'],
                isset($data['Description'])
                    ? (string) $data['Description']
                    : null
            );

            $response->getBody()->write(
                json_encode(['idArtist' => $id]) ?: '{}'
            );

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);
        });

        // Modifier un artiste
        $group->put('/artists/{id}', function (
            Request $request,
            Response $response,
            array $args
        ): Response {
            $updated = $this->get(ArtistRepository::class)->updateArtist(
                (int) $args['id'],
                (array) $request->getParsedBody()
            );

            $response->getBody()->write(
                json_encode(['updated' => $updated]) ?: '{}'
            );

            return $response->withHeader('Content-Type', 'application/json');
        });

        // Supprimer un artiste
        $group->delete('/artists/{id}', function (
            Request $request,
            Response $response,
            array $args
        ): Response {
            $deleted = $this->get(ArtistRepository::class)
                ->deleteArtist((int) $args['id']);

            $response->getBody()->write(
                json_encode(['deleted' => $deleted]) ?: '{}'
            );

            return $response->withHeader('Content-Type', 'application/json');
        });

        /*
         * Routes albums
         */
        $group->get('/albums/search/title', function (
            Request $request,
            Response $response
        ): Response {
            $title = (string) ($request->getQueryParams()['titre'] ?? '');
            $albums = $this->get(AlbumRepository::class)->findByTitle($title);

            $response->getBody()->write(json_encode($albums) ?: '[]');

            return $response->withHeader('Content-Type', 'application/json');
        });

        $group->get('/albums/search', function (
            Request $request,
            Response $response
        ): Response {
            $query = (string) ($request->getQueryParams()['q'] ?? '');
            $albums = $this->get(AlbumRepository::class)
                ->searchByTitle($query);

            $response->getBody()->write(json_encode($albums) ?: '[]');

            return $response->withHeader('Content-Type', 'application/json');
        });

        $group->get('/albums/by-artist/{artistId}', function (
            Request $request,
            Response $response,
            array $args
        ): Response {
            $albums = $this->get(AlbumRepository::class)
                ->findByArtistId((int) $args['artistId']);

            $response->getBody()->write(json_encode($albums) ?: '[]');

            return $response->withHeader('Content-Type', 'application/json');
        });

        $group->get('/albums', function (
            Request $request,
            Response $response
        ): Response {
            $albums = $this->get(AlbumRepository::class)->findAll();

            $response->getBody()->write(json_encode($albums) ?: '[]');

            return $response->withHeader('Content-Type', 'application/json');
        });

        $group->get('/albums/{id}', function (
            Request $request,
            Response $response,
            array $args
        ): Response {
            $album = $this->get(AlbumRepository::class)
                ->findById((int) $args['id']);

            $response->getBody()->write(json_encode(
                $album ?? ['error' => 'Album introuvable']
            ) ?: '{}');

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus($album ? 200 : 404);
        });

        $group->post('/albums', function (
            Request $request,
            Response $response
        ): Response {
            $data = (array) $request->getParsedBody();

            if (empty($data['Titre']) || empty($data['Artist_idArtist'])) {
                $response->getBody()->write(json_encode([
                    'error' => 'Titre et Artist_idArtist sont obligatoires',
                ]) ?: '{}');

                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(400);
            }

            $id = $this->get(AlbumRepository::class)->addAlbum(
                (string) $data['Titre'],
                (int) $data['Artist_idArtist']
            );

            $response->getBody()->write(
                json_encode(['idAlbums' => $id]) ?: '{}'
            );

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);
        });

        $group->put('/albums/{id}', function (
            Request $request,
            Response $response,
            array $args
        ): Response {
            $updated = $this->get(AlbumRepository::class)->updateAlbum(
                (int) $args['id'],
                (array) $request->getParsedBody()
            );

            $response->getBody()->write(
                json_encode(['updated' => $updated]) ?: '{}'
            );

            return $response->withHeader('Content-Type', 'application/json');
        });

        $group->delete('/albums/{id}', function (
            Request $request,
            Response $response,
            array $args
        ): Response {
            $deleted = $this->get(AlbumRepository::class)
                ->deleteAlbum((int) $args['id']);

            $response->getBody()->write(
                json_encode(['deleted' => $deleted]) ?: '{}'
            );

            return $response->withHeader('Content-Type', 'application/json');
        });
        // Notes et classements

        $group->get('/ratings', function (
            Request $request,
            Response $response
        ): Response {
            $ratings = $this->get(RatingRepository::class)->findAll();
            $response->getBody()->write(json_encode($ratings) ?: '[]');

            return $response->withHeader('Content-Type', 'application/json');
        });

        $group->get('/ratings/{id}', function (
            Request $request,
            Response $response,
            array $args
        ): Response {
            $rating = $this->get(RatingRepository::class)
                ->findById((int) $args['id']);

            $response->getBody()->write(json_encode(
                $rating ?? ['error' => 'Note introuvable']
            ) ?: '{}');

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus($rating ? 200 : 404);
        });

        $group->get('/albums/{albumId}/ratings', function (
            Request $request,
            Response $response,
            array $args
        ): Response {
            $ratings = $this->get(RatingRepository::class)
                ->findByAlbumId((int) $args['albumId']);

            $response->getBody()->write(json_encode($ratings) ?: '[]');

            return $response->withHeader('Content-Type', 'application/json');
        });

        $group->post('/albums/{albumId}/ratings', function (
            Request $request,
            Response $response,
            array $args
        ): Response {
            $data = (array) $request->getParsedBody();
            $grade = filter_var($data['grade'] ?? null, FILTER_VALIDATE_INT);

            if ($grade === false || $grade < 1 || $grade > 5) {
                $response->getBody()->write(json_encode([
                    'error' => 'La note doit être un nombre entre 1 et 5',
                ]) ?: '{}');

                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(400);
            }

            $id = $this->get(RatingRepository::class)->addRating(
                (int) $args['albumId'],
                $grade
            );

            $response->getBody()->write(
                json_encode(['idRatings' => $id]) ?: '{}'
            );

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);
        });

        $group->put('/ratings/{id}', function (
            Request $request,
            Response $response,
            array $args
        ): Response {
            $data = (array) $request->getParsedBody();
            $grade = filter_var($data['grade'] ?? null, FILTER_VALIDATE_INT);

            if ($grade === false || $grade < 1 || $grade > 5) {
                $response->getBody()->write(json_encode([
                    'error' => 'La note doit être un nombre entre 1 et 5',
                ]) ?: '{}');

                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(400);
            }

            $updated = $this->get(RatingRepository::class)->updateRating(
                (int) $args['id'],
                $grade
            );

            $response->getBody()->write(
                json_encode(['updated' => $updated]) ?: '{}'
            );

            return $response->withHeader('Content-Type', 'application/json');
        });

        $group->delete('/ratings/{id}', function (
            Request $request,
            Response $response,
            array $args
        ): Response {
            $deleted = $this->get(RatingRepository::class)->deleteRating(
                (int) $args['id']
            );

            $response->getBody()->write(
                json_encode(['deleted' => $deleted]) ?: '{}'
            );

            return $response->withHeader('Content-Type', 'application/json');
        });

        $group->get('/albums/{albumId}/rating-stats', function (
            Request $request,
            Response $response,
            array $args
        ): Response {
            $stats = $this->get(RatingRepository::class)
                ->getAlbumStats((int) $args['albumId']);

            $response->getBody()->write(json_encode(
                $stats ?? ['error' => 'Album introuvable']
            ) ?: '{}');

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus($stats ? 200 : 404);
        });

        $group->get('/albums/{albumId}/ratings/distribution', function (
            Request $request,
            Response $response,
            array $args
        ): Response {
            $distribution = $this->get(RatingRepository::class)
                ->getAlbumRatingDistribution((int) $args['albumId']);

            $response->getBody()->write(json_encode($distribution) ?: '[]');

            return $response->withHeader('Content-Type', 'application/json');
        });

        $group->get('/rankings/albums', function (
            Request $request,
            Response $response
        ): Response {
            $limit = (int) ($request->getQueryParams()['limit'] ?? 10);
            $ranking = $this->get(RatingRepository::class)
                ->getTopRatedAlbums($limit);

            $response->getBody()->write(json_encode($ranking) ?: '[]');

            return $response->withHeader('Content-Type', 'application/json');
        });

        $group->get('/rankings/albums/lowest-rated', function (
            Request $request,
            Response $response
        ): Response {
            $limit = (int) ($request->getQueryParams()['limit'] ?? 10);
            $ranking = $this->get(RatingRepository::class)
                ->getLowestRatedAlbums($limit);

            $response->getBody()->write(json_encode($ranking) ?: '[]');

            return $response->withHeader('Content-Type', 'application/json');
        });

        $group->get('/rankings/albums/most-rated', function (
            Request $request,
            Response $response
        ): Response {
            $limit = (int) ($request->getQueryParams()['limit'] ?? 10);
            $ranking = $this->get(RatingRepository::class)
                ->getMostRatedAlbums($limit);

            $response->getBody()->write(json_encode($ranking) ?: '[]');

            return $response->withHeader('Content-Type', 'application/json');
        });

        $group->get('/rankings/artists', function (
            Request $request,
            Response $response
        ): Response {
            $limit = (int) ($request->getQueryParams()['limit'] ?? 10);
            $ranking = $this->get(RatingRepository::class)
                ->getTopRatedArtists($limit);

            $response->getBody()->write(json_encode($ranking) ?: '[]');

            return $response->withHeader('Content-Type', 'application/json');
        });
    })->add(new \App\Middleware\JwtMiddleware());
};
