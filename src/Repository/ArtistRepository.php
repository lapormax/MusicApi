<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Artist;
use PDO;

class ArtistRepository extends BaseRepository
{
    protected string $table = 'artists';
    protected string $primaryKey = 'idArtist';
    protected array $columns = ['Name', 'Annee', 'Description'];
    protected ?string $entityClass = Artist::class;

    public function addArtist(
        string $name,
        int $annee,
        ?string $description = null
    ): int {
        return $this->insert([
            'Name' => $name,
            'Annee' => $annee,
            'Description' => $description,
        ]);
    }

    /** Recherche les artistes dont le nom correspond exactement. */
    public function findByName(string $name): array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM `artists` WHERE `Name` = :name ORDER BY `idArtist`'
        );
        $statement->execute(['name' => $name]);

        return array_map(
            [$this, 'hydrate'],
            $statement->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    /** Recherche les artistes dont le nom contient le texte fourni. */
    public function searchByName(string $query): array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM `artists` WHERE `Name` LIKE :query ORDER BY `Name`'
        );
        $statement->execute(['query' => '%' . $query . '%']);

        return array_map(
            [$this, 'hydrate'],
            $statement->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    public function findByYear(int $annee): array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM `artists` WHERE `Annee` = :annee ORDER BY `Name`'
        );
        $statement->execute(['annee' => $annee]);

        return array_map(
            [$this, 'hydrate'],
            $statement->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    public function findByYearRange(int $from, int $to): array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM `artists`
             WHERE `Annee` BETWEEN :year_from AND :year_to
             ORDER BY `Annee`, `Name`'
        );
        $statement->execute([
            'year_from' => $from,
            'year_to' => $to,
        ]);

        return array_map(
            [$this, 'hydrate'],
            $statement->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    public function updateArtist(int $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteArtist(int $id): bool
    {
        return $this->delete($id);
    }
}