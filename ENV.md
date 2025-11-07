# Configuration Backend - Variables d'Environnement

## Installation

1. Copiez le fichier exemple :
   ```bash
   cp .env.example .env
   ```

2. Modifiez les valeurs selon votre configuration :
   ```bash
   nano .env
   ```

## Variables Disponibles

### Base de Données
- `DB_HOST` - Hôte de la base de données (défaut: localhost)
- `DB_NAME` - Nom de la base de données (défaut: yelp_business_search)
- `DB_USER` - Utilisateur de la base de données (défaut: root)
- `DB_PASS` - Mot de passe de la base de données

### API Yelp
- `YELP_API_KEY` - **OBLIGATOIRE** Clé API Yelp Fusion
- `YELP_API_URL` - URL de l'API Yelp (défaut: https://api.yelp.com/v3/businesses/search)

### CORS
- `ALLOWED_ORIGINS` - URLs autorisées pour CORS (séparées par des virgules)

### Pagination
- `DEFAULT_LIMIT` - Nombre d'éléments par défaut (défaut: 10)
- `MAX_LIMIT` - Nombre maximum d'éléments (défaut: 50)

### Environnement
- `APP_ENV` - Environnement (development, production, test)
- `APP_DEBUG` - Mode debug (true/false)

## Sécurité

- Le fichier `.env` est ignoré par Git
- Ne commitez jamais vos clés API
- Utilisez des mots de passe forts pour la base de données
- Désactivez le debug en production

## Obtenir une clé API Yelp

1. Créez un compte sur [Yelp Developers](https://www.yelp.com/developers)
2. Créez une nouvelle application
3. Copiez votre API Key dans le fichier `.env`

## Test de Configuration

Exécutez ce script pour vérifier votre configuration :

```bash
php -r "
require_once 'config.php';
echo 'Configuration OK!' . PHP_EOL;
echo 'DB: ' . DB_HOST . '/' . DB_NAME . PHP_EOL;
echo 'API Key: ' . (empty(YELP_API_KEY) ? 'NON DÉFINIE' : 'DÉFINIE') . PHP_EOL;
"
```