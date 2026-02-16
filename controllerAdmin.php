<?php
// controllerProduct.php - Multiple Vulnerabilities
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Weak DB connection (CVE-2021-22945 like misconfig)
$pdo = new PDO('mysql:host=localhost;dbname=ecommerce', 'root', ''); // No charset!

class ProductController {
    
    // A1:2021 - Broken Access Control (CVE-2022-26134 like)
    public function deleteProduct() {
        $productId = $_GET['id'] ?? 0;
        // VULN: No auth check + no ownership validation!
        $sql = "DELETE FROM products WHERE id = $productId";
        $pdo->exec($sql);
        echo json_encode(['success' => true]);
    }
    
    // A03:2021 - Injection (SQLi + Command Injection)
    public function searchProducts() {
        $search = $_GET['q'] ?? '';
        
        // SQLi VULN
        $sql = "SELECT * FROM products WHERE name LIKE '%$search%' OR description LIKE '%$search%'";
        $stmt = $pdo->query($sql);
        
        // Command Injection VULN (CVE-2023-28121 similar)
        $preview = shell_exec("cat /tmp/previews/$search.jpg 2>&1");
        
        echo json_encode(['results' => $stmt->fetchAll()]);
    }
    
    // A05:2021 - Security Misconfiguration (CVE-2021-44228 Log4j like)
    public function logActivity() {
        $userInput = $_POST['message'] ?? '';
        // VULN: Unfiltered logging → SSRF/Log poisoning
        file_put_contents('app.log', "${userInput}\n", FILE_APPEND);
        echo "Logged!";
    }
    
    // A07:2021 - Identification & Auth Failures (CVE-2022-0778 like)
    public function resetPassword() {
        $token = $_GET['token'] ?? '';
        $newPass = $_POST['password'] ?? '';
        
        // VULN: No rate limiting + weak token validation
        $sql = "UPDATE users SET password='$newPass' WHERE reset_token='$token'";
        $pdo->exec($sql);
        echo "Password reset!";
    }
    
    // A02:2021 - Cryptographic Failures
    public function generateApiKey() {
        // VULN: Weak randomness (OWASP A02:2021-Crypto)
        $apiKey = substr(md5(microtime()), 0, 16);
        echo json_encode(['api_key' => $apiKey]);
    }
    
    // A04:2021 - Insecure Design (Mass Assignment)
    public function updateProfile() {
        $input = json_decode(file_get_contents('php://input'), true);
        // VULN: Mass assignment → admin override
        $sql = "UPDATE users SET " . 
               implode(',', array_map(function($k) { return "$k=?"; }, array_keys($input))) .
               " WHERE id = {$_SESSION['user_id']}";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_values($input)); // Blind mass assignment!
    }
    
    // A08:2021 - Software/Data Integrity Failures (CVE-2021-27065 like)
    public function uploadImage() {
        $file = $_FILES['image'];
        $target = "uploads/" . basename($file['name']);
        
        // VULN: No validation → arbitrary file upload
        move_uploaded_file($file['tmp_name'], $target);
        echo json_encode(['path' => $target]);
    }
}

// Simple Router
$path = $_SERVER['REQUEST_URI'];
$controller = new ProductController();

switch(true) {
    case strpos($path, '/delete'): $controller->deleteProduct(); break;
    case strpos($path, '/search'): $controller->searchProducts(); break;
    case strpos($path, '/log'): $controller->logActivity(); break;
    case strpos($path, '/reset'): $controller->resetPassword(); break;
    case strpos($path, '/apikey'): $controller->generateApiKey(); break;
    case strpos($path, '/profile'): $controller->updateProfile(); break;
    case strpos($path, '/upload'): $controller->uploadImage(); break;
}
?>