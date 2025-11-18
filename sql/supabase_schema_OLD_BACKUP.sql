-- =====================================================
-- SUPABASE DATABASE SCHEMA FOR SiCakap
-- PostgreSQL Version (converted from MySQL)
-- =====================================================

-- Enable UUID extension
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- =====================================================
-- TABLE: residents (Data Penduduk)
-- =====================================================
CREATE TABLE residents (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  nik VARCHAR(50) UNIQUE NOT NULL,
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
  kewarganegaraan VARCHAR(50) DEFAULT 'WNI',
  nama_ayah VARCHAR(255),
  nama_ibu VARCHAR(255),
  created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Index untuk pencarian cepat
CREATE INDEX idx_residents_nik ON residents(nik);
CREATE INDEX idx_residents_name ON residents(name);

-- =====================================================
-- TABLE: letter_templates (Template Surat)
-- =====================================================
CREATE TABLE letter_templates (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  code VARCHAR(50) UNIQUE NOT NULL,
  name VARCHAR(255) NOT NULL,
  description TEXT,
  content TEXT,
  required_fields JSONB, -- Fields yang wajib diisi
  is_active BOOLEAN DEFAULT true,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- TABLE: letter_requests (Permintaan Surat)
-- =====================================================
CREATE TABLE letter_requests (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  no_request VARCHAR(100) UNIQUE,
  resident_id UUID REFERENCES residents(id),
  resident_nik VARCHAR(50) NOT NULL,
  resident_name VARCHAR(255) NOT NULL,
  template_id UUID REFERENCES letter_templates(id),
  requested_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
  status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'verifikasi', 'approved', 'rejected', 'processing', 'finished')),
  attachments JSONB, -- Array of file URLs
  notes TEXT,
  admin_notes TEXT, -- Catatan dari admin
  processed_by UUID, -- Admin yang memproses
  created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Index untuk query
CREATE INDEX idx_requests_resident ON letter_requests(resident_id);
CREATE INDEX idx_requests_status ON letter_requests(status);
CREATE INDEX idx_requests_date ON letter_requests(requested_at);

-- =====================================================
-- TABLE: issued_letters (Surat yang Sudah Diterbitkan)
-- =====================================================
CREATE TABLE issued_letters (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  letter_no VARCHAR(100) UNIQUE,
  request_id UUID REFERENCES letter_requests(id),
  template_id UUID REFERENCES letter_templates(id),
  generated_pdf VARCHAR(255), -- URL to PDF file
  qr_code VARCHAR(255), -- URL to QR code image
  issued_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
  issued_by UUID, -- Admin yang menerbitkan
  created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_issued_letters_request ON issued_letters(request_id);
CREATE INDEX idx_issued_letters_date ON issued_letters(issued_at);

-- =====================================================
-- TABLE: users (Admin Users)
-- =====================================================
CREATE TABLE users (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  email VARCHAR(255) UNIQUE NOT NULL,
  name VARCHAR(255),
  password_hash VARCHAR(255), -- For local auth (optional if using Supabase Auth)
  role VARCHAR(20) DEFAULT 'admin' CHECK (role IN ('super_admin', 'admin', 'operator')),
  is_active BOOLEAN DEFAULT true,
  last_login TIMESTAMP WITH TIME ZONE,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- TABLE: notifications (Push Notifications for Mobile)
-- =====================================================
CREATE TABLE notifications (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  resident_id UUID REFERENCES residents(id),
  request_id UUID REFERENCES letter_requests(id),
  title VARCHAR(255) NOT NULL,
  message TEXT NOT NULL,
  type VARCHAR(50), -- 'request_approved', 'letter_ready', etc.
  is_read BOOLEAN DEFAULT false,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_notifications_resident ON notifications(resident_id);
CREATE INDEX idx_notifications_read ON notifications(is_read);

-- =====================================================
-- TABLE: fcm_tokens (Firebase Cloud Messaging Tokens)
-- =====================================================
CREATE TABLE fcm_tokens (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  resident_id UUID REFERENCES residents(id),
  token TEXT NOT NULL,
  device_type VARCHAR(20), -- 'android', 'ios'
  is_active BOOLEAN DEFAULT true,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_fcm_tokens_resident ON fcm_tokens(resident_id);

-- =====================================================
-- FUNCTIONS: Auto-update updated_at timestamp
-- =====================================================
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
  NEW.updated_at = CURRENT_TIMESTAMP;
  RETURN NEW;
END;
$$ language 'plpgsql';

-- Apply trigger to tables
CREATE TRIGGER update_residents_updated_at BEFORE UPDATE ON residents
  FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_letter_templates_updated_at BEFORE UPDATE ON letter_templates
  FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_letter_requests_updated_at BEFORE UPDATE ON letter_requests
  FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_users_updated_at BEFORE UPDATE ON users
  FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- =====================================================
-- ROW LEVEL SECURITY (RLS) POLICIES
-- =====================================================

-- Enable RLS on all tables
ALTER TABLE residents ENABLE ROW LEVEL SECURITY;
ALTER TABLE letter_templates ENABLE ROW LEVEL SECURITY;
ALTER TABLE letter_requests ENABLE ROW LEVEL SECURITY;
ALTER TABLE issued_letters ENABLE ROW LEVEL SECURITY;
ALTER TABLE notifications ENABLE ROW LEVEL SECURITY;
ALTER TABLE fcm_tokens ENABLE ROW LEVEL SECURITY;

-- Residents: Users can only see their own data
CREATE POLICY "Residents can view own data"
  ON residents FOR SELECT
  USING (auth.uid()::text = id::text);

-- Admins can see all residents
CREATE POLICY "Admins can view all residents"
  ON residents FOR ALL
  USING (
    EXISTS (
      SELECT 1 FROM users 
      WHERE id = auth.uid() 
      AND role IN ('admin', 'super_admin')
    )
  );

-- Letter requests: Users can see own requests
CREATE POLICY "Users can view own requests"
  ON letter_requests FOR SELECT
  USING (auth.uid()::text = resident_id::text);

-- Users can create requests
CREATE POLICY "Users can create requests"
  ON letter_requests FOR INSERT
  WITH CHECK (auth.uid()::text = resident_id::text);

-- Admins can manage all requests
CREATE POLICY "Admins can manage all requests"
  ON letter_requests FOR ALL
  USING (
    EXISTS (
      SELECT 1 FROM users 
      WHERE id = auth.uid() 
      AND role IN ('admin', 'super_admin')
    )
  );

-- Templates: Public can read active templates
CREATE POLICY "Anyone can view active templates"
  ON letter_templates FOR SELECT
  USING (is_active = true);

-- Notifications: Users can see own notifications
CREATE POLICY "Users can view own notifications"
  ON notifications FOR SELECT
  USING (auth.uid()::text = resident_id::text);

-- =====================================================
-- SAMPLE DATA
-- =====================================================

-- Insert sample letter templates
INSERT INTO letter_templates (code, name, description) VALUES
  ('SKT', 'Surat Keterangan', 'Surat keterangan umum dari desa'),
  ('SKCK', 'Surat Keterangan Catatan Kepolisian', 'Pengantar untuk membuat SKCK'),
  ('SKDU', 'Surat Keterangan Domisili Usaha', 'Keterangan domisili usaha'),
  ('SKU', 'Surat Keterangan Usaha', 'Keterangan untuk usaha/bisnis'),
  ('SKTM', 'Surat Keterangan Tidak Mampu', 'Keterangan untuk keluarga tidak mampu');

-- Insert sample admin user
INSERT INTO users (email, name, role, password_hash) VALUES
  ('admin@sicakap.com', 'Administrator', 'super_admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- =====================================================
-- VIEWS (untuk query yang sering dipakai)
-- =====================================================

-- View: Request dengan detail lengkap
CREATE VIEW vw_requests_detail AS
SELECT 
  lr.*,
  r.name as resident_full_name,
  r.alamat,
  r.rt,
  r.rw,
  r.desa,
  lt.name as template_name,
  lt.code as template_code,
  il.letter_no,
  il.generated_pdf
FROM letter_requests lr
LEFT JOIN residents r ON lr.resident_id = r.id
LEFT JOIN letter_templates lt ON lr.template_id = lt.id
LEFT JOIN issued_letters il ON lr.id = il.request_id;

-- View: Statistik harian
CREATE VIEW vw_daily_statistics AS
SELECT 
  DATE(requested_at) as date,
  COUNT(*) as total_requests,
  COUNT(*) FILTER (WHERE status = 'pending') as pending,
  COUNT(*) FILTER (WHERE status = 'finished') as finished
FROM letter_requests
WHERE requested_at >= CURRENT_DATE - INTERVAL '30 days'
GROUP BY DATE(requested_at)
ORDER BY date DESC;

-- =====================================================
-- COMMENTS
-- =====================================================
COMMENT ON TABLE residents IS 'Data penduduk desa';
COMMENT ON TABLE letter_templates IS 'Template jenis surat yang tersedia';
COMMENT ON TABLE letter_requests IS 'Permintaan surat dari warga';
COMMENT ON TABLE issued_letters IS 'Surat yang sudah diterbitkan';
COMMENT ON TABLE notifications IS 'Notifikasi untuk aplikasi mobile';
