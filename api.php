<?php
/**
 * Point d'entrée de l'API - Recherche d'entreprises Yelp
 * 
 * Endpoints:
 * GET /api.php?action=search&term=restaurant&location=Paris&limit=10&offset=0
 * GET /api.php?action=businesses&limit=10&offset=0
 */

// Chargement de la configuration
require_once 'config.php';

// Headers CORS
header('Content-Type: application/json; charset=utf-8');

// Gestion CORS
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, ALLOWED_ORIGINS)) {
    header("Access-Control-Allow-Origin: $origin");
}
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Répondre aux requêtes OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'config.php';
require_once 'Database.php';
require_once 'YelpAPI.php';

/**
 * Fonction pour envoyer une réponse JSON
 */
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

/**
 * Fonction pour envoyer une erreur
 */
function sendError($message, $statusCode = 400) {
    sendResponse([
        'error' => true,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ], $statusCode);
}

try {
    // Vérifier la méthode HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        sendError('Méthode non autorisée', 405);
    }
    
    // Récupérer l'action
    $action = $_GET['action'] ?? '';
    
    // Initialiser la base de données
    $database = new Database();
    
    switch ($action) {
        case 'search':
            handleSearchAction($database);
            break;
            
        case 'businesses':
            handleBusinessesAction($database);
            break;
            
        case 'check-duplicates':
            handleCheckDuplicatesAction($database);
            break;
            
        case 'clean-duplicates':
            handleCleanDuplicatesAction($database);
            break;
            
        default:
            sendError('Action non spécifiée. Actions disponibles: search, businesses, check-duplicates, clean-duplicates');
    }
    
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    sendError($e->getMessage(), 500);
}

/**
 * Gérer l'action de recherche via Yelp API
 */
function handleSearchAction($database) {
    // Valider les paramètres requis
    $term = trim($_GET['term'] ?? '');
    $location = trim($_GET['location'] ?? '');
    
    if (empty($term)) {
        sendError('Le paramètre "term" est requis');
    }
    
    if (empty($location)) {
        sendError('Le paramètre "location" est requis');
    }
    
    // Paramètres optionnels
    $limit = min((int)($_GET['limit'] ?? DEFAULT_LIMIT), MAX_LIMIT);
    $offset = max(0, (int)($_GET['offset'] ?? 0));
    
    try {
        // Rechercher via l'API Yelp
        $yelpAPI = new YelpAPI();
        $searchResults = $yelpAPI->searchBusinesses($term, $location, $limit, $offset);
        
        // Sauvegarder en base de données
        $savedCount = 0;
        foreach ($searchResults['businesses'] as $business) {
            try {
                $database->upsertBusiness($business);
                $savedCount++;
            } catch (Exception $e) {
                error_log("Failed to save business {$business['name']}: " . $e->getMessage());
                // Continuer même si une sauvegarde échoue
            }
        }
        
        // Préparer la réponse
        $response = [
            'success' => true,
            'data' => $searchResults['businesses'],
            'pagination' => [
                'total' => $searchResults['total'],
                'limit' => $limit,
                'offset' => $offset,
                'has_next' => ($offset + $limit) < $searchResults['total']
            ],
            'saved_to_database' => $savedCount,
            'search_params' => [
                'term' => $term,
                'location' => $location
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        sendResponse($response);
        
    } catch (Exception $e) {
        sendError("Erreur lors de la recherche: " . $e->getMessage());
    }
}

/**
 * Gérer l'action de récupération des entreprises depuis la base
 */
function handleBusinessesAction($database) {
    $limit = min((int)($_GET['limit'] ?? DEFAULT_LIMIT), MAX_LIMIT);
    $offset = max(0, (int)($_GET['offset'] ?? 0));
    $searchTerm = trim($_GET['search'] ?? '');
    
    try {
        $businesses = $database->getBusinesses($limit, $offset, $searchTerm);
        $total = $database->countBusinesses($searchTerm);
        
        $response = [
            'success' => true,
            'data' => $businesses,
            'pagination' => [
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
                'has_next' => ($offset + $limit) < $total
            ],
            'search_term' => $searchTerm,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        sendResponse($response);
        
    } catch (Exception $e) {
        sendError("Erreur lors de la récupération: " . $e->getMessage());
    }
}

/**
 * Gérer la vérification des doublons
 */
function handleCheckDuplicatesAction($database) {
    try {
        $duplicateCheck = $database->checkForDuplicates();
        
        $response = [
            'success' => true,
            'duplicate_check' => $duplicateCheck,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        sendResponse($response);
        
    } catch (Exception $e) {
        sendError("Erreur lors de la vérification des doublons: " . $e->getMessage());
    }
}

/**
 * Gérer le nettoyage des doublons
 */
function handleCleanDuplicatesAction($database) {
    try {
        $deletedCount = $database->cleanDuplicates();
        
        $response = [
            'success' => true,
            'deleted_duplicates' => $deletedCount,
            'message' => $deletedCount > 0 
                ? "Supprimé $deletedCount doublon(s)" 
                : "Aucun doublon trouvé",
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        sendResponse($response);
        
    } catch (Exception $e) {
        sendError("Erreur lors du nettoyage des doublons: " . $e->getMessage());
    }
}
?>