-- =====================================================
-- SUPABASE DATABASE SCHEMA V2 FOR SiCakap MOBILE
-- Updated based on Figma design prototype
-- Date: November 4, 2025
-- =====================================================

-- Enable extensions
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pgcrypto";

-- =====================================================
-- TABLE: residents (Data Penduduk)
-- =====================================================
CREATE TABLE residents (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  auth_user_id UUID UNIQUE, -- Link to Supabase Auth
  nik VARCHAR(16) UNIQUE NOT NULL,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) UNIQUE,
  phone VARCHAR(20),
  photo_url TEXT,
  tempat_lahir VARCHAR(255),
  tanggal_lahir DATE,
  jenis_kelamin VARCHAR(1) CHECK (jenis_kelamin IN ('L', 'P')),
  agama VARCHAR(50),
  alamat TEXT,
  rt VARCHAR(10),
  rw VARCHAR(10),
  desa VARCHAR(255),
  kecamatan VARCHAR(255),
  kabupaten VARCHAR(255),
  provinsi VARCHAR(255),
  pekerjaan VARCHAR(255),
  status_perkawinan VARCHAR(50),
  kewarganegaraan VARCHAR(50) DEFAULT 'WNI',
  nama_ayah VARCHAR(255),
  nama_ibu VARCHAR(255),
  is_active BOOLEAN DEFAULT true,
  created_at TIMESTAMPTZ DEFAULT NOW(),
  updated_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX idx_residents_nik ON residents(nik);
CREATE INDEX idx_residents_name ON residents(name);
CREATE INDEX idx_residents_email ON residents(email);
CREATE INDEX idx_residents_auth_user ON residents(auth_user_id);

