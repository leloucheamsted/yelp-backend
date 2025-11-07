<?php
/**
 * Gestionnaire des variables d'environnement
 * Charge et parse le fichier .env
 */
class EnvLoader {
    private static $loaded = false;
    private static $env = [];

    /**
     * Charger le fichier .env
     */
    public static function load($envFile = '.env') {
        if (self::$loaded) {
            return;
        }

        $envPath = __DIR__ . '/' . $envFile;
        
        if (!file_exists($envPath)) {
            throw new Exception("Fichier .env non trouvé : $envPath");
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Ignorer les commentaires
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // Parser les variables
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Supprimer les guillemets si présents
                if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                    (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                    $value = substr($value, 1, -1);
                }
                
                self::$env[$key] = $value;
                
                // Définir dans $_ENV si pas déjà défini
                if (!isset($_ENV[$key])) {
                    $_ENV[$key] = $value;
                }
            }
        }
        
        self::$loaded = true;
    }

    /**
     * Obtenir une variable d'environnement
     */
    public static function get($key, $default = null) {
        if (!self::$loaded) {
            self::load();
        }
        
        return self::$env[$key] ?? $_ENV[$key] ?? $default;
    }

    /**
     * Obtenir une variable comme booléen
     */
    public static function getBool($key, $default = false) {
        $value = self::get($key, $default);
        
        if (is_bool($value)) {
            return $value;
        }
        
        return in_array(strtolower($value), ['true', '1', 'yes', 'on']);
    }

    /**
     * Obtenir une variable comme entier
     */
    public static function getInt($key, $default = 0) {
        return (int) self::get($key, $default);
    }

    /**
     * Obtenir une variable comme tableau (séparée par des virgules)
     */
    public static function getArray($key, $default = []) {
        $value = self::get($key);
        
        if (empty($value)) {
            return $default;
        }
        
        return array_map('trim', explode(',', $value));
    }

    /**
     * Vérifier si une variable est définie
     */
    public static function has($key) {
        if (!self::$loaded) {
            self::load();
        }
        
        return isset(self::$env[$key]) || isset($_ENV[$key]);
    }

    /**
     * Obtenir toutes les variables chargées
     */
    public static function all() {
        if (!self::$loaded) {
            self::load();
        }
        
        return self::$env;
    }
}
?>