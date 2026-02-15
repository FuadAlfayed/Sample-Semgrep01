<?php
// controllerUser.php - VULNERABLE VERSION
header('Content-Type: application/json');
session_start();

// Database connection
$host = 'localhost';
$dbname = 'pentest_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
} catch(PDOException $e) {
    die(json_encode(['error' => 'Database connection failed']));
}

class UserController {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    // VULNERABILITY #1: SQL Injection di login
    public function login() {
        $input = json_decode(file_get_contents('php://input'), true);
        $username = $input['username'] ?? '';
        $password = $input['password'] ?? '';
        
        // VULN: Direct string concatenation!
        $sql = "SELECT id, username, role FROM users WHERE username='$username' AND password='$password'";
        $stmt = $this->pdo->query($sql);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            echo json_encode(['success' => true, 'user' => $user, 'token' => 'fake_jwt_token']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
        }
    }
    
    // VULNERABILITY #2: SQL Injection di getUser
    public function getUser($id) {
        // VULN: No sanitization on $id parameter!
        $sql = "SELECT * FROM users WHERE id = $id";
        $stmt = $this->pdo->query($sql);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['users' => $users]);
    }
    
    // VULNERABILITY #3: Blind SQLi di searchUsers
    public function searchUsers() {
        $input = json_decode(file_get_contents('php://input'), true);
        $search = $input['search'] ?? '';
        
        // VULN: Search term directly injected!
        $sql = "SELECT * FROM users WHERE username LIKE '%$search%' OR email LIKE '%$search%'";
        $stmt = $this->pdo->query($sql);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['results' => $users]);
    }
    
    // VULNERABILITY #4: Time-based Blind SQLi
    public function profile($user_id) {
        $sql = "SELECT * FROM users WHERE id = $user_id AND (SELECT COUNT(*) FROM orders WHERE user_id = users.id) > 0";
        $stmt = $this->pdo->query($sql);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode($profile ?: ['error' => 'Profile not found']);
    }
}

// Router sederhana
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$controller = new UserController($pdo);

switch ($path) {
    case '/api/login':
        if ($method === 'POST') $controller->login();
        break;
        
    case '/api/users':
        if ($method === 'GET') {
            $id = $_GET['id'] ?? 0;
            $controller->getUser($id);
        }
        break;
        
    case '/api/search':
        if ($method === 'POST') $controller->searchUsers();
        break;
        
    case '/api/profile':
        $path_parts = explode('/', trim($_SERVER['REQUEST_URI'], '/'));
        $user_id = end($path_parts);
        $controller->profile($user_id);
        break;
        
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Not found']);
}
?>