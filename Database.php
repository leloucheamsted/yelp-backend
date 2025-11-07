<?php
/**
 * Classe de gestion de la base de données
 */

class Database {
    private $connection;
    
    public function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Erreur de connexion à la base de données");
        }
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Insérer ou mettre à jour une entreprise
     */
    public function upsertBusiness($business) {
        try {
            // Vérifier que yelp_id n'est pas vide
            if (empty($business['yelp_id'])) {
                throw new Exception("yelp_id ne peut pas être vide");
            }
            
            $sql = "INSERT INTO businesses (yelp_id, name, address, phone, website, image_url, rating, review_count) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?) 
                    ON DUPLICATE KEY UPDATE 
                    name = VALUES(name),
                    address = VALUES(address),
                    phone = VALUES(phone),
                    website = VALUES(website),
                    image_url = VALUES(image_url),
                    rating = VALUES(rating),
                    review_count = VALUES(review_count),
                    updated_at = CURRENT_TIMESTAMP";
            
            $stmt = $this->connection->prepare($sql);
            
            $result = $stmt->execute([
                $business['yelp_id'],
                $business['name'] ?? '',
                $business['address'] ?? '',
                $business['phone'] ?? '',
                $business['website'] ?? '',
                $business['image_url'] ?? '',
                $business['rating'] ?? 0,
                $business['review_count'] ?? 0
            ]);
            
            // Retourner des informations sur l'opération
            return [
                'success' => $result,
                'inserted' => $this->connection->lastInsertId() > 0,
                'affected_rows' => $stmt->rowCount()
            ];
            
        } catch (PDOException $e) {
            // Log détaillé pour le debugging
            error_log("Database upsert failed for yelp_id: " . ($business['yelp_id'] ?? 'unknown') . " - " . $e->getMessage());
            
            // Vérifier si c'est une erreur de contrainte d'unicité
            if ($e->getCode() == 23000) {
                throw new Exception("Entreprise déjà existante avec cet ID Yelp");
            }
            
            throw new Exception("Erreur lors de l'enregistrement en base de données: " . $e->getMessage());
        }
    }
    
    /**
     * Récupérer les entreprises depuis la base
     */
    public function getBusinesses($limit = 10, $offset = 0, $searchTerm = null) {
        try {
            $sql = "SELECT * FROM businesses";
            $params = [];
            
            if ($searchTerm) {
                $sql .= " WHERE name LIKE ? OR address LIKE ?";
                $params = ["%$searchTerm%", "%$searchTerm%"];
            }
            
            $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Database select failed: " . $e->getMessage());
            throw new Exception("Erreur lors de la récupération des données");
        }
    }
    
    /**
     * Compter le total d'entreprises
     */
    public function countBusinesses($searchTerm = null) {
        try {
            $sql = "SELECT COUNT(*) as total FROM businesses";
            $params = [];
            
            if ($searchTerm) {
                $sql .= " WHERE name LIKE ? OR address LIKE ?";
                $params = ["%$searchTerm%", "%$searchTerm%"];
            }
            
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            
            $result = $stmt->fetch();
            return $result['total'];
        } catch (PDOException $e) {
            error_log("Database count failed: " . $e->getMessage());
            throw new Exception("Erreur lors du comptage des données");
        }
    }
    
    /**
     * Vérifier s'il existe des doublons dans la base
     */
    public function checkForDuplicates() {
        try {
            // Vérifier les doublons par yelp_id
            $stmt = $this->connection->prepare("
                SELECT yelp_id, COUNT(*) as count 
                FROM businesses 
                GROUP BY yelp_id 
                HAVING COUNT(*) > 1
            ");
            $stmt->execute();
            $yelpIdDuplicates = $stmt->fetchAll();
            
            // Vérifier les doublons par nom + adresse
            $stmt = $this->connection->prepare("
                SELECT name, address, COUNT(*) as count 
                FROM businesses 
                GROUP BY name, address 
                HAVING COUNT(*) > 1
            ");
            $stmt->execute();
            $nameDuplicates = $stmt->fetchAll();
            
            // Statistiques générales
            $stmt = $this->connection->prepare("SELECT COUNT(*) as total FROM businesses");
            $stmt->execute();
            $total = $stmt->fetch()['total'];
            
            $stmt = $this->connection->prepare("SELECT COUNT(DISTINCT yelp_id) as unique_ids FROM businesses");
            $stmt->execute();
            $uniqueIds = $stmt->fetch()['unique_ids'];
            
            return [
                'yelp_id_duplicates' => $yelpIdDuplicates,
                'name_address_duplicates' => $nameDuplicates,
                'total_businesses' => $total,
                'unique_yelp_ids' => $uniqueIds,
                'has_duplicates' => !empty($yelpIdDuplicates),
                'integrity_check' => $total === $uniqueIds
            ];
            
        } catch (PDOException $e) {
            error_log("Database duplicate check failed: " . $e->getMessage());
            throw new Exception("Erreur lors de la vérification des doublons");
        }
    }
    
    /**
     * Nettoyer les doublons (garder le plus récent)
     */
    public function cleanDuplicates() {
        try {
            $this->connection->beginTransaction();
            
            // Supprimer les doublons en gardant l'ID le plus élevé (le plus récent)
            $sql = "DELETE b1 FROM businesses b1
                    INNER JOIN businesses b2 
                    WHERE b1.yelp_id = b2.yelp_id 
                    AND b1.id < b2.id";
            
            $stmt = $this->connection->prepare($sql);
            $stmt->execute();
            $deletedCount = $stmt->rowCount();
            
            $this->connection->commit();
            
            return $deletedCount;
            
        } catch (PDOException $e) {
            $this->connection->rollBack();
            error_log("Database clean duplicates failed: " . $e->getMessage());
            throw new Exception("Erreur lors du nettoyage des doublons");
        }
    }
}
?>