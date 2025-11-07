<?php
/**
 * Point d'entrée de l'API - Version simplifiée pour tests
 * 
 * Endpoints:
 * GET /api-simple.php?action=search&term=restaurant&location=Paris&limit=10&offset=0
 */

// Chargement de la configuration
require_once 'config.php';
require_once 'YelpAPI.php';

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
    
    if ($action === 'search') {
        // Validation des paramètres requis
        $term = $_GET['term'] ?? '';
        $location = $_GET['location'] ?? '';
        
        if (empty($term)) {
            sendError('Le paramètre "term" est requis');
        }
        
        if (empty($location)) {
            sendError('Le paramètre "location" est requis');
        }
        
        // Paramètres optionnels
        $limit = min((int)($_GET['limit'] ?? DEFAULT_LIMIT), MAX_LIMIT);
        $offset = max(0, (int)($_GET['offset'] ?? 0));
        
        // Recherche via l'API Yelp
        $yelp = new YelpAPI();
        $results = $yelp->searchBusinesses($term, $location, $limit, $offset);
        
        if (!$results) {
            sendError('Erreur lors de la recherche', 500);
        }
        
        // Formater les résultats pour le frontend
        $businesses = [];
        foreach ($results['businesses'] as $business) {
            $formattedBusiness = [
                'yelp_id' => $business['id'],
                'name' => $business['name'],
                'address' => implode(', ', $business['location']['display_address']),
                'phone' => $business['phone'] ?? '',
                'website' => $business['url'] ?? '',
                'image_url' => $business['image_url'] ?? '',
                'rating' => $business['rating'] ?? 0,
                'review_count' => $business['review_count'] ?? 0,
                'categories' => $business['categories'] ?? [],
                'coordinates' => [
                    'latitude' => $business['coordinates']['latitude'] ?? null,
                    'longitude' => $business['coordinates']['longitude'] ?? null
                ]
            ];
            $businesses[] = $formattedBusiness;
        }
        
        // Réponse
        sendResponse([
            'success' => true,
            'data' => $businesses,
            'pagination' => [
                'total' => $results['total'] ?? count($businesses),
                'limit' => $limit,
                'offset' => $offset,
                'has_next' => ($offset + $limit) < ($results['total'] ?? count($businesses))
            ],
            'search_params' => [
                'term' => $term,
                'location' => $location,
                'limit' => $limit,
                'offset' => $offset
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
    } else {
        sendError('Action non supportée. Utilisez action=search');
    }
    
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    sendError('Erreur interne du serveur', 500);
}
?>