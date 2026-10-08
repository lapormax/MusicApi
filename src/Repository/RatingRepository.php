<?php

declare(strict_types=1);

namespace App\Repository;

use InvalidArgumentException;
use PDO;

/** Accès aux notes et aux classements de l'application Music. */
final class RatingRepository
{
    public function __construct(private PDO $db)
    {
    }

    /** Retourne toutes les notes avec le titre de l'album et le nom de l'artiste. */
    public function findAll(): array
    {
        $sql = 'SELECT r.idRatings, r.Grade, r.Albums_idAlbums,
                       al.Titre, ar.idArtist, ar.Name AS ArtistName
                FROM ratings r
                INNER JOIN albums al ON al.idAlbums = r.Albums_idAlbums
                INNER JOIN artists ar ON ar.idArtist = al.Artist_idArtist
                ORDER BY r.idRatings DESC';

        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Cherche une note par son identifiant. */
    public function findById(int $ratingId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT r.idRatings, r.Grade, r.Albums_idAlbums,
                    al.Titre, ar.idArtist, ar.Name AS ArtistName
             FROM ratings r
             INNER JOIN albums al ON al.idAlbums = r.Albums_idAlbums
             INNER JOIN artists ar ON ar.idArtist = al.Artist_idArtist
             WHERE r.idRatings = :id'
        );
        $statement->execute(['id' => $ratingId]);
        $rating = $statement->fetch(PDO::FETCH_ASSOC);

        return $rating ?: null;
    }

