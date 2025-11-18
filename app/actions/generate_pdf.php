<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../functions.php';
if (!is_logged_in()) exit('Unauthorized');

$pdo = db();
$request_id = isset($_GET['request_id']) ? intval($_GET['request_id']) : 0;

if ($request_id <= 0) {
    flash_set('ID permintaan tidak valid');
    header('Location: ../?p=requests');
    exit;
}

try {
    // Get request data with resident info
    $sql = "SELECT lr.*, 
                   r.name as resident_full_name, r.nik, r.alamat, r.rt, r.rw, r.desa,
                   r.tempat_lahir, r.tanggal_lahir, r.jenis_kelamin, r.agama,
                   r.pekerjaan, r.status_perkawinan, r.kewarganegaraan,
                   r.nama_ayah, r.nama_ibu,
                   lt.name as template_name, lt.code as template_code, lt.content
            FROM letter_requests lr
            LEFT JOIN residents r ON lr.resident_id = r.id
            LEFT JOIN letter_templates lt ON lr.template_id = lt.id
            WHERE lr.id = :id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $request_id]);
    $request = $stmt->fetch();
    
    if (!$request) {
        flash_set('Permintaan tidak ditemukan');
        header('Location: ../?p=requests');
        exit;
    }

    // Check if already issued
    $check_sql = "SELECT id, letter_no, generated_pdf FROM issued_letters WHERE request_id = :request_id";
    $stmt = $pdo->prepare($check_sql);
    $stmt->execute([':request_id' => $request_id]);
    $existing = $stmt->fetch();

    if ($existing && $existing['generated_pdf']) {
        // Already generated, just show the PDF
        $pdf_path = __DIR__ . '/../generated/' . $existing['generated_pdf'];
        if (file_exists($pdf_path)) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . $existing['generated_pdf'] . '"');
            readfile($pdf_path);
            exit;
        }
    }

    // Generate letter number if not exists
    if ($existing && $existing['letter_no']) {
        $letter_no = $existing['letter_no'];
    } else {
        $letter_no = generateLetterNumber($pdo, $request['template_code']);
    }

    // Prepare data for PDF
    $data = [
        'letter_no' => $letter_no,
        'issued_date' => date('d F Y'),
        'resident_nik' => $request['nik'] ?? $request['resident_nik'],
        'resident_name' => $request['resident_full_name'] ?? $request['resident_name'],
        'tempat_lahir' => $request['tempat_lahir'] ?? '',
        'tanggal_lahir' => $request['tanggal_lahir'] ? date('d-m-Y', strtotime($request['tanggal_lahir'])) : '',
        'jenis_kelamin' => $request['jenis_kelamin'] ?? '',
        'agama' => $request['agama'] ?? '',
        'pekerjaan' => $request['pekerjaan'] ?? '',
        'status_perkawinan' => $request['status_perkawinan'] ?? '',
        'alamat' => $request['alamat'] ?? '',
        'rt' => $request['rt'] ?? '',
        'rw' => $request['rw'] ?? '',
        'desa' => $request['desa'] ?? '',
        'kewarganegaraan' => $request['kewarganegaraan'] ?? 'WNI',
        'nama_ayah' => $request['nama_ayah'] ?? '',
        'nama_ibu' => $request['nama_ibu'] ?? '',
        'template_name' => $request['template_name'] ?? 'Surat Keterangan',
    ];

    // Generate PDF filename
    $pdf_filename = 'surat_' . $request_id . '_' . time() . '.pdf';
    $pdf_path = __DIR__ . '/../generated/' . $pdf_filename;

    // Ensure directory exists
    if (!is_dir(__DIR__ . '/../generated')) {
        mkdir(__DIR__ . '/../generated', 0755, true);
    }

    // Generate simple PDF (you can use library like TCPDF or FPDF for better output)
    generateSimplePDF($pdf_path, $data);

    // Save to issued_letters table
    if ($existing) {
        // Update existing
        $stmt = $pdo->prepare("UPDATE issued_letters 
                               SET letter_no = :letter_no, 
                                   generated_pdf = :pdf, 
                                   issued_at = NOW() 
                               WHERE id = :id");
        $stmt->execute([
            ':letter_no' => $letter_no,
            ':pdf' => $pdf_filename,
            ':id' => $existing['id']
        ]);
    } else {
        // Insert new
        $stmt = $pdo->prepare("INSERT INTO issued_letters 
                               (letter_no, request_id, template_id, generated_pdf, issued_at) 
                               VALUES (:letter_no, :request_id, :template_id, :pdf, NOW())");
        $stmt->execute([
            ':letter_no' => $letter_no,
            ':request_id' => $request_id,
            ':template_id' => $request['template_id'],
            ':pdf' => $pdf_filename
        ]);
    }

    // Update request status to finished
    $stmt = $pdo->prepare("UPDATE letter_requests SET status = 'finished' WHERE id = :id");
    $stmt->execute([':id' => $request_id]);

    // Display PDF
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . $pdf_filename . '"');
    readfile($pdf_path);
    exit;

} catch (Exception $e) {
    error_log('Error generating PDF: ' . $e->getMessage());
    flash_set('Terjadi kesalahan saat generate PDF: ' . $e->getMessage());
    header('Location: ../?p=request_detail&id=' . $request_id);
    exit;
}

