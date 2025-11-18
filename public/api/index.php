<?php
/**
 * REST API for SiCakap Mobile App
 * 
 * Endpoints:
 * - POST /api/auth/login
 * - POST /api/auth/register
 * - GET /api/templates
 * - GET /api/residents/{nik}
 * - POST /api/requests
 * - GET /api/requests
 * - GET /api/requests/{id}
 * - GET /api/notifications
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../app/config.php';

// Supabase Configuration
define('SUPABASE_URL', 'https://zjjqaupkggdaxlieoaoz.supabase.co'); 
define('SUPABASE_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InpqanFhdXBrZ2dkYXhsaWVvYW96Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3NjIyNTQ0NzUsImV4cCI6MjA3NzgzMDQ3NX0.R1fepJGqmBs6aN2-e1paeacCLXXoGlvQQw-3uLqo-BQ');

/**
 * Send JSON response
 */
function jsonResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Get Authorization token from header
 */
function getBearerToken() {
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        if (preg_match('/Bearer\s+(.*)$/i', $headers['Authorization'], $matches)) {
            return $matches[1];
        }
    }
    return null;
}

/**
 * Make Supabase API request
 */
function supabaseRequest($method, $endpoint, $data = null, $token = null) {
    $url = SUPABASE_URL . '/rest/v1/' . $endpoint;
    
    $headers = [
        'apikey: ' . SUPABASE_KEY,
        'Content-Type: application/json',
        'Prefer: return=representation'
    ];
    
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    } elseif ($method === 'PATCH') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    } elseif ($method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'data' => json_decode($response, true)
    ];
}