-- =====================================================
-- TABLE: letter_templates (Template Surat/Dokumen)
-- Untuk screen: Pengajuan (pilih jenis dokumen)
-- =====================================================
CREATE TABLE letter_templates (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  code VARCHAR(50) UNIQUE NOT NULL,
  name VARCHAR(255) NOT NULL,
  description TEXT,
  icon VARCHAR(50), -- Icon name untuk mobile UI
  category VARCHAR(100), -- Identitas, Surat Keterangan, dll
  required_fields JSONB, -- Fields yang wajib diisi
  required_documents JSONB, -- Dokumen yang harus diupload (array)
  processing_time_days INTEGER DEFAULT 3,
  price DECIMAL(10,2) DEFAULT 0,
  is_active BOOLEAN DEFAULT true,
  sort_order INTEGER DEFAULT 0,
  created_at TIMESTAMPTZ DEFAULT NOW(),
  updated_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX idx_templates_category ON letter_templates(category);
CREATE INDEX idx_templates_active ON letter_templates(is_active);

-- =====================================================
-- TABLE: letter_requests (Pengajuan Dokumen/Surat)
-- Untuk screen: Pengajuan Form, Riwayat, Detail Dokumen, Antrian
-- =====================================================
CREATE TABLE letter_requests (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  request_number VARCHAR(50) UNIQUE NOT NULL, -- Auto-generated: REQ-YYYYMMDD-0001
  resident_id UUID NOT NULL REFERENCES residents(id) ON DELETE CASCADE,
  template_id UUID NOT NULL REFERENCES letter_templates(id),
  
  -- Data dari form pengajuan
  form_data JSONB, -- Data dinamis sesuai template
  
  -- Status tracking (untuk badge di Riwayat screen)
  status VARCHAR(50) DEFAULT 'pending' CHECK (status IN (
    'pending',      -- Baru diajukan (badge kuning)
    'verified',     -- Sudah diverifikasi (badge biru)
    'processing',   -- Sedang diproses (badge biru)
    'ready',        -- Siap diambil (badge hijau)
    'completed',    -- Selesai (badge hijau)
    'rejected'      -- Ditolak (badge merah)
  )),
  
  -- Timestamps
  submitted_at TIMESTAMPTZ DEFAULT NOW(),
  verified_at TIMESTAMPTZ,
  processed_at TIMESTAMPTZ,
  ready_at TIMESTAMPTZ,
  completed_at TIMESTAMPTZ,
  
  -- Processing info
  verified_by UUID REFERENCES residents(id),
  processed_by UUID REFERENCES residents(id),
  notes TEXT, -- Catatan dari user
  admin_notes TEXT, -- Catatan dari admin
  rejection_reason TEXT,
  
  -- Queue system (untuk screen Antrian)
  queue_number INTEGER, -- Nomor antrian: 021
  queue_date DATE,
  
  created_at TIMESTAMPTZ DEFAULT NOW(),
  updated_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX idx_requests_resident ON letter_requests(resident_id);
CREATE INDEX idx_requests_template ON letter_requests(template_id);
CREATE INDEX idx_requests_status ON letter_requests(status);
CREATE INDEX idx_requests_queue ON letter_requests(queue_date, queue_number);
CREATE INDEX idx_requests_number ON letter_requests(request_number);

-- =====================================================
-- TABLE: request_documents (Dokumen Lampiran Pengajuan)
-- Upload file di form Pengajuan
-- =====================================================
CREATE TABLE request_documents (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  request_id UUID NOT NULL REFERENCES letter_requests(id) ON DELETE CASCADE,
  document_type VARCHAR(100) NOT NULL, -- KTP, KK, Foto, dll
  file_name VARCHAR(255) NOT NULL,
  file_url TEXT NOT NULL, -- URL di Supabase Storage
  file_size INTEGER, -- dalam bytes
  mime_type VARCHAR(100),
  uploaded_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX idx_documents_request ON request_documents(request_id);

-- =====================================================
-- TABLE: issued_letters (Surat/Dokumen yang Sudah Jadi)
-- Download PDF dari Detail Dokumen screen
-- =====================================================
CREATE TABLE issued_letters (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  request_id UUID NOT NULL REFERENCES letter_requests(id),
  letter_number VARCHAR(100) UNIQUE NOT NULL, -- Nomor surat resmi
  pdf_url TEXT, -- URL file PDF di storage
  issued_at TIMESTAMPTZ DEFAULT NOW(),
  issued_by UUID REFERENCES residents(id), -- Admin yang menerbitkan
  valid_until DATE, -- Masa berlaku surat
  is_printed BOOLEAN DEFAULT false,
  printed_at TIMESTAMPTZ,
  created_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX idx_issued_request ON issued_letters(request_id);
CREATE INDEX idx_issued_number ON issued_letters(letter_number);

-- =====================================================
-- TABLE: news (Berita/Informasi untuk Dashboard)
-- Card berita di Dashboard screen
-- =====================================================
CREATE TABLE news (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  title VARCHAR(500) NOT NULL,
  content TEXT NOT NULL,
  excerpt TEXT, -- Ringkasan untuk card
  image_url TEXT,
  category VARCHAR(100), -- berita, pengumuman, info
  author_id UUID REFERENCES residents(id),
  is_published BOOLEAN DEFAULT false,
  published_at TIMESTAMPTZ,
  view_count INTEGER DEFAULT 0,
  created_at TIMESTAMPTZ DEFAULT NOW(),
  updated_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX idx_news_published ON news(is_published, published_at DESC);
CREATE INDEX idx_news_category ON news(category);

-- =====================================================
-- TABLE: problem_reports (Pelaporan Masalah)
-- Screen: Pelaporan Masalah dengan kategori dropdown
-- =====================================================
CREATE TABLE problem_reports (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  report_number VARCHAR(50) UNIQUE NOT NULL, -- Auto: RPT-YYYYMMDD-0001
  resident_id UUID NOT NULL REFERENCES residents(id) ON DELETE CASCADE,
  
  -- Data laporan
  category VARCHAR(100) NOT NULL, -- Pelayanan, Infrastruktur, Keamanan, Kesehatan, Lingkungan
  title VARCHAR(500) NOT NULL,
  description TEXT NOT NULL,
  location TEXT,
  
  -- Status
  status VARCHAR(50) DEFAULT 'submitted' CHECK (status IN (
    'submitted',   -- Baru dilaporkan
    'reviewed',    -- Sedang ditinjau
    'in_progress', -- Sedang ditangani
    'resolved',    -- Selesai
    'closed'       -- Ditutup
  )),
  
  -- Priority
  priority VARCHAR(20) DEFAULT 'normal' CHECK (priority IN ('low', 'normal', 'high', 'urgent')),
  
  -- Tracking
  submitted_at TIMESTAMPTZ DEFAULT NOW(),
  reviewed_at TIMESTAMPTZ,
  resolved_at TIMESTAMPTZ,
  reviewed_by UUID REFERENCES residents(id),
  admin_response TEXT,
  
  created_at TIMESTAMPTZ DEFAULT NOW(),
  updated_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX idx_reports_resident ON problem_reports(resident_id);
CREATE INDEX idx_reports_status ON problem_reports(status);
CREATE INDEX idx_reports_category ON problem_reports(category);
CREATE INDEX idx_reports_priority ON problem_reports(priority);

-- =====================================================
-- TABLE: report_attachments (Lampiran Laporan Masalah)
-- Upload foto/video untuk laporan
-- =====================================================
CREATE TABLE report_attachments (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  report_id UUID NOT NULL REFERENCES problem_reports(id) ON DELETE CASCADE,
  file_name VARCHAR(255) NOT NULL,
  file_url TEXT NOT NULL,
  file_type VARCHAR(50), -- image, video, document
  mime_type VARCHAR(100),
  uploaded_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX idx_attachments_report ON report_attachments(report_id);

-- =====================================================
-- TABLE: help_articles (Artikel Bantuan/FAQ)
-- Screen: Bantuan dengan search dan kategori
-- =====================================================
CREATE TABLE help_articles (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  category VARCHAR(100) NOT NULL, -- Cara Pengajuan, Syarat Dokumen, FAQ Umum
  title VARCHAR(500) NOT NULL,
  content TEXT NOT NULL,
  steps JSONB, -- Array of step-by-step instructions dengan langka 1-6
  related_template_id UUID REFERENCES letter_templates(id),
  search_keywords TEXT[], -- Keywords untuk search
  view_count INTEGER DEFAULT 0,
  is_published BOOLEAN DEFAULT true,
  sort_order INTEGER DEFAULT 0,
  created_at TIMESTAMPTZ DEFAULT NOW(),
  updated_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX idx_help_category ON help_articles(category);
CREATE INDEX idx_help_published ON help_articles(is_published);
CREATE INDEX idx_help_keywords ON help_articles USING GIN(search_keywords);

-- =====================================================
-- TABLE: notifications (Notifikasi untuk User)
-- Notifikasi badge di top bar
-- =====================================================
CREATE TABLE notifications (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  user_id UUID NOT NULL REFERENCES residents(id) ON DELETE CASCADE,
  title VARCHAR(255) NOT NULL,
  message TEXT NOT NULL,
  type VARCHAR(50) NOT NULL, -- request_update, letter_ready, news, report_update
  related_type VARCHAR(50), -- letter_request, news, problem_report
  related_id UUID, -- ID dari entity terkait
  is_read BOOLEAN DEFAULT false,
  read_at TIMESTAMPTZ,
  created_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX idx_notifications_user ON notifications(user_id, created_at DESC);
CREATE INDEX idx_notifications_read ON notifications(user_id, is_read);

-- =====================================================
-- TABLE: fcm_tokens (Firebase Cloud Messaging)
-- Push notification tokens
-- =====================================================
CREATE TABLE fcm_tokens (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  user_id UUID NOT NULL REFERENCES residents(id) ON DELETE CASCADE,
  token TEXT NOT NULL,
  device_type VARCHAR(20), -- android, ios
  device_id VARCHAR(255),
  is_active BOOLEAN DEFAULT true,
  last_used_at TIMESTAMPTZ DEFAULT NOW(),
  created_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX idx_fcm_user ON fcm_tokens(user_id);
CREATE INDEX idx_fcm_active ON fcm_tokens(is_active);
CREATE UNIQUE INDEX idx_fcm_token ON fcm_tokens(token);

-- =====================================================
-- TABLE: app_settings (Pengaturan Aplikasi)
-- Queue system, jam operasional, kontak
-- =====================================================
CREATE TABLE app_settings (
  key VARCHAR(100) PRIMARY KEY,
  value JSONB NOT NULL,
  description TEXT,
  updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- =====================================================
-- TABLE: admin_users (User Admin untuk Web Panel)
-- =====================================================
CREATE TABLE admin_users (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  resident_id UUID REFERENCES residents(id),
  username VARCHAR(100) UNIQUE NOT NULL,
  email VARCHAR(255) UNIQUE NOT NULL,
  password_hash TEXT NOT NULL,
  role VARCHAR(50) DEFAULT 'admin' CHECK (role IN ('super_admin', 'admin', 'operator')),
  is_active BOOLEAN DEFAULT true,
  last_login_at TIMESTAMPTZ,
  created_at TIMESTAMPTZ DEFAULT NOW(),
  updated_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX idx_admin_username ON admin_users(username);
CREATE INDEX idx_admin_email ON admin_users(email);

-- =====================================================
-- FUNCTIONS
-- =====================================================

-- Auto update updated_at timestamp
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
  NEW.updated_at = NOW();
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Generate request number: REQ-YYYYMMDD-0001
CREATE OR REPLACE FUNCTION generate_request_number()
RETURNS TEXT AS $$
DECLARE
  new_number TEXT;
  counter INTEGER;
BEGIN
  SELECT COUNT(*) + 1 INTO counter
  FROM letter_requests
  WHERE submitted_at::date = CURRENT_DATE;
  
  new_number := 'REQ-' || TO_CHAR(CURRENT_DATE, 'YYYYMMDD') || '-' || LPAD(counter::TEXT, 4, '0');
  RETURN new_number;
END;
$$ LANGUAGE plpgsql;

-- Generate report number: RPT-YYYYMMDD-0001
CREATE OR REPLACE FUNCTION generate_report_number()
RETURNS TEXT AS $$
DECLARE
  new_number TEXT;
  counter INTEGER;
BEGIN
  SELECT COUNT(*) + 1 INTO counter
  FROM problem_reports
  WHERE submitted_at::date = CURRENT_DATE;
  
  new_number := 'RPT-' || TO_CHAR(CURRENT_DATE, 'YYYYMMDD') || '-' || LPAD(counter::TEXT, 4, '0');
  RETURN new_number;
END;
$$ LANGUAGE plpgsql;

-- =====================================================
-- TRIGGERS
-- =====================================================

-- Trigger untuk auto-update updated_at
CREATE TRIGGER update_residents_updated_at BEFORE UPDATE ON residents
  FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_templates_updated_at BEFORE UPDATE ON letter_templates
  FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_requests_updated_at BEFORE UPDATE ON letter_requests
  FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_news_updated_at BEFORE UPDATE ON news
  FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_reports_updated_at BEFORE UPDATE ON problem_reports
  FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_help_updated_at BEFORE UPDATE ON help_articles
  FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Trigger untuk auto-generate request number
CREATE OR REPLACE FUNCTION set_request_number()
RETURNS TRIGGER AS $$
BEGIN
  IF NEW.request_number IS NULL OR NEW.request_number = '' THEN
    NEW.request_number := generate_request_number();
  END IF;
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER generate_request_number_trigger
  BEFORE INSERT ON letter_requests
  FOR EACH ROW EXECUTE FUNCTION set_request_number();

-- Trigger untuk auto-generate report number
CREATE OR REPLACE FUNCTION set_report_number()
RETURNS TRIGGER AS $$
BEGIN
  IF NEW.report_number IS NULL OR NEW.report_number = '' THEN
    NEW.report_number := generate_report_number();
  END IF;
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER generate_report_number_trigger
  BEFORE INSERT ON problem_reports
  FOR EACH ROW EXECUTE FUNCTION set_report_number();

-- =====================================================
-- ROW LEVEL SECURITY (RLS) POLICIES
-- =====================================================

-- Enable RLS
ALTER TABLE residents ENABLE ROW LEVEL SECURITY;
ALTER TABLE letter_requests ENABLE ROW LEVEL SECURITY;
ALTER TABLE request_documents ENABLE ROW LEVEL SECURITY;
ALTER TABLE issued_letters ENABLE ROW LEVEL SECURITY;
ALTER TABLE problem_reports ENABLE ROW LEVEL SECURITY;
ALTER TABLE report_attachments ENABLE ROW LEVEL SECURITY;
ALTER TABLE notifications ENABLE ROW LEVEL SECURITY;
ALTER TABLE fcm_tokens ENABLE ROW LEVEL SECURITY;

-- Residents: User bisa lihat dan edit data sendiri
CREATE POLICY "Users can view own data" ON residents
  FOR SELECT USING (auth.uid() = auth_user_id);

CREATE POLICY "Users can update own data" ON residents
  FOR UPDATE USING (auth.uid() = auth_user_id);

CREATE POLICY "Users can insert own data" ON residents
  FOR INSERT WITH CHECK (auth.uid() = auth_user_id);

-- Letter Requests: User bisa CRUD permintaan sendiri
CREATE POLICY "Users can view own requests" ON letter_requests
  FOR SELECT USING (
    resident_id IN (SELECT id FROM residents WHERE auth_user_id = auth.uid())
  );

CREATE POLICY "Users can create own requests" ON letter_requests
  FOR INSERT WITH CHECK (
    resident_id IN (SELECT id FROM residents WHERE auth_user_id = auth.uid())
  );

CREATE POLICY "Users can update own pending requests" ON letter_requests
  FOR UPDATE USING (
    resident_id IN (SELECT id FROM residents WHERE auth_user_id = auth.uid())
    AND status = 'pending'
  );

-- Request Documents: User bisa CRUD dokumen permintaan sendiri
CREATE POLICY "Users can manage own request documents" ON request_documents
  FOR ALL USING (
    request_id IN (
      SELECT id FROM letter_requests 
      WHERE resident_id IN (
        SELECT id FROM residents WHERE auth_user_id = auth.uid()
      )
    )
  );

-- Issued Letters: User bisa lihat surat sendiri
CREATE POLICY "Users can view own issued letters" ON issued_letters
  FOR SELECT USING (
    request_id IN (
      SELECT id FROM letter_requests 
      WHERE resident_id IN (
        SELECT id FROM residents WHERE auth_user_id = auth.uid()
      )
    )
  );

-- Problem Reports: User bisa CRUD laporan sendiri
CREATE POLICY "Users can view own reports" ON problem_reports
  FOR SELECT USING (
    resident_id IN (SELECT id FROM residents WHERE auth_user_id = auth.uid())
  );

CREATE POLICY "Users can create reports" ON problem_reports
  FOR INSERT WITH CHECK (
    resident_id IN (SELECT id FROM residents WHERE auth_user_id = auth.uid())
  );

CREATE POLICY "Users can update own reports" ON problem_reports
  FOR UPDATE USING (
    resident_id IN (SELECT id FROM residents WHERE auth_user_id = auth.uid())
    AND status = 'submitted'
  );

-- Report Attachments: User bisa manage attachment laporan sendiri
CREATE POLICY "Users can manage own report attachments" ON report_attachments
  FOR ALL USING (
    report_id IN (
      SELECT id FROM problem_reports 
      WHERE resident_id IN (
        SELECT id FROM residents WHERE auth_user_id = auth.uid()
      )
    )
  );

-- Notifications: User hanya bisa lihat notifikasi sendiri
CREATE POLICY "Users can view own notifications" ON notifications
  FOR SELECT USING (
    user_id IN (SELECT id FROM residents WHERE auth_user_id = auth.uid())
  );

CREATE POLICY "Users can update own notifications" ON notifications
  FOR UPDATE USING (
    user_id IN (SELECT id FROM residents WHERE auth_user_id = auth.uid())
  );

-- FCM Tokens: User hanya bisa manage token sendiri
CREATE POLICY "Users can manage own tokens" ON fcm_tokens
  FOR ALL USING (
    user_id IN (SELECT id FROM residents WHERE auth_user_id = auth.uid())
  );

-- Public read untuk templates, news, help articles
ALTER TABLE letter_templates ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Templates are viewable by everyone" ON letter_templates
  FOR SELECT USING (is_active = true);

ALTER TABLE news ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Published news are viewable by everyone" ON news
  FOR SELECT USING (is_published = true);

ALTER TABLE help_articles ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Published help articles are viewable by everyone" ON help_articles
  FOR SELECT USING (is_published = true);

ALTER TABLE app_settings ENABLE ROW LEVEL SECURITY;
CREATE POLICY "App settings are viewable by everyone" ON app_settings
  FOR SELECT USING (true);

-- =====================================================
-- VIEWS
-- =====================================================

-- View untuk dashboard statistics
CREATE OR REPLACE VIEW vw_user_dashboard_stats AS
SELECT 
  r.id as user_id,
  r.name,
  r.nik,
  COUNT(DISTINCT lr.id) FILTER (WHERE lr.status = 'pending') as pending_requests,
  COUNT(DISTINCT lr.id) FILTER (WHERE lr.status IN ('verified', 'processing')) as processing_requests,
  COUNT(DISTINCT lr.id) FILTER (WHERE lr.status = 'ready') as ready_requests,
  COUNT(DISTINCT lr.id) FILTER (WHERE lr.status = 'completed') as completed_requests,
  COUNT(DISTINCT pr.id) FILTER (WHERE pr.status NOT IN ('resolved', 'closed')) as active_reports,
  COUNT(DISTINCT n.id) FILTER (WHERE n.is_read = false) as unread_notifications
FROM residents r
LEFT JOIN letter_requests lr ON r.id = lr.resident_id
LEFT JOIN problem_reports pr ON r.id = pr.resident_id
LEFT JOIN notifications n ON r.id = n.user_id
GROUP BY r.id, r.name, r.nik;

-- View untuk request detail dengan info lengkap
CREATE OR REPLACE VIEW vw_request_details AS
SELECT 
  lr.*,
  r.name as resident_name,
  r.nik as resident_nik,
  r.phone as resident_phone,
  r.email as resident_email,
  lt.name as template_name,
  lt.category as template_category,
  lt.icon as template_icon,
  lt.processing_time_days,
  il.letter_number,
  il.pdf_url,
  il.issued_at,
  COALESCE(
    json_agg(
      json_build_object(
        'id', rd.id,
        'type', rd.document_type,
        'url', rd.file_url,
        'name', rd.file_name,
        'size', rd.file_size
      ) ORDER BY rd.uploaded_at
    ) FILTER (WHERE rd.id IS NOT NULL),
    '[]'::json
  ) as documents
FROM letter_requests lr
JOIN residents r ON lr.resident_id = r.id
JOIN letter_templates lt ON lr.template_id = lt.id
LEFT JOIN issued_letters il ON lr.id = il.request_id
LEFT JOIN request_documents rd ON lr.id = rd.request_id
GROUP BY lr.id, r.name, r.nik, r.phone, r.email, lt.name, lt.category, 
         lt.icon, lt.processing_time_days, il.letter_number, il.pdf_url, il.issued_at;

-- =====================================================
-- SAMPLE DATA
-- =====================================================

-- Insert template surat/dokumen (sesuai Figma)
INSERT INTO letter_templates (code, name, description, icon, category, required_documents, processing_time_days, price) VALUES
('KTP', 'Pengajuan KTP', 'Kartu Tanda Penduduk elektronik - Persyaratan: KK, Akta Lahir, Foto 4x6', 'card', 'Identitas', 
 '["KK", "Akta Lahir", "Foto 4x6"]'::jsonb, 7, 0),
('KK', 'Pengajuan KK', 'Kartu Keluarga - Persyaratan: KTP, Akta Nikah, Akta Lahir Anak', 'family', 'Identitas', 
 '["KTP", "Akta Nikah", "Akta Lahir Anak"]'::jsonb, 5, 0),
('SKCK', 'Surat Keterangan Catatan Kepolisian', 'SKCK untuk berbagai keperluan (pengantar dari desa)', 'security', 'Surat Keterangan', 
 '["KTP", "KK", "Pas Foto 4x6"]'::jsonb, 3, 0),
('SKTM', 'Surat Keterangan Tidak Mampu', 'Untuk kebutuhan beasiswa, pengobatan, atau bantuan sosial', 'document', 'Surat Keterangan', 
 '["KTP", "KK"]'::jsonb, 2, 0),
('DOMISILI', 'Surat Keterangan Domisili', 'Keterangan tempat tinggal untuk keperluan administrasi', 'home', 'Surat Keterangan', 
 '["KTP", "KK"]'::jsonb, 2, 0),
('USAHA', 'Surat Keterangan Usaha', 'Untuk keperluan perizinan dan administrasi usaha', 'business', 'Surat Keterangan', 
 '["KTP", "KK", "Foto Tempat Usaha"]'::jsonb, 3, 0);

-- Insert help articles/tutorial (sesuai Figma: langkah 1-6)
INSERT INTO help_articles (category, title, content, steps, sort_order, search_keywords) VALUES
('Panduan', 'Cara Pengajuan KTP', 'Panduan lengkap mengajukan KTP elektronik melalui aplikasi SiCakap',
 '[
   {"step": 1, "title": "Login ke Aplikasi", "description": "Masuk menggunakan NIK dan password yang sudah terdaftar"},
   {"step": 2, "title": "Pilih Menu Pengajuan", "description": "Pada dashboard, klik menu Pengajuan di bagian bawah"},
   {"step": 3, "title": "Pilih Jenis Dokumen", "description": "Pilih Pengajuan KTP dari daftar dokumen yang tersedia"},
   {"step": 4, "title": "Isi Formulir", "description": "Lengkapi semua data yang diminta dengan benar"},
   {"step": 5, "title": "Upload Dokumen", "description": "Upload foto KK, Akta Lahir, dan pas foto 4x6 sesuai persyaratan"},
   {"step": 6, "title": "Submit Pengajuan", "description": "Klik tombol Ajukan Pengajuan dan tunggu konfirmasi"}
 ]'::jsonb, 1, ARRAY['ktp', 'pengajuan', 'identitas', 'e-ktp']),
 
('Panduan', 'Cara Cek Status Pengajuan', 'Melihat progress dan status pengajuan dokumen Anda',
 '[
   {"step": 1, "title": "Buka Menu Riwayat", "description": "Klik icon Riwayat di navigation bar bawah"},
   {"step": 2, "title": "Lihat Daftar Pengajuan", "description": "Semua pengajuan Anda akan muncul dengan status terkini"},
   {"step": 3, "title": "Cek Detail", "description": "Tap pada pengajuan untuk melihat detail lengkap dan timeline proses"}
 ]'::jsonb, 2, ARRAY['riwayat', 'status', 'cek', 'tracking']),

('Panduan', 'Cara Mengambil Antrian', 'Ambil nomor antrian untuk pengambilan dokumen',
 '[
   {"step": 1, "title": "Cek Status Dokumen", "description": "Pastikan status pengajuan sudah Siap Diambil"},
   {"step": 2, "title": "Buka Menu Antrian", "description": "Klik menu Antrian di navigation bar"},
   {"step": 3, "title": "Lihat Nomor Antrian", "description": "Nomor antrian Anda akan ditampilkan"},
   {"step": 4, "title": "Datang ke Kantor", "description": "Datang sesuai jadwal dengan membawa nomor antrian"}
 ]'::jsonb, 3, ARRAY['antrian', 'ambil', 'nomor', 'queue']),

('FAQ', 'Berapa Lama Proses Pengajuan?', 'Estimasi waktu proses untuk setiap jenis dokumen:\n\n- KTP: 7 hari kerja\n- KK: 5 hari kerja\n- SKCK: 3 hari kerja\n- Surat Keterangan: 2-3 hari kerja\n\nWaktu dapat berbeda tergantung kelengkapan dokumen.',
 NULL, 4, ARRAY['waktu', 'berapa lama', 'proses', 'estimasi']),

('FAQ', 'Dokumen Apa Saja yang Diperlukan?', 'Persyaratan dokumen untuk pengajuan:\n\n**KTP:**\n- Kartu Keluarga\n- Akta Lahir\n- Pas foto 4x6\n\n**SKTM:**\n- KTP\n- Kartu Keluarga\n\n**Surat Domisili:**\n- KTP\n- Kartu Keluarga',
 NULL, 5, ARRAY['dokumen', 'persyaratan', 'syarat', 'upload']);

-- Insert app settings
INSERT INTO app_settings (key, value, description) VALUES
('queue_enabled', '{"enabled": true}'::jsonb, 'Enable/disable sistem antrian'),
('working_hours', '{"start": "08:00", "end": "16:00", "days": ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"]}'::jsonb, 'Jam operasional kantor'),
('max_queue_per_day', '{"max": 100}'::jsonb, 'Maximum antrian per hari'),
('contact_info', '{"phone": "021-12345678", "email": "admin@sicakap.go.id", "address": "Jl. Sicakap No. 123", "whatsapp": "628123456789"}'::jsonb, 'Informasi kontak'),
('report_categories', '{"categories": ["Pelayanan", "Infrastruktur", "Keamanan", "Kesehatan", "Lingkungan", "Lainnya"]}'::jsonb, 'Kategori pelaporan masalah');

-- Insert sample admin user (password: admin123)
INSERT INTO admin_users (username, email, password_hash, role) VALUES
('admin', 'admin@sicakap.go.id', '$2a$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', 'super_admin');

-- Insert sample news (untuk dashboard)
INSERT INTO news (title, content, excerpt, category, is_published, published_at) VALUES
('Layanan Administrasi Kini Lebih Mudah', 
 'Pemerintah desa menghadirkan aplikasi SiCakap untuk memudahkan warga dalam mengurus berbagai keperluan administrasi. Tidak perlu lagi antre panjang, cukup ajukan melalui aplikasi.',
 'Aplikasi SiCakap memudahkan pengurusan administrasi desa',
 'pengumuman', true, NOW()),
 
('Jadwal Pelayanan Libur Lebaran', 
 'Pelayanan administrasi akan libur pada tanggal 10-17 April 2025 dalam rangka libur Idul Fitri. Pengajuan tetap dapat dilakukan melalui aplikasi.',
 'Info jadwal pelayanan saat libur lebaran',
 'info', true, NOW());

-- =====================================================
-- INDEXES FOR PERFORMANCE
-- =====================================================

-- Indexes untuk query optimization (tanpa fungsi DATE)
CREATE INDEX idx_requests_submitted_date ON letter_requests(submitted_at);
CREATE INDEX idx_reports_submitted_date ON problem_reports(submitted_at);
CREATE INDEX idx_news_published_date ON news(published_at) WHERE is_published = true;

-- Full text search indexes
CREATE INDEX idx_news_title_search ON news USING gin(to_tsvector('indonesian', title));
CREATE INDEX idx_help_title_search ON help_articles USING gin(to_tsvector('indonesian', title));
CREATE INDEX idx_help_content_search ON help_articles USING gin(to_tsvector('indonesian', content));

-- =====================================================
-- COMMENTS
-- =====================================================
COMMENT ON TABLE residents IS 'Data penduduk/user aplikasi mobile';
COMMENT ON TABLE letter_templates IS 'Template jenis surat/dokumen yang bisa diajukan (screen: Pengajuan)';
COMMENT ON TABLE letter_requests IS 'Data pengajuan surat/dokumen dari user (screen: Pengajuan Form, Riwayat, Detail, Antrian)';
COMMENT ON TABLE request_documents IS 'File lampiran untuk pengajuan (upload di form)';
COMMENT ON TABLE issued_letters IS 'Surat/dokumen yang sudah selesai dibuat (download PDF)';
COMMENT ON TABLE news IS 'Berita/informasi untuk dashboard (card berita)';
COMMENT ON TABLE problem_reports IS 'Laporan masalah dari user (screen: Pelaporan Masalah)';
COMMENT ON TABLE report_attachments IS 'Lampiran foto/video untuk laporan';
COMMENT ON TABLE help_articles IS 'Artikel bantuan/tutorial/FAQ (screen: Bantuan)';
COMMENT ON TABLE notifications IS 'Notifikasi push untuk user (badge notif)';
COMMENT ON TABLE fcm_tokens IS 'Firebase tokens untuk push notification';
COMMENT ON TABLE app_settings IS 'Pengaturan aplikasi (queue, jam operasional, kontak)';

-- =====================================================
-- SUCCESS MESSAGE
-- =====================================================
DO $$
BEGIN
  RAISE NOTICE '✅ Database schema berhasil dibuat!';
  RAISE NOTICE '📱 Sesuai dengan Figma design:';
  RAISE NOTICE '   - Dashboard (news, stats)';
  RAISE NOTICE '   - Pengajuan (templates, form, upload)';
  RAISE NOTICE '   - Riwayat (requests list dengan status badge)';
  RAISE NOTICE '   - Akun (profile)';
  RAISE NOTICE '   - Pelaporan Masalah (reports dengan kategori)';
  RAISE NOTICE '   - Bantuan (help articles dengan search)';
  RAISE NOTICE '   - Antrian (queue system)';
  RAISE NOTICE '   - Notifikasi (push notifications)';
END $$;
