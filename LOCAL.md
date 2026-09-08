# Développement local (Windows et MySQL)

Depuis `C:\Carre`, lancer :

```powershell
composer dev
```

Ouvrir http://127.0.0.1:8000. Cette commande lance Laravel, la file de tâches et Vite. Arrêter avec Ctrl+C. Le port Vite est choisi automatiquement si 5173 est occupé.

Compte de développement créé par le seeder : `test@example.com`, mot de passe `password`. Connexion sur `/login`, administration sur `/admin/dashboard`.

Dans MySQL Workbench, utiliser la connexion locale `127.0.0.1:3306`, utilisateur `root`, avec le mot de passe de votre fichier `.env`. Rafraîchir les schémas pour voir `carre` (MySQL Windows peut afficher le nom en minuscules).

Le fichier `.env` reste ignoré par Git. La clé Laravel a été générée et `APP_URL` pointe vers http://127.0.0.1:8000. Les images publiques utilisent le lien `public/storage`. Les courriels locaux sont écrits dans `storage/logs/laravel.log`.

Après récupération de nouvelles modifications :

```powershell
composer install
npm.cmd ci
php artisan migrate
```

Compiler l'interface : `npm.cmd run build`. Exécuter les tests : `php artisan test` (base SQLite en mémoire, indépendante de MySQL).

La commande `composer dev` omet Laravel Pail, qui nécessite PCNTL indisponible sur Windows. Pour suivre les logs dans un autre terminal :

```powershell
Get-Content storage/logs/laravel.log -Wait -Tail 50
```

État lors de la préparation : compilation réussie ; 34 tests réussis et 2 échecs dans `GeneratedCompositionManagementTest` (champ obligatoire `fingerprint` absent des données de test). npm signale 13 vulnérabilités dans les dépendances installées, dont 2 critiques ; les versions verrouillées du dépôt ont été conservées.
