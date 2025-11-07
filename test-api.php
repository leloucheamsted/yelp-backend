<?php
/**
 * Script de test pour l'API backend
 * Teste les différents endpoints et la connectivité
 */

// Configuration
$API_BASE = 'http://localhost:8000';

// Couleurs pour la console
function colorText($text, $color) {
    $colors = [
        'green' => "\033[32m",
        'red' => "\033[31m",
        'yellow' => "\033[33m",
        'blue' => "\033[34m",
        'reset' => "\033[0m"
    ];
    return $colors[$color] . $text . $colors['reset'];
}

function testEndpoint($url, $description) {
    echo "\n" . colorText("🧪 Test: $description", 'blue') . "\n";
    echo "URL: $url\n";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HEADER => true,
        CURLOPT_NOBODY => false
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo colorText("❌ ERREUR: $error", 'red') . "\n";
        return false;
    }
    
    if ($httpCode === 200) {
        echo colorText("✅ SUCCESS: HTTP $httpCode", 'green') . "\n";
        
        // Extraire le body
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $body = substr($response, $headerSize ?? strpos($response, "\r\n\r\n") + 4);
        
        // Essayer de décoder le JSON
        $data = json_decode($body, true);
        if ($data) {
            if (isset($data['success']) && $data['success']) {
                echo colorText("✅ API Response: SUCCESS", 'green') . "\n";
            } elseif (isset($data['error'])) {
                echo colorText("⚠️ API Error: " . $data['message'], 'yellow') . "\n";
            }
        }
        return true;
    } else {
        echo colorText("❌ FAILED: HTTP $httpCode", 'red') . "\n";
        return false;
    }
}

function checkRequirements() {
    echo colorText("🔍 Vérification des prérequis...", 'blue') . "\n";
    
    // Vérifier PHP
    echo "PHP Version: " . PHP_VERSION . "\n";
    
    // Vérifier les extensions
    $extensions = ['curl', 'pdo', 'pdo_mysql', 'json'];
    foreach ($extensions as $ext) {
        if (extension_loaded($ext)) {
            echo colorText("✅ Extension $ext: OK", 'green') . "\n";
        } else {
            echo colorText("❌ Extension $ext: MANQUANTE", 'red') . "\n";
        }
    }
    
    // Vérifier la configuration
    include_once 'config.php';
    
    if (YELP_API_KEY === 'YOUR_YELP_API_KEY_HERE') {
        echo colorText("❌ ERREUR: Clé API Yelp non configurée!", 'red') . "\n";
        echo colorText("👉 Éditez backend/config.php", 'yellow') . "\n";
        return false;
    } else {
        echo colorText("✅ Clé API Yelp: Configurée", 'green') . "\n";
    }
    
    return true;
}

// Main
echo colorText("🚀 Tests de l'API Backend - Yelp Business Search", 'blue') . "\n";
echo str_repeat("=", 60) . "\n";

// Vérifier les prérequis
if (!checkRequirements()) {
    echo colorText("❌ Échec des prérequis - Arrêt des tests", 'red') . "\n";
    exit(1);
}

echo "\n" . str_repeat("-", 60) . "\n";

// Test des endpoints
$tests = [
    // Test endpoint sans paramètres
    ["$API_BASE/api.php", "Endpoint principal sans paramètres"],
    
    // Test endpoint businesses (base de données)
    ["$API_BASE/api.php?action=businesses&limit=5", "Récupération entreprises (base de données)"],
    
    // Test endpoint search (nécessite Yelp API)
    ["$API_BASE/api.php?action=search&term=restaurant&location=Paris&limit=5", "Recherche via API Yelp"],
];

$passed = 0;
$total = count($tests);

foreach ($tests as $test) {
    if (testEndpoint($test[0], $test[1])) {
        $passed++;
    }
    echo str_repeat("-", 40) . "\n";
}

// Résumé
echo "\n" . colorText("📊 RÉSUMÉ DES TESTS", 'blue') . "\n";
echo str_repeat("=", 60) . "\n";
echo "Tests réussis: $passed/$total\n";

if ($passed === $total) {
    echo colorText("🎉 TOUS LES TESTS SONT PASSÉS !", 'green') . "\n";
    echo colorText("✅ L'API est prête à être utilisée", 'green') . "\n";
} else {
    echo colorText("⚠️ Certains tests ont échoué", 'yellow') . "\n";
    echo colorText("👉 Vérifiez la configuration et la connectivité", 'yellow') . "\n";
}

echo "\n" . colorText("💡 Pour démarrer l'API:", 'blue') . "\n";
echo "cd backend && php -S localhost:8000\n";
echo "\n" . colorText("💡 Pour démarrer le frontend:", 'blue') . "\n";
echo "cd frontend && npm start\n";
?>