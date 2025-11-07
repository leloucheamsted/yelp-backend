<?php
/**
 * Script de test de la configuration
 * Vérifie que toutes les variables d'environnement sont correctement chargées
 */

echo "=== Test de Configuration Backend ===\n\n";

try {
    // Charger la configuration
    require_once 'config.php';
    
    echo "Configuration chargée avec succès\n\n";
    
    // Vérifier la base de données
    echo "📊 Configuration Base de Données :\n";
    echo "   Host: " . DB_HOST . "\n";
    echo "   Database: " . DB_NAME . "\n";
    echo "   User: " . DB_USER . "\n";
    echo "   Password: " . (empty(DB_PASS) ? "❌ NON DÉFINI" : "✅ DÉFINI") . "\n\n";
    
    // Tester la connexion à la base de données
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        echo "✅ Connexion à la base de données réussie\n\n";
    } catch (PDOException $e) {
        echo "❌ Erreur de connexion à la base de données: " . $e->getMessage() . "\n\n";
    }
    
    // Vérifier l'API Yelp
    echo "🔑 Configuration API Yelp :\n";
    echo "   API Key: " . (empty(YELP_API_KEY) ? "❌ NON DÉFINIE" : "✅ DÉFINIE (longueur: " . strlen(YELP_API_KEY) . ")") . "\n";
    echo "   API URL: " . YELP_API_URL . "\n\n";
    
    // Vérifier CORS
    echo "🌐 Configuration CORS :\n";
    foreach (ALLOWED_ORIGINS as $origin) {
        echo "   - " . $origin . "\n";
    }
    echo "\n";
    
    // Vérifier la pagination
    echo "📄 Configuration Pagination :\n";
    echo "   Limite par défaut: " . DEFAULT_LIMIT . "\n";
    echo "   Limite maximum: " . MAX_LIMIT . "\n\n";
    
    // Vérifier l'environnement
    echo "⚙️  Configuration Environnement :\n";
    echo "   Environnement: " . APP_ENV . "\n";
    echo "   Debug: " . (APP_DEBUG ? "✅ ACTIVÉ" : "❌ DÉSACTIVÉ") . "\n\n";
    
    // Test de l'API Yelp (si la clé est définie)
    if (!empty(YELP_API_KEY)) {
        echo "🧪 Test de l'API Yelp...\n";
        
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => YELP_API_URL . "?term=test&location=Paris&limit=1",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer " . YELP_API_KEY,
            ],
        ]);
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        if ($httpCode === 200) {
            echo "✅ API Yelp accessible\n";
        } else {
            echo "❌ Erreur API Yelp (Code HTTP: $httpCode)\n";
        }
    }
    
    echo "\n=== Test terminé ===\n";
    
} catch (Exception $e) {
    echo "❌ Erreur fatale: " . $e->getMessage() . "\n";
    exit(1);
}
?>