# API Music

API REST de gestion d’artistes, d’albums et de notes, développée en PHP avec Slim 4 et PHP-DI. Les routes `/api` sont protégées par un jeton JWT.

## Prérequis

- PHP 8 ou plus récent
- Extensions PHP PDO MySQL (`pdo_mysql`) et JSON
- Composer 2
- Une base MySQL ou MariaDB
- Postman, facultatif pour tester l’API

## Structure du projet

- `app/` : configuration, routes et définitions du conteneur
- `public/` : point d’entrée HTTP (`index.php`)
- `src/Repository/` : accès aux données artistes, albums et notes
- `src/routes/routesJWT.php` : connexion et route protégée d’exemple
- `composer.json` et `composer.lock` : dépendances PHP

## Installation dans PhpStorm

Ouvre dans PhpStorm le dossier qui contient `composer.json`. Installe les dépendances avec l’outil Composer de PhpStorm (`install`), ou avec cette commande dans le terminal intégré, à la racine du projet :

```bash
composer install
```

Démarre ensuite le serveur PHP avec le script Composer `start`. En local, l’API est généralement disponible à :

```text
http://localhost:8080
```

## Base de données

La base doit contenir les tables et colonnes suivantes :

- `artists` : `idArtist`, `Name`, `Annee`, `Description`
- `albums` : `idAlbums`, `Titre`, `Artist_idArtist`
- `ratings` : `idRatings`, `Grade`, `Albums_idAlbums`

`albums.Artist_idArtist` référence `artists.idArtist`.  
`ratings.Albums_idAlbums` référence `albums.idAlbums`.

Importe le fichier SQL du projet s’il existe, ou crée les tables correspondantes dans ton outil de gestion de base de données.

Le fichier `app/settings.php` lit les paramètres de connexion depuis l’environnement PHP. Exemple :

```php
<?php

$dbSettings = [
    'host' => getenv('DB_HOST'),
    'username' => getenv('DB_USER'),
    'database' => getenv('DB_NAME'),
    'password' => getenv('DB_PASSWORD'),
];
```

Configure ces variables dans ton environnement local ou dans Alwaysdata. **Ne mets pas leurs valeurs dans ce README ni dans un dépôt partagé.**

Dans `app/repositories.php`, vérifie que les dépôts `ArtistRepository`, `AlbumRepository` et `RatingRepository` sont importés et enregistrés dans le conteneur PHP-DI.

## Authentification JWT

La route `POST /login` est publique. Dans Postman, choisis **POST**, puis **Body → raw → JSON**, et saisis tes identifiants localement. Ne les copie pas dans ce README, une capture publique ou un dépôt partagé.

Une connexion réussie renvoie un champ `token`. Pour appeler une route `/api`, ouvre **Authorization → Bearer Token** dans Postman et colle le jeton seul.

La durée de validité du jeton dépend de la configuration actuelle. Dans la version de démonstration, les identifiants de connexion et la clé JWT sont définis dans le code (`src/routes/routesJWT.php` et `src/Middleware/JwtHelper.php`). Ne publie pas ces valeurs.

## URL de base

En local, utilise :

```text
http://localhost:8080
```

En ligne, remplace cette adresse par le domaine fourni par Alwaysdata, par exemple :

```text
https://TON-SOUS-DOMAINE.alwaysdata.net
```

Dans les tableaux ci-dessous, remplace `{id}`, `{albumId}`, `{artistId}`, `{year}`, `{from}` et `{to}` par des valeurs réelles.

Toutes les routes commençant par `/api` nécessitent un jeton JWT.

## Artistes

| Méthode | Chemin | Description |
|---|---|---|
| GET | `/api/artists` | Liste tous les artistes |
| GET | `/api/artists/{id}` | Renvoie un artiste par identifiant |
| GET | `/api/artists/search/name?name=Daft%20Punk` | Recherche exacte par nom |
| GET | `/api/artists/search?q=daft` | Recherche partielle par nom |
| GET | `/api/artists/year/{year}` | Recherche par année |
| GET | `/api/artists/years/{from}/{to}` | Recherche entre deux années |
| POST | `/api/artists` | Ajoute un artiste |
| PUT | `/api/artists/{id}` | Modifie un artiste |
| DELETE | `/api/artists/{id}` | Supprime un artiste |

Exemple de JSON pour ajouter un artiste :

```json
{
  "Name": "Daft Punk",
  "Annee": 1993,
  "Description": "Duo de musique électronique"
}
```

Pour modifier un artiste, envoie uniquement les champs à changer.

## Albums

| Méthode | Chemin | Description |
|---|---|---|
| GET | `/api/albums` | Liste tous les albums |
| GET | `/api/albums/{id}` | Renvoie un album par identifiant |
| GET | `/api/albums/search/title?titre=Discovery` | Recherche exacte par titre |
| GET | `/api/albums/search?q=disco` | Recherche partielle par titre |
| GET | `/api/albums/by-artist/{artistId}` | Liste les albums d’un artiste |
| POST | `/api/albums` | Ajoute un album |
| PUT | `/api/albums/{id}` | Modifie un album |
| DELETE | `/api/albums/{id}` | Supprime un album |

