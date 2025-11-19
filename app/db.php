<?php
/**
 * Supabase REST API Helper
 * Gunakan REST API untuk akses database, lebih reliable daripada PostgreSQL pooler
 */

function supabase_request($method, $endpoint, $data = null, $token = null) {
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
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    } elseif ($method === 'PATCH') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
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

// Backward compatibility: return dummy PDO-like object for existing code
function db() {
    return new SupabaseDB();
}

class SupabaseDB {
    public function prepare($sql) {
        return new SupabaseStatement($sql);
    }
    
    public function query($sql) {
        // Simple query executor for basic SQL
        return new SupabaseStatement($sql, true);
    }
}

class SupabaseStatement {
    private $sql;
    private $params = [];
    private $result = null;
    private $boundValues = [];
    
    public function __construct($sql, $execute = false) {
        $this->sql = $sql;
        if ($execute) {
            $this->execute();
        }
    }
    
    public function bindValue($parameter, $value, $type = null) {
        $this->boundValues[$parameter] = $value;
        return true;
    }
    
    public function bindParam($parameter, &$variable, $type = null) {
        $this->boundValues[$parameter] = &$variable;
        return true;
    }
    
    public function execute($params = []) {
        // Merge bound values dengan params
        if (!empty($this->boundValues)) {
            $params = array_merge($this->boundValues, $params);
        }
        
        $this->params = $params;
        
        // Parse SQL dan convert ke Supabase REST API call
        // Simplified: hanya support SELECT basic
        if (preg_match('/SELECT \* FROM (\w+) WHERE (\w+) = \?/i', $this->sql, $matches)) {
            $table = $matches[1];
            $column = $matches[2];
            $value = $params[0] ?? null;
            
            $endpoint = "$table?$column=eq.$value";
            $result = supabase_request('GET', $endpoint);
            
            if ($result['code'] === 200) {
                $this->result = $result['data'];
                return true;
            }
        } elseif (preg_match('/SELECT \* FROM (\w+)/i', $this->sql, $matches)) {
            // SELECT semua data
            $table = $matches[1];
            $endpoint = $table;
            $result = supabase_request('GET', $endpoint);
            
            if ($result['code'] === 200) {
                $this->result = $result['data'];
                return true;
            }
        }
        
        return false;
    }
    
    public function fetch($mode = null) {
        if (!empty($this->result) && is_array($this->result)) {
            return $this->result[0] ?? null;
        }
        return null;
    }
    
    public function fetchAll($mode = null) {
        return $this->result ?? [];
    }
    
    public function fetchColumn($column = 0) {
        $row = $this->fetch();
        if ($row) {
            if (is_numeric($column)) {
                return array_values($row)[$column] ?? null;
            }
            return $row[$column] ?? null;
        }
        return null;
    }
    
    public function rowCount() {
        return is_array($this->result) ? count($this->result) : 0;
    }
}