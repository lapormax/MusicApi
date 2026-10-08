<?php

namespace App\Repository;

class AlbumRepository extends BaseRepository
{
    protected string $table = 'albums';
    protected string $primaryKey = 'idAlbums';
    protected array $columns = ['Titre', 'Artist_idArtist'];
}