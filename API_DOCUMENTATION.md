# SiCakap API Documentation

API untuk integrasi dengan aplikasi mobile Flutter.

**Base URL:** `http://localhost/sicakap-admin/public/api`

---

## Authentication

### Register
Mendaftarkan user baru dengan Supabase Auth.

**Endpoint:** `POST /auth/register`

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "password123",
  "nik": "1234567890123456",
  "name": "Nama Lengkap"
}
```

**Response Success (200):**
```json
{
  "success": true,
  "message": "Registrasi berhasil",
  "user": {
    "id": "uuid-string",
    "email": "user@example.com"
  }
}
```

**Response Error (400):**
```json
{
  "error": "Semua field harus diisi"
}
```

---

### Login
Login user dengan email dan password.

**Endpoint:** `POST /auth/login`

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

**Response Success (200):**
```json
{
  "success": true,
  "access_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "user": {
    "id": "uuid-string",
    "email": "user@example.com"
  }
}
```

**Response Error (401):**
```json
{
  "error": "Email atau password salah"
}
```

**Note:** Simpan `access_token` untuk digunakan di request berikutnya.

---

## Letter Templates

### Get All Templates
Mendapatkan list semua template surat yang aktif.

**Endpoint:** `GET /templates`

**Auth Required:** No

**Response Success (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": "uuid-string",
      "name": "Surat Keterangan Domisili",
      "description": "Surat keterangan bahwa seseorang tinggal di alamat tertentu",
      "required_fields": ["address", "rt", "rw"],
      "processing_time_days": 3,
      "requirements": "KTP, KK",
      "is_active": true
    },
    {
      "id": "uuid-string",
      "name": "Surat Keterangan Usaha",
      "description": "Surat keterangan bahwa seseorang memiliki usaha",
      "required_fields": ["business_name", "business_address", "business_type"],
      "processing_time_days": 5,
      "requirements": "KTP, Foto Usaha",
      "is_active": true
    }
  ]
}
```

---

## Residents

### Get Resident by NIK
Mendapatkan data penduduk berdasarkan NIK.

**Endpoint:** `GET /residents/{nik}`

**Auth Required:** Yes

**Headers:**
```
Authorization: Bearer {access_token}
```

**Response Success (200):**
```json
{
  "success": true,
  "data": {
    "id": "uuid-string",
    "nik": "1234567890123456",
    "name": "Nama Lengkap",
    "gender": "L",
    "birth_place": "Jakarta",
    "birth_date": "1990-01-01",
    "address": "Jl. Contoh No. 123",
    "rt": "001",
    "rw": "002",
    "village": "Kelurahan",
    "district": "Kecamatan",
    "regency": "Kabupaten",
    "province": "Provinsi",
    "religion": "Islam",
    "marital_status": "Kawin",
    "occupation": "Wiraswasta",
    "phone": "081234567890",
    "email": "user@example.com"
  }
}
```

**Response Error (404):**
```json
{
  "error": "Data penduduk tidak ditemukan"
}
```

---

## Letter Requests

### Create Request
Membuat permintaan surat baru.

**Endpoint:** `POST /requests`

**Auth Required:** Yes

**Headers:**
```
Authorization: Bearer {access_token}
```

**Request Body:**
```json
{
  "resident_id": "uuid-string",
  "template_id": "uuid-string",
  "notes": "Catatan tambahan (opsional)",
  "attachments": {
    "ktp_url": "https://storage.supabase.co/...",
    "kk_url": "https://storage.supabase.co/..."
  }
}
```

**Response Success (201):**
```json
{
  "success": true,
  "message": "Permintaan surat berhasil dibuat",
  "data": {
    "id": "uuid-string",
    "no_request": "REQ-20240115-ABC123",
    "resident_id": "uuid-string",
    "template_id": "uuid-string",
    "status": "pending",
    "notes": "Catatan tambahan",
    "requested_at": "2024-01-15T10:30:00Z"
  }
}
```

**Response Error (400):**
```json
{
  "error": "resident_id dan template_id harus diisi"
}
```

**Response Error (401):**
```json
{
  "error": "Unauthorized"
}
```

---

### Get All Requests
Mendapatkan semua permintaan surat user yang login.

**Endpoint:** `GET /requests`

**Auth Required:** Yes

**Headers:**
```
Authorization: Bearer {access_token}
```

