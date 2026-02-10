<?php
// FUTURE AUTOMOTIVE - Database Configuration
// إعدادات قاعدة البيانات للنظام الجديد

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'u442210176_Futur2');
define('DB_USER', 'u442210176_Futur2');
define('DB_PASS', '12Abdou12');
define('DB_CHARSET', 'utf8mb4');

// Application Constants
define('APP_NAME', 'Future Automotive');
define('APP_VERSION', '2.0.0');
define('DEBUG', true);

// Security
define('HASH_COST', 12);
define('SESSION_LIFETIME', 3600); // 1 hour

// Paths
define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_PATH', ROOT_PATH . '/uploads');
define('BACKUP_PATH', ROOT_PATH . '/backups');

// Database Connection Class
class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $charset = DB_CHARSET;
    private $conn;

    public function connect() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset={$this->charset}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            return $this->conn;
        } catch (PDOException $e) {
            // Log the actual error for debugging
            error_log("Database connection failed: " . $e->getMessage());
            
            // Return a user-friendly error
            throw new Exception("Database connection failed. Please check your database settings.");
        }
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    public function query($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Query failed: " . $e->getMessage());
            throw new Exception("Query execution failed.");
        }
    }
    
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    public function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        $stmt = $this->query($sql, array_values($data));
        return $this->conn->lastInsertId();
    }
    
    public function update($table, $data, $where, $where_params = []) {
        $set_parts = [];
        foreach ($data as $key => $value) {
            $set_parts[] = "$key = ?";
        }
        $sql = "UPDATE $table SET " . implode(', ', $set_parts) . " WHERE $where";
        $stmt = $this->query($sql, array_merge(array_values($data), $where_params));
        return $stmt->rowCount();
    }
    
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM $table WHERE $where";
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    public function createTables() {
        // Create customers table
        $this->conn->exec("
            CREATE TABLE IF NOT EXISTS customers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(100) UNIQUE,
                phone VARCHAR(20),
                address TEXT,
                city VARCHAR(50),
                country VARCHAR(50),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");
        
        // Create vehicles table
        $this->conn->exec("
            CREATE TABLE IF NOT EXISTS vehicles (
                id INT AUTO_INCREMENT PRIMARY KEY,
                customer_id INT NOT NULL,
                make VARCHAR(50) NOT NULL,
                model VARCHAR(50) NOT NULL,
                year INT,
                color VARCHAR(30),
                plate_number VARCHAR(20) UNIQUE,
                vin_number VARCHAR(17),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
            )
        ");
        
        // Create services table
        $this->conn->exec("
            CREATE TABLE IF NOT EXISTS services (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                description TEXT,
                price DECIMAL(10,2) NOT NULL,
                duration_minutes INT DEFAULT 60,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Create appointments table
        $this->conn->exec("
            CREATE TABLE IF NOT EXISTS appointments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                customer_id INT NOT NULL,
                vehicle_id INT NOT NULL,
                service_id INT NOT NULL,
                appointment_date DATE NOT NULL,
                appointment_time TIME NOT NULL,
                status ENUM('scheduled', 'completed', 'cancelled') DEFAULT 'scheduled',
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
                FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
                FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
            )
        ");
        
        // Create invoices table
        $this->conn->exec("
            CREATE TABLE IF NOT EXISTS invoices (
                id INT AUTO_INCREMENT PRIMARY KEY,
                customer_id INT NOT NULL,
                invoice_number VARCHAR(20) NOT NULL UNIQUE,
                issue_date DATE NOT NULL,
                due_date DATE,
                subtotal DECIMAL(10,2) NOT NULL,
                tax_rate DECIMAL(5,2) DEFAULT 0.00,
                tax_amount DECIMAL(10,2) GENERATED ALWAYS AS (subtotal * tax_rate / 100) STORED,
                total_amount DECIMAL(10,2) GENERATED ALWAYS AS (subtotal + tax_amount) STORED,
                status ENUM('draft', 'sent', 'paid', 'overdue') DEFAULT 'draft',
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
            )
        ");
        
        // Create users table
        $this->conn->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                full_name VARCHAR(100) NOT NULL,
                email VARCHAR(100) UNIQUE,
                role ENUM('admin', 'mechanic', 'receptionist') DEFAULT 'mechanic',
                is_active BOOLEAN DEFAULT TRUE,
                last_login TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Create indexes
        $this->conn->exec("CREATE INDEX IF NOT EXISTS idx_customers_name ON customers(name)");
        $this->conn->exec("CREATE INDEX IF NOT EXISTS idx_vehicles_plate ON vehicles(plate_number)");
        $this->conn->exec("CREATE INDEX IF NOT EXISTS idx_vehicles_customer ON vehicles(customer_id)");
        $this->conn->exec("CREATE INDEX IF NOT EXISTS idx_appointments_date ON appointments(appointment_date)");
        $this->conn->exec("CREATE INDEX IF NOT EXISTS idx_services_name ON services(name)");
        $this->conn->exec("CREATE INDEX IF NOT EXISTS idx_invoices_status ON invoices(status)");
        $this->conn->exec("CREATE INDEX IF NOT EXISTS idx_invoices_date ON invoices(issue_date)");
    }
    
    public function createDefaultAdmin() {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM users WHERE username = 'admin'");
        $stmt->execute();
        $result = $stmt->fetch();
        
        if ($result['count'] == 0) {
            $password = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt = $this->conn->prepare("INSERT INTO users (username, password, full_name, email, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute(['admin', $password, 'Administrator', 'admin@futureautomotive.com', 'admin']);
        }
    }
    
    public function seedData() {
        // Insert sample services
        $services = [
            ['صيانة دورية', 'فحص شامل للسيارة', 150.00, 30],
            ['تغيير زيت المحرك', 'تغيير زيت المحرك مع فلاتر عالية الجودة', 200.00, 45],
            ['فحص وتشخيص', 'فحص نظام التشخيص وتشخيص المشاكل', 100.00, 60],
            ['تغيير إطارات', 'تغيير إطارات السيارة', 80.00, 30],
            ['فحص بطارية', 'فحص البطارية والشحن', 120.00, 40],
            ['تكييف فرامل', 'تكييف وتوازن الفرامل', 250.00, 90]
        ];
        
        foreach ($services as $service) {
            $this->insert('services', [
                'name' => $service[0],
                'description' => $service[1],
                'price' => $service[2],
                'duration_minutes' => $service[3]
            ]);
        }
    }
}

// Initialize database connection
try {
    $database = new Database();
    $conn = $database->getConnection();
} catch (Exception $e) {
    die("Database Error: " . $e->getMessage());
}

// Helper functions
function getDB() {
    global $database;
    return $database;
}

function executeQuery($sql, $params = []) {
    global $database;
    return $database->query($sql, $params);
}

function fetchAll($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetchAll();
}

function fetchOne($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetch();
}

function insert($table, $data) {
    global $database;
    return $database->insert($table, $data);
}

function update($table, $data, $where, $where_params = []) {
    global $database;
    return $database->update($table, $data, $where, $where_params);
}

function delete($table, $where, $params = []) {
    global $database;
    return $database->delete($table, $where, $params);
}
?>
