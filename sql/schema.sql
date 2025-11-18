-- Buat database lalu impor script ini via phpMyAdmin
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  name VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS residents (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nik VARCHAR(50) NOT NULL UNIQUE,
  name VARCHAR(255) NOT NULL,
  tempat_lahir VARCHAR(255),
  tanggal_lahir DATE,
  jenis_kelamin VARCHAR(20),
  agama VARCHAR(50),
  alamat TEXT,
  rt VARCHAR(10),
  rw VARCHAR(10),
  desa VARCHAR(255),
  pekerjaan VARCHAR(255),
  status_perkawinan VARCHAR(50),
  kewarganegaraan VARCHAR(50),
  nama_ayah VARCHAR(255),
  nama_ibu VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS letter_templates (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(50) NOT NULL UNIQUE,
  name VARCHAR(255) NOT NULL,
  content TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS letter_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  no_request VARCHAR(100) UNIQUE,
  resident_id INT,
  resident_nik VARCHAR(50),
  resident_name VARCHAR(255),
  template_id INT,
  requested_at DATETIME,
  status ENUM('pending','verifikasi','approved','rejected','processing','finished') DEFAULT 'pending',
  attachments TEXT,
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (resident_id) REFERENCES residents(id) ON DELETE SET NULL,
  FOREIGN KEY (template_id) REFERENCES letter_templates(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS issued_letters (
  id INT AUTO_INCREMENT PRIMARY KEY,
  letter_no VARCHAR(100) UNIQUE,
  request_id INT,
  template_id INT,
  generated_pdf VARCHAR(255),
  qr_code VARCHAR(255),
  issued_at DATETIME,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (request_id) REFERENCES letter_requests(id) ON DELETE SET NULL,
  FOREIGN KEY (template_id) REFERENCES letter_templates(id) ON DELETE SET NULL
);