<?php
/**
 * Configuration de l'application
 * 
 * IMPORTANT: Les paramètres sont maintenant chargés depuis le fichier .env
 */

// Charger les variables d'environnement
require_once __DIR__ . '/EnvLoader.php';

try {
    EnvLoader::load();
} catch (Exception $e) {
    die("Erreur de chargement de la configuration : " . $e->getMessage());
}

// Configuration de la base de données
define('DB_HOST', EnvLoader::get('DB_HOST', 'localhost'));
define('DB_NAME', EnvLoader::get('DB_NAME', 'yelp_business_search'));
define('DB_USER', EnvLoader::get('DB_USER', 'root'));
define('DB_PASS', EnvLoader::get('DB_PASS', ''));

// Configuration Yelp API
define('YELP_API_KEY', EnvLoader::get('YELP_API_KEY', ''));
define('YELP_API_URL', EnvLoader::get('YELP_API_URL', 'https://api.yelp.com/v3/businesses/search'));

// Vérifier que la clé API Yelp est définie
if (empty(YELP_API_KEY)) {
    die("Erreur : YELP_API_KEY non définie dans le fichier .env");
}

// Configuration CORS pour React
define('ALLOWED_ORIGINS', EnvLoader::getArray('ALLOWED_ORIGINS', [
    'http://localhost:3000', 
    'http://localhost:3001',
    'http://127.0.0.1:3000',
    'http://127.0.0.1:3001'
]));

// Configuration pagination
define('DEFAULT_LIMIT', EnvLoader::getInt('DEFAULT_LIMIT', 10));
define('MAX_LIMIT', EnvLoader::getInt('MAX_LIMIT', 50));

// Configuration de l'environnement
define('APP_ENV', EnvLoader::get('APP_ENV', 'development'));
define('APP_DEBUG', EnvLoader::getBool('APP_DEBUG', true));

// Fuseau horaire
date_default_timezone_set('Europe/Paris');

// Affichage des erreurs selon l'environnement
if (APP_DEBUG && APP_ENV === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}

// Log de démarrage
if (APP_DEBUG) {
    error_log("Configuration chargée : Environnement " . APP_ENV);
    error_log("Base de données : " . DB_HOST . "/" . DB_NAME);
    error_log("CORS autorisés : " . implode(', ', ALLOWED_ORIGINS));
}
?>