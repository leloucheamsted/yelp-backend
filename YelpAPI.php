<?php
/**
 * Classe pour gérer les appels à l'API Yelp Fusion
 */

class YelpAPI {
    private $apiKey;
    private $baseUrl;
    
    public function __construct() {
        $this->apiKey = YELP_API_KEY;
        $this->baseUrl = YELP_API_URL;
        
        if (empty($this->apiKey) || $this->apiKey === 'YOUR_YELP_API_KEY_HERE') {
            throw new Exception("Clé API Yelp non configurée. Veuillez mettre à jour config.php");
        }
    }
    
    /**
     * Rechercher des entreprises via l'API Yelp
     */
    public function searchBusinesses($term, $location, $limit = 10, $offset = 0) {
        try {
            $url = $this->baseUrl . '?' . http_build_query([
                'term' => $term,
                'location' => $location,
                'limit' => min($limit, MAX_LIMIT),
                'offset' => $offset,
                'sort_by' => 'best_match'
            ]);
            
            $headers = [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json'
            ];
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_USERAGENT => 'YelpBusinessSearch/1.0'
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                throw new Exception("Erreur cURL: " . $error);
            }
            
            if ($httpCode !== 200) {
                $errorData = json_decode($response, true);
                $errorMessage = isset($errorData['error']['description']) 
                    ? $errorData['error']['description'] 
                    : "Erreur API Yelp (Code: $httpCode)";
                throw new Exception($errorMessage);
            }
            
            $data = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Erreur de décodage JSON: " . json_last_error_msg());
            }
            
            return $this->formatBusinesses($data);
            
        } catch (Exception $e) {
            error_log("Yelp API Error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Formater les données des entreprises pour notre application
     */
    private function formatBusinesses($data) {
        $formatted = [
            'businesses' => [],
            'total' => $data['total'] ?? 0
        ];
        
        if (!isset($data['businesses']) || !is_array($data['businesses'])) {
            return $formatted;
        }
        
        foreach ($data['businesses'] as $business) {
            $formatted['businesses'][] = [
                'yelp_id' => $business['id'] ?? '',
                'name' => $business['name'] ?? '',
                'address' => $this->formatAddress($business['location'] ?? []),
                'phone' => $business['display_phone'] ?? '',
                'website' => $business['url'] ?? '',
                'image_url' => $business['image_url'] ?? '',
                'rating' => $business['rating'] ?? 0,
                'review_count' => $business['review_count'] ?? 0,
                'categories' => $this->formatCategories($business['categories'] ?? []),
                'coordinates' => [
                    'latitude' => $business['coordinates']['latitude'] ?? null,
                    'longitude' => $business['coordinates']['longitude'] ?? null
                ]
            ];
        }
        
        return $formatted;
    }
    
    /**
     * Formater l'adresse
     */
    private function formatAddress($location) {
        if (empty($location)) return '';
        
        $addressParts = [];
        
        if (!empty($location['address1'])) {
            $addressParts[] = $location['address1'];
        }
        if (!empty($location['address2'])) {
            $addressParts[] = $location['address2'];
        }
        if (!empty($location['city'])) {
            $addressParts[] = $location['city'];
        }
        if (!empty($location['zip_code'])) {
            $addressParts[] = $location['zip_code'];
        }
        if (!empty($location['country'])) {
            $addressParts[] = $location['country'];
        }
        
        return implode(', ', $addressParts);
    }
    
    /**
     * Formater les catégories
     */
    private function formatCategories($categories) {
        if (empty($categories) || !is_array($categories)) return [];
        
        return array_map(function($category) {
            return [
                'alias' => $category['alias'] ?? '',
                'title' => $category['title'] ?? ''
            ];
        }, $categories);
    }
}
?>