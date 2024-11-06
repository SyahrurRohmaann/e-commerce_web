<?php
class config extends PDO {
    public function __construct() {
        try {
            error_log("Initializing database connection");
            parent::__construct(
                "mysql:host=localhost;dbname=toko_online",
                "root",
                ""
            );
            $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            error_log("Database connection successful");
        } catch(PDOException $e) {
            error_log("Connection failed: " . $e->getMessage());
            throw $e;
        }
    }
}
?>
