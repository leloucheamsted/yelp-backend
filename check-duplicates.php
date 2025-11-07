<?php
/**
 * Script de vérification et nettoyage des doublons
 * Usage: php check-duplicates.php
 */

require_once 'config.php';
require_once 'Database.php';

try {
    $database = new Database();
    $connection = $database->getConnection();
    
    echo "=== VÉRIFICATION DES DOUBLONS ===\n\n";
    
    // 1. Vérifier les doublons par yelp_id
    echo "1. Vérification des doublons par yelp_id...\n";
    $stmt = $connection->prepare("
        SELECT yelp_id, COUNT(*) as count 
        FROM businesses 
        GROUP BY yelp_id 
        HAVING COUNT(*) > 1
    ");
    $stmt->execute();
    $duplicates = $stmt->fetchAll();
    
    if (empty($duplicates)) {
        echo "✅ Aucun doublon trouvé par yelp_id\n";
    } else {
        echo "⚠️ Doublons trouvés:\n";
        foreach ($duplicates as $duplicate) {
            echo "   - yelp_id: {$duplicate['yelp_id']} ({$duplicate['count']} entrées)\n";
        }
    }
    
    // 2. Vérifier les doublons par nom + adresse
    echo "\n2. Vérification des doublons par nom + adresse...\n";
    $stmt = $connection->prepare("
        SELECT name, address, COUNT(*) as count 
        FROM businesses 
        GROUP BY name, address 
        HAVING COUNT(*) > 1
    ");
    $stmt->execute();
    $nameDuplicates = $stmt->fetchAll();
    
    if (empty($nameDuplicates)) {
        echo "✅ Aucun doublon trouvé par nom + adresse\n";
    } else {
        echo "⚠️ Doublons potentiels trouvés:\n";
        foreach ($nameDuplicates as $duplicate) {
            echo "   - {$duplicate['name']} à {$duplicate['address']} ({$duplicate['count']} entrées)\n";
        }
    }
    
    // 3. Statistiques générales
    echo "\n=== STATISTIQUES ===\n";
    $stmt = $connection->prepare("SELECT COUNT(*) as total FROM businesses");
    $stmt->execute();
    $total = $stmt->fetch()['total'];
    echo "Total d'entreprises: $total\n";
    
    $stmt = $connection->prepare("SELECT COUNT(DISTINCT yelp_id) as unique_yelp_ids FROM businesses");
    $stmt->execute();
    $uniqueYelpIds = $stmt->fetch()['unique_yelp_ids'];
    echo "IDs Yelp uniques: $uniqueYelpIds\n";
    
    if ($total === $uniqueYelpIds) {
        echo "✅ Tous les enregistrements ont des yelp_id uniques\n";
    } else {
        echo "⚠️ Différence détectée entre le total et les IDs uniques\n";
    }
    
    // 4. Nettoyer les doublons si nécessaire
    if (!empty($duplicates)) {
        echo "\n=== NETTOYAGE DES DOUBLONS ===\n";
        echo "Voulez-vous supprimer les doublons ? (y/N): ";
        $handle = fopen("php://stdin", "r");
        $response = trim(fgets($handle));
        fclose($handle);
        
        if (strtolower($response) === 'y') {
            foreach ($duplicates as $duplicate) {
                // Garder seulement l'entrée la plus récente
                $stmt = $connection->prepare("
                    DELETE FROM businesses 
                    WHERE yelp_id = ? 
                    AND id NOT IN (
                        SELECT * FROM (
                            SELECT MAX(id) 
                            FROM businesses 
                            WHERE yelp_id = ?
                        ) AS temp
                    )
                ");
                $stmt->execute([$duplicate['yelp_id'], $duplicate['yelp_id']]);
                $deleted = $stmt->rowCount();
                echo "✅ Supprimé $deleted doublon(s) pour yelp_id: {$duplicate['yelp_id']}\n";
            }
            echo "Nettoyage terminé!\n";
        } else {
            echo "Nettoyage annulé.\n";
        }
    }
    
    echo "\n=== VÉRIFICATION TERMINÉE ===\n";
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    exit(1);
}
?>