// Parse request
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
// Remove base path: /sicakap-admin/public/api
$path = str_replace('/sicakap-admin/public/api', '', $path);
$segments = array_filter(explode('/', $path));
$segments = array_values($segments);

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Routing
try {
    
    // ========================================
    // AUTHENTICATION
    // ========================================
    
    if ($segments[0] === 'auth' && $segments[1] === 'login' && $method === 'POST') {
        // Login with Supabase Auth
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';
        
        if (!$email || !$password) {
            jsonResponse(['error' => 'Email dan password harus diisi'], 400);
        }
        
        $ch = curl_init(SUPABASE_URL . '/auth/v1/token?grant_type=password');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'apikey: ' . SUPABASE_KEY,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'email' => $email,
            'password' => $password
        ]));
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            jsonResponse([
                'success' => true,
                'access_token' => $data['access_token'],
                'user' => $data['user']
            ]);
        } else {
            jsonResponse(['error' => 'Email atau password salah'], 401);
        }
    }
    
    if ($segments[0] === 'auth' && $segments[1] === 'register' && $method === 'POST') {
        // Register with Supabase Auth
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';
        $nik = $input['nik'] ?? '';
        $name = $input['name'] ?? '';
        
        if (!$email || !$password || !$nik || !$name) {
            jsonResponse(['error' => 'Semua field harus diisi'], 400);
        }
        
        // First, create auth user
        $ch = curl_init(SUPABASE_URL . '/auth/v1/signup');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'apikey: ' . SUPABASE_KEY,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'email' => $email,
            'password' => $password
        ]));
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $authData = json_decode($response, true);
            
            // Get user ID from response (could be in 'user' or directly in response)
            $userId = $authData['user']['id'] ?? $authData['id'] ?? null;
            
            if (!$userId) {
                jsonResponse([
                    'error' => 'User ID tidak ditemukan',
                    'debug' => $authData
                ], 400);
            }
            
            // Then create resident record
            $result = supabaseRequest('POST', 'residents', [
                'id' => $userId,
                'nik' => $nik,
                'name' => $name
            ]);
            
            jsonResponse([
                'success' => true,
                'message' => 'Registrasi berhasil',
                'user' => $authData
            ]);
        } else {
            $errorData = json_decode($response, true);
            jsonResponse([
                'error' => 'Gagal registrasi',
                'detail' => $errorData,
                'http_code' => $httpCode
            ], 400);
        }
    }
    
    // Get auth token for protected routes
    $token = getBearerToken();
    
    // ========================================
    // LETTER TEMPLATES
    // ========================================
    
    if ($segments[0] === 'templates' && $method === 'GET') {
        $result = supabaseRequest('GET', 'letter_templates?is_active=eq.true&select=*');
        
        if ($result['code'] === 200) {
            jsonResponse([
                'success' => true,
                'data' => $result['data']
            ]);
        } else {
            jsonResponse(['error' => 'Gagal mengambil data template'], 500);
        }
    }
    
    // ========================================
    // RESIDENTS
    // ========================================
    
    if ($segments[0] === 'residents' && $method === 'GET' && isset($segments[1])) {
        $nik = $segments[1];
        
        $result = supabaseRequest('GET', "residents?nik=eq.$nik", null, $token);
        
        if ($result['code'] === 200 && !empty($result['data'])) {
            jsonResponse([
                'success' => true,
                'data' => $result['data'][0]
            ]);
        } else {
            jsonResponse(['error' => 'Data penduduk tidak ditemukan'], 404);
        }
    }
    
    // ========================================
    // LETTER REQUESTS
    // ========================================
    
    if ($segments[0] === 'requests' && $method === 'POST') {
        if (!$token) {
            jsonResponse(['error' => 'Unauthorized'], 401);
        }
        
        // Validate input
        $residentId = $input['resident_id'] ?? null;
        $templateId = $input['template_id'] ?? null;
        
        if (!$residentId || !$templateId) {
            jsonResponse(['error' => 'resident_id dan template_id harus diisi'], 400);
        }
        
        // Generate request number
        $noRequest = 'REQ-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
        
        // Get resident data
        $residentResult = supabaseRequest('GET', "residents?id=eq.$residentId&select=nik,name", null, $token);
        
        if (empty($residentResult['data'])) {
            jsonResponse(['error' => 'Data penduduk tidak ditemukan'], 404);
        }
        
        $resident = $residentResult['data'][0];
        
        // Create request
        $requestData = [
            'no_request' => $noRequest,
            'resident_id' => $residentId,
            'resident_nik' => $resident['nik'],
            'resident_name' => $resident['name'],
            'template_id' => $templateId,
            'status' => 'pending',
            'notes' => $input['notes'] ?? null,
            'attachments' => $input['attachments'] ?? null
        ];
        
        $result = supabaseRequest('POST', 'letter_requests', $requestData, $token);
        
        if ($result['code'] === 201) {
            jsonResponse([
                'success' => true,
                'message' => 'Permintaan surat berhasil dibuat',
                'data' => $result['data'][0]
            ], 201);
        } else {
            jsonResponse(['error' => 'Gagal membuat permintaan'], 500);
        }
    }
    
    if ($segments[0] === 'requests' && $method === 'GET' && !isset($segments[1])) {
        if (!$token) {
            jsonResponse(['error' => 'Unauthorized'], 401);
        }
        
        // Get all requests for current user
        $result = supabaseRequest('GET', 'vw_requests_detail?order=requested_at.desc', null, $token);
        
        if ($result['code'] === 200) {
            jsonResponse([
                'success' => true,
                'data' => $result['data']
            ]);
        } else {
            jsonResponse(['error' => 'Gagal mengambil data'], 500);
        }
    }
    
    if ($segments[0] === 'requests' && $method === 'GET' && isset($segments[1])) {
        if (!$token) {
            jsonResponse(['error' => 'Unauthorized'], 401);
        }
        
        $requestId = $segments[1];
        
        $result = supabaseRequest('GET', "vw_requests_detail?id=eq.$requestId", null, $token);
        
        if ($result['code'] === 200 && !empty($result['data'])) {
            jsonResponse([
                'success' => true,
                'data' => $result['data'][0]
            ]);
        } else {
            jsonResponse(['error' => 'Permintaan tidak ditemukan'], 404);
        }
    }
    
    // ========================================
    // NOTIFICATIONS
    // ========================================
    
    if ($segments[0] === 'notifications' && $method === 'GET') {
        if (!$token) {
            jsonResponse(['error' => 'Unauthorized'], 401);
        }
        
        $result = supabaseRequest('GET', 'notifications?order=created_at.desc&limit=50', null, $token);
        
        if ($result['code'] === 200) {
            jsonResponse([
                'success' => true,
                'data' => $result['data']
            ]);
        } else {
            jsonResponse(['error' => 'Gagal mengambil notifikasi'], 500);
        }
    }
    
    // Route not found
    jsonResponse(['error' => 'Endpoint tidak ditemukan'], 404);
    
} catch (Exception $e) {
    jsonResponse([
        'error' => 'Terjadi kesalahan server',
        'message' => $e->getMessage()
    ], 500);
}
