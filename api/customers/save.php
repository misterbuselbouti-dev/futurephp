<?php
// FUTURE AUTOMOTIVE - Save Customer API
// Enregistre un nouveau client dans la base de données

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Include database configuration
require_once '../../config.php';

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Méthode non autorisée'
    ]);
    exit;
}

try {
    // Get POST data
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $city = $_POST['city'] ?? '';
    $country = $_POST['country'] ?? '';

    // Validate required fields
    if (empty($name)) {
        throw new Exception('Le nom du client est requis');
    }

    // Use the database connection
    $database = new Database();
    $pdo = $database->connect();

    // Check if email already exists
    if (!empty($email)) {
        $stmt = $pdo->prepare("SELECT id FROM customers WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            throw new Exception('Cet email est déjà utilisé');
        }
    }

    // SQL to insert new customer
    $sql = "INSERT INTO customers (name, email, phone, address, city, country) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([$name, $email, $phone, $address, $city, $country]);

    if ($result) {
        $customer_id = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message' => 'Client ajouté avec succès',
            'customer_id' => $customer_id,
            'customer' => [
                'id' => $customer_id,
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'address' => $address,
                'city' => $city,
                'country' => $country
            ]
        ]);
    } else {
        throw new Exception('Erreur lors de l\'ajout du client');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
