<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

class AlbumRepository extends BaseRepository
{
    protected string $table = 'albums';
    protected string $primaryKey = 'idAlbums';
    protected array $columns = ['Titre', 'Artist_idArtist'];

    public function addAlbum(string $titre, int $artistId): int
    {
        return $this->insert([
            'Titre' => $titre,
            'Artist_idArtist' => $artistId,
        ]);
    }

    public function findByTitle(string $titre): array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM `albums`
             WHERE `Titre` = :titre
             ORDER BY `idAlbums`'
        );
        $statement->execute(['titre' => $titre]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function searchByTitle(string $query): array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM `albums`
             WHERE `Titre` LIKE :query
             ORDER BY `Titre`'
        );
        $statement->execute(['query' => '%' . $query . '%']);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByArtistId(int $artistId): array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM `albums`
             WHERE `Artist_idArtist` = :artistId
             ORDER BY `Titre`'
        );
        $statement->execute(['artistId' => $artistId]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateAlbum(int $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteAlbum(int $id): bool
    {
        return $this->delete($id);
    }
}