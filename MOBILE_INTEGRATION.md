# Panduan Integrasi Mobile Flutter

Panduan lengkap untuk mengintegrasikan aplikasi mobile Flutter dengan backend SiCakap.

## Arsitektur Sistem

```
┌─────────────────┐         ┌─────────────────┐         ┌─────────────────┐
│                 │         │                 │         │                 │
│  Flutter App    │◄───────►│  PHP REST API   │◄───────►│    Supabase     │
│  (Mobile)       │  JSON   │  (Backend)      │  REST   │  (Database)     │
│                 │         │                 │         │                 │
└─────────────────┘         └─────────────────┘         └─────────────────┘
       │                            │                            │
       │                            │                            │
       ▼                            ▼                            ▼
  - UI/UX Layer            - API Routes              - PostgreSQL
  - State Management       - Authentication          - Storage
  - Local Storage          - Business Logic          - Auth Service
  - Push Notifications     - File Upload             - Real-time
```

## Setup Flutter Project

### 1. Buat Project Flutter

```bash
flutter create sicakap_mobile
cd sicakap_mobile
```

### 2. Install Dependencies

Edit `pubspec.yaml`:

```yaml
name: sicakap_mobile
description: SiCakap - Aplikasi Layanan Administrasi Kependudukan

environment:
  sdk: '>=3.0.0 <4.0.0'

dependencies:
  flutter:
    sdk: flutter
  
  # State Management
  provider: ^6.1.1
  
  # HTTP & API
  http: ^1.1.0
  dio: ^5.4.0
  
  # Supabase
  supabase_flutter: ^2.0.0
  
  # Local Storage
  shared_preferences: ^2.2.2
  
  # UI Components
  google_fonts: ^6.1.0
  flutter_svg: ^2.0.9
  cached_network_image: ^3.3.1
  
  # Forms & Validation
  flutter_form_builder: ^9.1.1
  form_builder_validators: ^9.1.0
  
  # Date & Time
  intl: ^0.18.1
  
  # File Picker
  file_picker: ^6.1.1
  image_picker: ^1.0.5
  
  # PDF Viewer
  flutter_pdfview: ^1.3.2
  
  # Push Notifications
  firebase_messaging: ^14.7.9
  firebase_core: ^2.24.2
  
  # Permissions
  permission_handler: ^11.1.0

dev_dependencies:
  flutter_test:
    sdk: flutter
  flutter_launcher_icons: ^0.13.1
  flutter_lints: ^3.0.0
```

Install:
```bash
flutter pub get
```

### 3. Konfigurasi Supabase

Buat file `lib/config/supabase_config.dart`:

```dart
import 'package:supabase_flutter/supabase_flutter.dart';

class SupabaseConfig {
  static const String supabaseUrl = 'YOUR_SUPABASE_URL';
  static const String supabaseAnonKey = 'YOUR_SUPABASE_ANON_KEY';
  
  static Future<void> initialize() async {
    await Supabase.initialize(
      url: supabaseUrl,
      anonKey: supabaseAnonKey,
      authOptions: const FlutterAuthClientOptions(
        authFlowType: AuthFlowType.pkce,
      ),
    );
  }
  
  static SupabaseClient get client => Supabase.instance.client;
}
```

### 4. Setup API Service

Buat file `lib/services/api_service.dart`:

```dart
import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

class ApiService {
  static const String baseUrl = 'http://localhost/sicakap-admin/api';
  // Production: static const String baseUrl = 'https://yourdomain.com/api';
  
  String? _token;
  
  ApiService() {
    _loadToken();
  }
  
  Future<void> _loadToken() async {
    final prefs = await SharedPreferences.getInstance();
    _token = prefs.getString('access_token');
  }
  
  Future<void> _saveToken(String token) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('access_token', token);
    _token = token;
  }
  
  Future<void> clearToken() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('access_token');
    _token = null;
  }
  
  Map<String, String> get _headers => {
    'Content-Type': 'application/json',
    if (_token != null) 'Authorization': 'Bearer $_token',
  };
  
  // Authentication
  Future<Map<String, dynamic>> register({
    required String email,
    required String password,
    required String nik,
    required String name,
  }) async {
    final response = await http.post(
      Uri.parse('$baseUrl/auth/register'),
      headers: {'Content-Type': 'application/json'},
      body: json.encode({
        'email': email,
        'password': password,
        'nik': nik,
        'name': name,
      }),
    );
    
    return _handleResponse(response);
  }
  
  Future<Map<String, dynamic>> login({
    required String email,
    required String password,
  }) async {
    final response = await http.post(
      Uri.parse('$baseUrl/auth/login'),
      headers: {'Content-Type': 'application/json'},
      body: json.encode({
        'email': email,
        'password': password,
      }),
    );
    
    final data = _handleResponse(response);
    
    if (data['success'] == true && data['access_token'] != null) {
      await _saveToken(data['access_token']);
    }
    
    return data;
  }
  
  // Templates
  Future<List<dynamic>> getTemplates() async {
    final response = await http.get(
      Uri.parse('$baseUrl/templates'),
      headers: _headers,
    );
    
    final data = _handleResponse(response);
    return data['data'] ?? [];
  }
  
  // Residents
  Future<Map<String, dynamic>> getResidentByNik(String nik) async {
    final response = await http.get(
      Uri.parse('$baseUrl/residents/$nik'),
      headers: _headers,
    );
    
    final data = _handleResponse(response);
    return data['data'];
  }
  
  // Requests
  Future<Map<String, dynamic>> createRequest({
    required String residentId,
    required String templateId,
    String? notes,
    Map<String, dynamic>? attachments,
  }) async {
    final response = await http.post(
      Uri.parse('$baseUrl/requests'),
      headers: _headers,
      body: json.encode({
        'resident_id': residentId,
        'template_id': templateId,
        'notes': notes,
        'attachments': attachments,
      }),
    );
    
    return _handleResponse(response);
  }
  
  Future<List<dynamic>> getRequests() async {
    final response = await http.get(
      Uri.parse('$baseUrl/requests'),
      headers: _headers,
    );
    
    final data = _handleResponse(response);
    return data['data'] ?? [];
  }
  
  Future<Map<String, dynamic>> getRequestDetail(String id) async {
    final response = await http.get(
      Uri.parse('$baseUrl/requests/$id'),
      headers: _headers,
    );
    
    final data = _handleResponse(response);
    return data['data'];
  }
  
  // Notifications
  Future<List<dynamic>> getNotifications() async {
    final response = await http.get(
      Uri.parse('$baseUrl/notifications'),
      headers: _headers,
    );
    
    final data = _handleResponse(response);
    return data['data'] ?? [];
  }
  
  // Response handler
  Map<String, dynamic> _handleResponse(http.Response response) {
    final data = json.decode(response.body);
    
    if (response.statusCode >= 200 && response.statusCode < 300) {
      return data;
    } else {
      throw ApiException(
        message: data['error'] ?? 'Terjadi kesalahan',
        statusCode: response.statusCode,
      );
    }
  }
}

class ApiException implements Exception {
  final String message;
  final int statusCode;
  
  ApiException({required this.message, required this.statusCode});
  
  @override
  String toString() => message;
}
```

### 5. Setup State Management

Buat file `lib/providers/auth_provider.dart`:

```dart
import 'package:flutter/material.dart';
import '../services/api_service.dart';

class AuthProvider with ChangeNotifier {
  final ApiService _apiService = ApiService();
  
  bool _isAuthenticated = false;
  Map<String, dynamic>? _user;
  bool _isLoading = false;
  String? _error;
  
  bool get isAuthenticated => _isAuthenticated;
  Map<String, dynamic>? get user => _user;
  bool get isLoading => _isLoading;
  String? get error => _error;
  
  Future<bool> login(String email, String password) async {
    _isLoading = true;
    _error = null;
    notifyListeners();
    
    try {
      final response = await _apiService.login(
        email: email,
        password: password,
      );
      
      _isAuthenticated = true;
      _user = response['user'];
      _isLoading = false;
      notifyListeners();
      
      return true;
    } catch (e) {
      _error = e.toString();
      _isLoading = false;
      notifyListeners();
      
      return false;
    }
  }
  
  Future<bool> register({
    required String email,
    required String password,
    required String nik,
    required String name,
  }) async {
    _isLoading = true;
    _error = null;
    notifyListeners();
    
    try {
      await _apiService.register(
        email: email,
        password: password,
        nik: nik,
        name: name,
      );
      
      _isLoading = false;
      notifyListeners();
      
      return true;
    } catch (e) {
      _error = e.toString();
      _isLoading = false;
      notifyListeners();
      
      return false;
    }
  }
  
  Future<void> logout() async {
    await _apiService.clearToken();
    _isAuthenticated = false;
    _user = null;
    notifyListeners();
  }
}
```

### 6. Main App Setup

Edit `lib/main.dart`:

```dart
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'config/supabase_config.dart';
import 'providers/auth_provider.dart';
import 'screens/splash_screen.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  
  // Initialize Supabase
  await SupabaseConfig.initialize();
  
  runApp(const MyApp());
}

class MyApp extends StatelessWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        ChangeNotifierProvider(create: (_) => AuthProvider()),
      ],
      child: MaterialApp(
        title: 'SiCakap',
        theme: ThemeData(
          primarySwatch: Colors.blue,
          useMaterial3: true,
        ),
        home: const SplashScreen(),
      ),
    );
  }
}
```

### 7. Contoh Screen - Login

Buat file `lib/screens/login_screen.dart`:

```dart
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/auth_provider.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _obscurePassword = true;

  @override
  void dispose() {
    _emailController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _handleLogin() async {
    if (!_formKey.currentState!.validate()) return;

    final authProvider = context.read<AuthProvider>();
    final success = await authProvider.login(
      _emailController.text.trim(),
      _passwordController.text,
    );

    if (!mounted) return;

    if (success) {
      Navigator.pushReplacementNamed(context, '/home');
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(authProvider.error ?? 'Login gagal'),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(24.0),
          child: Form(
            key: _formKey,
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                // Logo
                Icon(
                  Icons.account_balance,
                  size: 80,
                  color: Theme.of(context).primaryColor,
                ),
                const SizedBox(height: 16),
                
                // Title
                Text(
                  'SiCakap',
                  style: Theme.of(context).textTheme.headlineMedium?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: 8),
                Text(
                  'Layanan Administrasi Kependudukan',
                  style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                    color: Colors.grey,
                  ),
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: 48),
                
                // Email Field
                TextFormField(
                  controller: _emailController,
                  keyboardType: TextInputType.emailAddress,
                  decoration: const InputDecoration(
                    labelText: 'Email',
                    prefixIcon: Icon(Icons.email),
                    border: OutlineInputBorder(),
                  ),
                  validator: (value) {
                    if (value == null || value.isEmpty) {
                      return 'Email harus diisi';
                    }
                    if (!value.contains('@')) {
                      return 'Email tidak valid';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 16),
                
                // Password Field
                TextFormField(
                  controller: _passwordController,
                  obscureText: _obscurePassword,
                  decoration: InputDecoration(
                    labelText: 'Password',
                    prefixIcon: const Icon(Icons.lock),
                    suffixIcon: IconButton(
                      icon: Icon(
                        _obscurePassword
                            ? Icons.visibility
                            : Icons.visibility_off,
                      ),
                      onPressed: () {
                        setState(() {
                          _obscurePassword = !_obscurePassword;
                        });
                      },
                    ),
                    border: const OutlineInputBorder(),
                  ),
                  validator: (value) {
                    if (value == null || value.isEmpty) {
                      return 'Password harus diisi';
                    }
                    if (value.length < 6) {
                      return 'Password minimal 6 karakter';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 24),
                
                // Login Button
                Consumer<AuthProvider>(
                  builder: (context, auth, child) {
                    return ElevatedButton(
                      onPressed: auth.isLoading ? null : _handleLogin,
                      style: ElevatedButton.styleFrom(
                        padding: const EdgeInsets.symmetric(vertical: 16),
                      ),
                      child: auth.isLoading
                          ? const SizedBox(
                              height: 20,
                              width: 20,
                              child: CircularProgressIndicator(
                                strokeWidth: 2,
                              ),
                            )
                          : const Text('Login'),
                    );
                  },
                ),
                const SizedBox(height: 16),
                
                // Register Link
                TextButton(
                  onPressed: () {
                    Navigator.pushNamed(context, '/register');
                  },
                  child: const Text('Belum punya akun? Daftar'),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
```

## Testing

### Test di Emulator Android

1. Start XAMPP (Apache untuk API PHP)
2. Update `api_service.dart`:
   ```dart
   static const String baseUrl = 'http://10.0.2.2/sicakap-admin/api';
   ```
   (10.0.2.2 adalah localhost dari perspektif Android emulator)

3. Run app:
   ```bash
   flutter run
   ```

### Test di Device Real (Same Network)

1. Cek IP komputer (cmd: `ipconfig`, cari IPv4)
2. Update `api_service.dart`:
   ```dart
   static const String baseUrl = 'http://192.168.x.x/sicakap-admin/api';
   ```

3. Run app:
   ```bash
   flutter run
   ```

## Fitur yang Bisa Dibangun

### Phase 1 - Core Features ✅
- [x] Login & Register
- [x] List Template Surat
- [x] Buat Permintaan Surat
- [x] List Permintaan (History)
- [x] Detail Permintaan
- [x] Notifikasi

### Phase 2 - Enhanced Features
- [ ] Upload Foto/Dokumen Persyaratan
- [ ] Download PDF Surat yang Sudah Jadi
- [ ] Push Notifications (FCM)
- [ ] Profile Management
- [ ] Dark Mode

### Phase 3 - Advanced Features
- [ ] Real-time Status Updates (Supabase Realtime)
- [ ] In-app PDF Viewer
- [ ] Offline Mode dengan Local Storage
- [ ] Biometric Authentication
- [ ] Share Surat ke WhatsApp/Email

## Best Practices

1. **Error Handling**: Selalu wrap API calls dengan try-catch
2. **Loading States**: Tampilkan loading indicator saat fetch data
3. **Validation**: Validasi input di client-side sebelum kirim ke server
4. **Token Management**: Auto-refresh token sebelum expired
5. **Offline Support**: Cache data penting di local storage
6. **Security**: Jangan simpan password di plain text

## Troubleshooting

### Connection Refused
- Pastikan XAMPP Apache running
- Cek firewall Windows tidak block Apache
- Pastikan IP address benar

### CORS Error
- Header CORS sudah ada di `public/api/index.php`
- Restart Apache setelah update code

### Token Expired
- Implement auto-refresh token
- Atau redirect ke login screen

## Resources

- Flutter Docs: https://docs.flutter.dev
- Supabase Flutter: https://supabase.com/docs/guides/getting-started/quickstarts/flutter
- Provider Package: https://pub.dev/packages/provider
- HTTP Package: https://pub.dev/packages/http