    /** Retourne toutes les notes d'un album. */
    public function findByAlbumId(int $albumId): array
    {
        $statement = $this->db->prepare(
            'SELECT idRatings, Grade, Albums_idAlbums
             FROM ratings
             WHERE Albums_idAlbums = :album_id
             ORDER BY idRatings DESC'
        );
        $statement->execute(['album_id' => $albumId]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Retourne les notes correspondant à une valeur entre 1 et 5 étoiles. */
    public function findByGrade(int $grade): array
    {
        $this->validateGrade($grade);

        $statement = $this->db->prepare(
            'SELECT idRatings, Grade, Albums_idAlbums
             FROM ratings
             WHERE CAST(Grade AS UNSIGNED) = :grade
             ORDER BY idRatings DESC'
        );
        $statement->execute(['grade' => $grade]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Ajoute une note normalisée, par exemple « 5 Star ». */
    public function addRating(int $albumId, int $grade): int
    {
        $this->validateGrade($grade);

        $statement = $this->db->prepare(
            'INSERT INTO ratings (Grade, Albums_idAlbums)
             VALUES (:grade, :album_id)'
        );
        $statement->execute([
            'grade' => $grade . ' Star',
            'album_id' => $albumId,
        ]);

        return (int)$this->db->lastInsertId();
    }

    /** Modifie une note. */
    public function updateRating(int $ratingId, int $grade): bool
    {
        $this->validateGrade($grade);

        $statement = $this->db->prepare(
            'UPDATE ratings SET Grade = :grade WHERE idRatings = :id'
        );

        return $statement->execute([
            'grade' => $grade . ' Star',
            'id' => $ratingId,
        ]);
    }

    /** Supprime une note. */
    public function deleteRating(int $ratingId): bool
    {
        $statement = $this->db->prepare(
            'DELETE FROM ratings WHERE idRatings = :id'
        );
        $statement->execute(['id' => $ratingId]);

        return $statement->rowCount() > 0;
    }

    /** Calcule les statistiques des notes d'un album. */
    public function getAlbumStats(int $albumId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT al.idAlbums, al.Titre, ar.idArtist,
                    ar.Name AS ArtistName,
                    COUNT(r.idRatings) AS ratingsCount,
                    AVG(CAST(r.Grade AS UNSIGNED)) AS averageRating,
                    MIN(CAST(r.Grade AS UNSIGNED)) AS lowestRating,
                    MAX(CAST(r.Grade AS UNSIGNED)) AS highestRating
             FROM albums al
             INNER JOIN artists ar ON ar.idArtist = al.Artist_idArtist
             LEFT JOIN ratings r ON r.Albums_idAlbums = al.idAlbums
             WHERE al.idAlbums = :album_id
             GROUP BY al.idAlbums, al.Titre, ar.idArtist, ar.Name'
        );

        $statement->execute(['album_id' => $albumId]);
        $stats = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$stats) {
            return null;
        }

        $stats['ratingsCount'] = (int)$stats['ratingsCount'];
        $stats['averageRating'] = $stats['averageRating'] !== null
            ? round((float)$stats['averageRating'], 2)
            : null;
        $stats['lowestRating'] = $stats['lowestRating'] !== null
            ? (int)$stats['lowestRating']
            : null;
        $stats['highestRating'] = $stats['highestRating'] !== null
            ? (int)$stats['highestRating']
            : null;

        return $stats;
    }

    /** Retourne le nombre de notes reçues par un album. */
    public function countByAlbumId(int $albumId): int
    {
        $statement = $this->db->prepare(
            'SELECT COUNT(*) FROM ratings WHERE Albums_idAlbums = :album_id'
        );
        $statement->execute(['album_id' => $albumId]);

        return (int)$statement->fetchColumn();
    }

    /** Classe les albums par meilleure moyenne. */
    public function getTopRatedAlbums(int $limit = 10): array
    {
        return $this->getAlbumRanking('average', $limit);
    }

    /** Classe les albums par moins bonne moyenne. */
    public function getLowestRatedAlbums(int $limit = 10): array
    {
        return $this->getAlbumRanking('lowest', $limit);
    }

    /** Classe les albums qui ont reçu le plus de notes. */
    public function getMostRatedAlbums(int $limit = 10): array
    {
        return $this->getAlbumRanking('count', $limit);
    }

    /** Classe les artistes selon la moyenne des notes de leurs albums. */
    public function getTopRatedArtists(int $limit = 10): array
    {
        $limit = $this->normalizeLimit($limit);
        $sql = 'SELECT ar.idArtist, ar.Name AS ArtistName,
                       COUNT(r.idRatings) AS ratingsCount,
                       COUNT(DISTINCT al.idAlbums) AS ratedAlbumsCount,
                       ROUND(AVG(CAST(r.Grade AS UNSIGNED)), 2) AS averageRating
                FROM artists ar
                INNER JOIN albums al ON al.Artist_idArtist = ar.idArtist
                INNER JOIN ratings r ON r.Albums_idAlbums = al.idAlbums
                GROUP BY ar.idArtist, ar.Name
                ORDER BY averageRating DESC, ratingsCount DESC, ar.Name ASC
                LIMIT :limit';
        $statement = $this->db->prepare($sql);
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Renvoie le nombre d'étoiles de chaque valeur reçue pour un album. */
    public function getAlbumRatingDistribution(int $albumId): array
    {
        $statement = $this->db->prepare(
            'SELECT CAST(Grade AS UNSIGNED) AS grade,
                    COUNT(*) AS ratingsCount
             FROM ratings
             WHERE Albums_idAlbums = :album_id
             GROUP BY CAST(Grade AS UNSIGNED)
             ORDER BY grade DESC'
        );
        $statement->execute(['album_id' => $albumId]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getAlbumRanking(string $sort, int $limit): array
    {
        $limit = $this->normalizeLimit($limit);
        $orderBy = match ($sort) {
            'lowest' => 'averageRating ASC, ratingsCount DESC',
            'count' => 'ratingsCount DESC, averageRating DESC',
            default => 'averageRating DESC, ratingsCount DESC',
        };

        $sql = 'SELECT al.idAlbums, al.Titre, ar.idArtist,
                       ar.Name AS ArtistName,
                       COUNT(r.idRatings) AS ratingsCount,
                       ROUND(AVG(CAST(r.Grade AS UNSIGNED)), 2) AS averageRating
                FROM albums al
                INNER JOIN artists ar ON ar.idArtist = al.Artist_idArtist
                INNER JOIN ratings r ON r.Albums_idAlbums = al.idAlbums
                GROUP BY al.idAlbums, al.Titre, ar.idArtist, ar.Name
                ORDER BY ' . $orderBy . ', al.Titre ASC
                LIMIT :limit';
        $statement = $this->db->prepare($sql);
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    private function normalizeLimit(int $limit): int
    {
        return max(1, min($limit, 100));
    }

    private function validateGrade(int $grade): void
    {
        if ($grade < 1 || $grade > 5) {
            throw new InvalidArgumentException('La note doit être comprise entre 1 et 5.');

        }
    }
}