Exemple de JSON pour ajouter un album :

```json
{
  "Titre": "Discovery",
  "Artist_idArtist": 1
}
```

Pour modifier un album, envoie uniquement les champs à changer.

## Notes (ratings)

Une note est un nombre entier compris entre 1 et 5.

| Méthode | Chemin | Description |
|---|---|---|
| GET | `/api/ratings` | Liste toutes les notes |
| GET | `/api/ratings/{id}` | Renvoie une note par identifiant |
| GET | `/api/albums/{albumId}/ratings` | Liste les notes d’un album |
| POST | `/api/albums/{albumId}/ratings` | Ajoute une note à un album |
| PUT | `/api/ratings/{id}` | Modifie une note |
| DELETE | `/api/ratings/{id}` | Supprime une note |
| GET | `/api/albums/{albumId}/rating-stats` | Affiche les statistiques de notes d’un album |
| GET | `/api/albums/{albumId}/ratings/distribution` | Affiche la répartition des notes d’un album |

Exemple de JSON pour ajouter une note à l’album 1 :

```json
{
  "grade": 5
}
```

Pour modifier une note, envoie le même type de JSON à `PUT /api/ratings/{id}`.

## Classements (rankings)

| Méthode | Chemin | Description |
|---|---|---|
| GET | `/api/rankings/albums` | Classe les albums par meilleure moyenne |
| GET | `/api/rankings/albums?limit=5` | Affiche les 5 premiers albums |
| GET | `/api/rankings/albums/lowest-rated` | Classe les albums par moyenne la plus basse |
| GET | `/api/rankings/albums/most-rated` | Classe les albums ayant reçu le plus de notes |
| GET | `/api/rankings/artists` | Classe les artistes selon les notes de leurs albums |
| GET | `/api/rankings/artists?limit=5` | Affiche les 5 premiers artistes |

Le paramètre `limit` est facultatif. Par exemple :

```text
http://localhost:8080/api/rankings/albums?limit=5
```

## Exemples d’URL complètes en local

```text
http://localhost:8080/login
http://localhost:8080/api/artists
http://localhost:8080/api/artists/search?q=daft
http://localhost:8080/api/albums
http://localhost:8080/api/albums/by-artist/1
http://localhost:8080/api/ratings
http://localhost:8080/api/albums/1/ratings
http://localhost:8080/api/albums/1/rating-stats
http://localhost:8080/api/rankings/albums
http://localhost:8080/api/rankings/albums/most-rated
http://localhost:8080/api/rankings/artists?limit=5
```

Pour tester en ligne, remplace `http://localhost:8080` par ton domaine Alwaysdata :

```text
https://TON-SOUS-DOMAINE.alwaysdata.net/api/artists
https://TON-SOUS-DOMAINE.alwaysdata.net/api/albums
https://TON-SOUS-DOMAINE.alwaysdata.net/api/ratings
https://TON-SOUS-DOMAINE.alwaysdata.net/api/rankings/albums
https://TON-SOUS-DOMAINE.alwaysdata.net/api/rankings/artists
```

## Routes supplémentaires

| Méthode | Chemin | Description |
|---|---|---|
| GET | `/` | Vérifie que l’application répond |
| GET | `/users` | Liste les utilisateurs de démonstration |
| GET | `/users/{id}` | Renvoie un utilisateur de démonstration |
| GET | `/GetAllArtist` | Ancienne route de liste des artistes |
| GET | `/getArtistById/{id}` | Ancienne route artiste par identifiant |
| POST | `/AddArtist` | Ancienne route d’ajout d’artiste |

Les méthodes d’un Repository ne créent pas automatiquement une URL : chaque URL doit aussi être déclarée dans `app/routes.php`, et le Repository correspondant doit être enregistré dans `app/repositories.php`.

## Tester avec Postman

1. Envoie `POST /login` avec tes identifiants saisis localement, puis récupère le jeton JWT.
2. Pour appeler une route `/api`, choisis **Authorization → Bearer Token** et colle le jeton.
3. Pour les requêtes `POST` et `PUT`, sélectionne **Body → raw → JSON**.
4. Pour supprimer une donnée, choisis la méthode `DELETE` et indique son URL.

## Déploiement sur Alwaysdata avec PhpStorm

1. Configure la connexion SFTP Alwaysdata dans **Settings → Build, Execution, Deployment → Deployment**.
2. Envoie le projet avec **Tools → Deployment → Upload to…**.
3. Dans l’administration Alwaysdata, configure le site en PHP et choisis le dossier `public` comme racine web.
4. Configure la base de données et les variables d’environnement sur le serveur. Ne mets pas leurs valeurs dans le README.
5. En SSH, place-toi dans le dossier contenant `composer.json` et installe les dépendances de production :

   ```bash
   composer install --no-dev --optimize-autoloader
   ```

6. Utilise le domaine Alwaysdata comme base d’URL.
7. Envoie un nouveau `POST /login` sur le site en ligne pour obtenir un jeton destiné au serveur en ligne.