**Response Success (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": "uuid-string",
      "no_request": "REQ-20240115-ABC123",
      "resident_name": "Nama Lengkap",
      "resident_nik": "1234567890123456",
      "template_name": "Surat Keterangan Domisili",
      "status": "pending",
      "requested_at": "2024-01-15T10:30:00Z",
      "processed_at": null,
      "finished_at": null,
      "notes": "Catatan"
    },
    {
      "id": "uuid-string",
      "no_request": "REQ-20240114-XYZ789",
      "resident_name": "Nama Lengkap",
      "resident_nik": "1234567890123456",
      "template_name": "Surat Keterangan Usaha",
      "status": "approved",
      "requested_at": "2024-01-14T08:00:00Z",
      "processed_at": "2024-01-14T14:30:00Z",
      "finished_at": null,
      "notes": null
    }
  ]
}
```

---

### Get Request Detail
Mendapatkan detail permintaan surat.

**Endpoint:** `GET /requests/{id}`

**Auth Required:** Yes

**Headers:**
```
Authorization: Bearer {access_token}
```

**Response Success (200):**
```json
{
  "success": true,
  "data": {
    "id": "uuid-string",
    "no_request": "REQ-20240115-ABC123",
    "resident_id": "uuid-string",
    "resident_name": "Nama Lengkap",
    "resident_nik": "1234567890123456",
    "template_id": "uuid-string",
    "template_name": "Surat Keterangan Domisili",
    "template_description": "Surat keterangan bahwa...",
    "status": "approved",
    "notes": "Catatan tambahan",
    "attachments": {
      "ktp_url": "https://...",
      "kk_url": "https://..."
    },
    "requested_at": "2024-01-15T10:30:00Z",
    "processed_at": "2024-01-15T15:00:00Z",
    "finished_at": null,
    "processed_by": "Admin Name",
    "rejection_reason": null
  }
}
```

**Response Error (404):**
```json
{
  "error": "Permintaan tidak ditemukan"
}
```

---

## Notifications

### Get Notifications
Mendapatkan notifikasi user.

**Endpoint:** `GET /notifications`

**Auth Required:** Yes

**Headers:**
```
Authorization: Bearer {access_token}
```

**Response Success (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": "uuid-string",
      "user_id": "uuid-string",
      "title": "Permintaan Disetujui",
      "message": "Permintaan surat REQ-20240115-ABC123 telah disetujui",
      "type": "request_approved",
      "is_read": false,
      "related_id": "uuid-of-request",
      "created_at": "2024-01-15T15:00:00Z"
    },
    {
      "id": "uuid-string",
      "user_id": "uuid-string",
      "title": "Surat Selesai",
      "message": "Surat REQ-20240114-XYZ789 telah selesai dibuat",
      "type": "letter_finished",
      "is_read": true,
      "related_id": "uuid-of-issued-letter",
      "created_at": "2024-01-14T16:30:00Z"
    }
  ]
}
```

---

## Status Codes

| Code | Description |
|------|-------------|
| 200 | Success |
| 201 | Created |
| 400 | Bad Request - Invalid input |
| 401 | Unauthorized - Token invalid/expired |
| 404 | Not Found |
| 500 | Internal Server Error |

---

## Request Status Flow

```
pending → verifikasi → approved → processing → finished
                    ↘ rejected
```

**Status Descriptions:**
- `pending`: Permintaan baru, menunggu verifikasi admin
- `verifikasi`: Sedang diverifikasi oleh admin
- `approved`: Disetujui, menunggu pembuatan surat
- `processing`: Surat sedang dibuat
- `finished`: Surat selesai dibuat
- `rejected`: Permintaan ditolak

---

## Error Handling

Semua error response mengikuti format:

```json
{
  "error": "Error message in Indonesian",
  "details": "Optional technical details"
}
```

**Common Errors:**

1. **Missing Authorization:**
   ```json
   {
     "error": "Unauthorized"
   }
   ```

2. **Invalid Token:**
   ```json
   {
     "error": "JWT expired",
     "details": "Token expired at 2024-01-15T10:00:00Z"
   }
   ```

3. **Validation Error:**
   ```json
   {
     "error": "resident_id dan template_id harus diisi"
   }
   ```

---

## Rate Limiting

Currently no rate limiting. Will be implemented in production.

**Planned Limits:**
- 100 requests per minute per user
- 1000 requests per day per user

---

## CORS

CORS enabled for all origins (`*`). Update in production to specific domains.

---

## Testing

### PowerShell Example:

```powershell
# Set base URL
$baseUrl = "http://localhost/sicakap-admin/api"

# Register
$body = @{
    email = "test@example.com"
    password = "password123"
    nik = "1234567890123456"
    name = "Test User"
} | ConvertTo-Json

$response = Invoke-RestMethod -Uri "$baseUrl/auth/register" -Method Post -Body $body -ContentType "application/json"

# Login
$body = @{
    email = "test@example.com"
    password = "password123"
} | ConvertTo-Json

$response = Invoke-RestMethod -Uri "$baseUrl/auth/login" -Method Post -Body $body -ContentType "application/json"
$token = $response.access_token

# Get templates
$headers = @{ Authorization = "Bearer $token" }
$templates = Invoke-RestMethod -Uri "$baseUrl/templates" -Headers $headers
```

---

## Flutter Integration Example

```dart
import 'package:http/http.dart' as http;
import 'dart:convert';

class ApiService {
  static const String baseUrl = 'http://localhost/sicakap-admin/api';
  String? _token;
  
  Future<Map<String, dynamic>> login(String email, String password) async {
    final response = await http.post(
      Uri.parse('$baseUrl/auth/login'),
      headers: {'Content-Type': 'application/json'},
      body: json.encode({
        'email': email,
        'password': password,
      }),
    );
    
    if (response.statusCode == 200) {
      final data = json.decode(response.body);
      _token = data['access_token'];
      return data;
    } else {
      throw Exception(json.decode(response.body)['error']);
    }
  }
  
  Future<List<dynamic>> getTemplates() async {
    final response = await http.get(
      Uri.parse('$baseUrl/templates'),
    );
    
    if (response.statusCode == 200) {
      return json.decode(response.body)['data'];
    } else {
      throw Exception('Failed to load templates');
    }
  }
  
  Future<Map<String, dynamic>> createRequest({
    required String residentId,
    required String templateId,
    String? notes,
  }) async {
    final response = await http.post(
      Uri.parse('$baseUrl/requests'),
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer $_token',
      },
      body: json.encode({
        'resident_id': residentId,
        'template_id': templateId,
        'notes': notes,
      }),
    );
    
    if (response.statusCode == 201) {
      return json.decode(response.body);
    } else {
      throw Exception(json.decode(response.body)['error']);
    }
  }
}
```

---

## Changelog

### Version 1.0.0 (2024-01-15)
- Initial API release
- Authentication endpoints (login, register)
- Letter templates endpoints
- Letter requests CRUD
- Notifications endpoint
