# Backend PHP - API Yelp Fusion

## Structure des fichiers

- `config.php` - Configuration de l'application
- `Database.php` - Classe de gestion MySQL
- `YelpAPI.php` - Classe d'intégration Yelp Fusion
- `api.php` - Point d'entrée de l'API REST

## Configuration requise

1. **Clé API Yelp** : 
   - Créer un compte sur https://www.yelp.com/developers
   - Obtenir une clé API
   - Modifier `YELP_API_KEY` dans `config.php`

2. **Base de données MySQL** :
   - Modifier les paramètres dans `config.php`
   - Exécuter le script `../database/create_tables.sql`

## Endpoints disponibles

### Recherche via Yelp API
```
GET /api.php?action=search&term=restaurant&location=Paris&limit=10&offset=0
```

**Paramètres** :
- `term` (requis) : Mot-clé de recherche
- `location` (requis) : Localisation
- `limit` (optionnel) : Nombre de résultats (défaut: 10, max: 50)
- `offset` (optionnel) : Décalage pour pagination (défaut: 0)

**Réponse** :
```json
{
  "success": true,
  "data": [...],
  "pagination": {
    "total": 150,
    "limit": 10,
    "offset": 0,
    "has_next": true
  },
  "saved_to_database": 10
}
```

### Récupération depuis la base de données
```
GET /api.php?action=businesses&limit=10&offset=0&search=restaurant
```

**Paramètres** :
- `limit` (optionnel) : Nombre de résultats
- `offset` (optionnel) : Décalage pour pagination
- `search` (optionnel) : Recherche dans les entreprises sauvegardées

## Installation et déploiement

### Serveur de développement PHP
```bash
cd backend
php -S localhost:8000
```

L'API sera accessible sur `http://localhost:8000/api.php`

### Apache/Nginx
Configurez votre serveur web pour pointer vers le dossier `backend/`

## Gestion des erreurs

L'API retourne des erreurs au format JSON :
```json
{
  "error": true,
  "message": "Description de l'erreur",
  "timestamp": "2025-11-07 14:30:00"
}
```

## Sécurité

- Validation des paramètres d'entrée
- Protection contre l'injection SQL (requêtes préparées)
- Gestion CORS pour le frontend React
- Limitation des requêtes (rate limiting recommandé en production)