<?php
/**
 * Classe de gestion de base de données SQLite
 */
class DatabaseSQLite {
    private $pdo;
    private $dbPath;

    public function __construct($dbPath = null) {
        $this->dbPath = $dbPath ?? DB_PATH;
        $this->connect();
        $this->createTables();
    }

    /**
     * Connexion à la base SQLite
     */
    private function connect() {
        try {
            // Créer le répertoire si nécessaire
            $dir = dirname($this->dbPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $this->pdo = new PDO("sqlite:" . $this->dbPath);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            // Activer les clés étrangères
            $this->pdo->exec("PRAGMA foreign_keys = ON");
            
        } catch (PDOException $e) {
            throw new Exception("Erreur de connexion SQLite: " . $e->getMessage());
        }
    }

    /**
     * Créer les tables nécessaires
     */
    private function createTables() {
        $sql = "
        CREATE TABLE IF NOT EXISTS businesses (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            yelp_id TEXT UNIQUE NOT NULL,
            name TEXT NOT NULL,
            address TEXT,
            phone TEXT,
            website TEXT,
            image_url TEXT,
            rating REAL DEFAULT 0,
            review_count INTEGER DEFAULT 0,
            coordinates_lat REAL,
            coordinates_lng REAL,
            categories TEXT, -- JSON string
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE INDEX IF NOT EXISTS idx_yelp_id ON businesses(yelp_id);
        CREATE INDEX IF NOT EXISTS idx_name ON businesses(name);
        CREATE INDEX IF NOT EXISTS idx_rating ON businesses(rating);
        ";

        try {
            $this->pdo->exec($sql);
        } catch (PDOException $e) {
            throw new Exception("Erreur création tables: " . $e->getMessage());
        }
    }

    /**
     * Sauvegarder des entreprises
     */
    public function saveBusinesses($businesses) {
        if (empty($businesses)) {
            return 0;
        }

        $saved = 0;
        $stmt = $this->pdo->prepare("
            INSERT OR REPLACE INTO businesses 
            (yelp_id, name, address, phone, website, image_url, rating, review_count, 
             coordinates_lat, coordinates_lng, categories, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'))
        ");

        foreach ($businesses as $business) {
            try {
                $stmt->execute([
                    $business['yelp_id'],
                    $business['name'],
                    $business['address'],
                    $business['phone'],
                    $business['website'],
                    $business['image_url'],
                    $business['rating'],
                    $business['review_count'],
                    $business['coordinates']['latitude'],
                    $business['coordinates']['longitude'],
                    json_encode($business['categories'])
                ]);
                $saved++;
            } catch (PDOException $e) {
                error_log("Erreur sauvegarde business: " . $e->getMessage());
            }
        }

        return $saved;
    }

    /**
     * Récupérer des entreprises
     */
    public function getBusinesses($limit = 10, $offset = 0, $search = '') {
        $sql = "SELECT * FROM businesses";
        $params = [];

        if (!empty($search)) {
            $sql .= " WHERE name LIKE ? OR address LIKE ?";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $sql .= " ORDER BY rating DESC, review_count DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        $businesses = $stmt->fetchAll();
        
        // Décoder les catégories JSON
        foreach ($businesses as &$business) {
            $business['categories'] = json_decode($business['categories'], true) ?: [];
            $business['coordinates'] = [
                'latitude' => $business['coordinates_lat'],
                'longitude' => $business['coordinates_lng']
            ];
            unset($business['coordinates_lat'], $business['coordinates_lng']);
        }

        return $businesses;
    }

    /**
     * Compter le total d'entreprises
     */
    public function countBusinesses($search = '') {
        $sql = "SELECT COUNT(*) as total FROM businesses";
        $params = [];

        if (!empty($search)) {
            $sql .= " WHERE name LIKE ? OR address LIKE ?";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetch()['total'];
    }

    /**
     * Obtenir la connexion PDO
     */
    public function getPdo() {
        return $this->pdo;
    }
}
?>