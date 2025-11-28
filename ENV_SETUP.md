# Configuration Backend

## Créer le fichier .env

Créez un fichier `.env` dans le dossier racine avec le contenu suivant :

```env
APP_NAME="MyApp"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

LOG_CHANNEL=stack
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=myapp_db
DB_USERNAME=root
DB_PASSWORD=

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120
```

## Commandes de Configuration

```bash
# 1. Créer le fichier .env (si pas déjà créé)
# Copiez le contenu ci-dessus dans .env

# 2. Générer la clé d'application
php artisan key:generate

# 3. Créer la base de données
# Exécutez dans MySQL :
# CREATE DATABASE myapp_db;

# 4. Exécuter les migrations
php artisan migrate

# 5. Démarrer le serveur
php artisan serve
```

## Vérification

- ✅ Backend API : http://localhost:8000/api