/**
 * Generate letter number with format: 001/KODE/DESA/BULAN/TAHUN
 */
function generateLetterNumber($pdo, $template_code = 'SKT') {
    $month = date('m');
    $year = date('Y');
    $roman_month = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
    
    // Get last number for this month and year
    $sql = "SELECT letter_no FROM issued_letters 
            WHERE MONTH(issued_at) = :month 
            AND YEAR(issued_at) = :year 
            ORDER BY id DESC LIMIT 1";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':month' => $month, ':year' => $year]);
    $last = $stmt->fetch();
    
    $number = 1;
    if ($last && $last['letter_no']) {
        // Extract number from format: 001/SKT/...
        $parts = explode('/', $last['letter_no']);
        if (isset($parts[0]) && is_numeric($parts[0])) {
            $number = intval($parts[0]) + 1;
        }
    }
    
    $code = $template_code ?: 'SKT';
    $month_roman = $roman_month[intval($month) - 1];
    
    return sprintf('%03d/%s/DESA/%s/%d', $number, $code, $month_roman, $year);
}

/**
 * Generate simple PDF with basic layout
 * For production, consider using TCPDF, FPDF, or mPDF library
 */
function generateSimplePDF($filepath, $data) {
    // Simple HTML-based PDF (requires wkhtmltopdf or similar)
    // For now, we'll create a text-based format
    
    $content = "
=============================================================================
                           PEMERINTAH DESA
                        SURAT KETERANGAN
                    Nomor: {$data['letter_no']}
=============================================================================

Yang bertanda tangan di bawah ini Kepala Desa, menerangkan bahwa:

    Nama                : {$data['resident_name']}
    NIK                 : {$data['resident_nik']}
    Tempat/Tgl Lahir    : {$data['tempat_lahir']}, {$data['tanggal_lahir']}
    Jenis Kelamin       : {$data['jenis_kelamin']}
    Agama               : {$data['agama']}
    Pekerjaan           : {$data['pekerjaan']}
    Status Perkawinan   : {$data['status_perkawinan']}
    Kewarganegaraan     : {$data['kewarganegaraan']}
    Alamat              : {$data['alamat']}
                          RT {$data['rt']}/RW {$data['rw']}, {$data['desa']}

Adalah benar penduduk desa kami dan memohon {$data['template_name']}.

Demikian surat keterangan ini dibuat untuk dipergunakan sebagaimana mestinya.

                                                Diterbitkan: {$data['issued_date']}
                                                Kepala Desa,




                                                (___________________)

=============================================================================
    ";

    // For proper PDF generation, you should use a library like TCPDF
    // This is just a temporary solution using plain text
    file_put_contents($filepath, $content);
    
    // TODO: Implement proper PDF generation using TCPDF or similar library
}
