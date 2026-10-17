<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Material;
use App\Models\Question;
use Illuminate\Database\Seeder;

class BankSoalSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->bank() as $code => [$name, $semester, $material, $questions]) {
            $course = Course::updateOrCreate(['code' => $code], ['name' => $name, 'semester' => $semester, 'prodi' => 'SI', 'is_active' => true]);
            $materi = Material::firstOrCreate(['course_id' => $course->id, 'title' => $material]);

            foreach ($questions as [$text, $options, $key]) {
                Question::updateOrCreate(['material_id' => $materi->id, 'question_text' => $text], [
                    'option_a' => $options[0], 'option_b' => $options[1], 'option_c' => $options[2], 'option_d' => $options[3],
                    'correct_answer' => $key, 'status' => Question::STATUS_ACTIVE, 'source' => 'manual',
                ]);
            }
        }
    }

    private function bank(): array
    {
        return [
            'SI101' => ['Pengantar Sistem Informasi', 1, 'Konsep Dasar Sistem Informasi', [
                ['Komponen sistem informasi yang berupa prosedur, kebijakan, dan aturan termasuk ke dalam...', ['Hardware', 'Software', 'Prosedur', 'Data'], 'c'],
                ['Data yang sudah diolah sehingga bermakna bagi penerimanya disebut...', ['Informasi', 'Fakta', 'Record', 'Field'], 'a'],
                ['Sistem yang membantu manajer tingkat menengah dengan laporan rutin terjadwal adalah...', ['TPS', 'MIS', 'ESS', 'Office Automation'], 'b'],
                ['Sistem yang mencatat transaksi harian seperti penjualan di kasir disebut...', ['Decision Support System', 'Executive Support System', 'Expert System', 'Transaction Processing System'], 'd'],
                ['Sistem informasi yang mengintegrasikan proses bisnis seluruh departemen dalam satu basis data adalah...', ['CRM', 'SCM', 'ERP', 'KMS'], 'c'],
                ['Karakteristik informasi yang berarti informasi tersedia saat dibutuhkan adalah...', ['Akurat', 'Tepat waktu', 'Relevan', 'Lengkap'], 'b'],
                ['Sistem yang berfokus pada pengelolaan hubungan dengan pelanggan disebut...', ['CRM', 'ERP', 'HRIS', 'POS'], 'a'],
                ['Contoh keputusan tidak terstruktur yang biasanya dibantu oleh ESS adalah...', ['Memesan ulang stok', 'Menghitung gaji', 'Mencetak faktur', 'Menentukan arah ekspansi perusahaan'], 'd'],
                ['Batas yang memisahkan sistem dengan lingkungan luarnya disebut...', ['Interface', 'Boundary', 'Environment', 'Input'], 'b'],
                ['Bagian sistem informasi yang menghubungkan komponen-komponen lewat jaringan disebut...', ['Brainware', 'Teknologi komunikasi', 'Basis model', 'Output'], 'b'],
            ]],
            'SI102' => ['Basis Data', 1, 'Model Relasional dan SQL', [
                ['Atribut yang nilainya unik dan dipakai untuk mengidentifikasi setiap baris tabel disebut...', ['Foreign key', 'Primary key', 'Candidate key', 'Composite key'], 'b'],
                ['Perintah SQL untuk mengambil data dari tabel adalah...', ['INSERT', 'UPDATE', 'SELECT', 'DELETE'], 'c'],
                ['Bentuk normal yang mensyaratkan tidak ada ketergantungan parsial terhadap primary key adalah...', ['1NF', '2NF', '3NF', 'BCNF'], 'b'],
                ['Pada diagram ER, entitas biasanya digambarkan dengan bentuk...', ['Persegi panjang', 'Belah ketupat', 'Elips', 'Lingkaran ganda'], 'a'],
                ['JOIN yang menampilkan semua baris tabel kiri walaupun tidak ada pasangannya di tabel kanan adalah...', ['INNER JOIN', 'RIGHT JOIN', 'CROSS JOIN', 'LEFT JOIN'], 'd'],
                ['Perintah DDL untuk membuat tabel baru adalah...', ['CREATE TABLE', 'INSERT INTO', 'ALTER VIEW', 'SELECT INTO'], 'a'],
                ['Sifat transaksi ACID yang menjamin transaksi dijalankan seluruhnya atau tidak sama sekali adalah...', ['Consistency', 'Isolation', 'Atomicity', 'Durability'], 'c'],
                ['Klausa SQL untuk menyaring hasil setelah GROUP BY adalah...', ['WHERE', 'ORDER BY', 'LIMIT', 'HAVING'], 'd'],
                ['Relasi satu mahasiswa dapat mengambil banyak matkul dan satu matkul diambil banyak mahasiswa adalah...', ['One to one', 'One to many', 'Many to many', 'Many to one'], 'c'],
                ['Fungsi agregat untuk menghitung jumlah baris adalah...', ['SUM()', 'COUNT()', 'AVG()', 'MAX()'], 'b'],
            ]],
            'SI103' => ['Algoritma dan Pemrograman', 1, 'Dasar Algoritma', [
                ['Kompleksitas waktu pencarian biner (binary search) pada data terurut adalah...', ['O(n)', 'O(n log n)', 'O(log n)', 'O(1)'], 'c'],
                ['Struktur data yang menerapkan prinsip LIFO adalah...', ['Queue', 'Stack', 'Array', 'Linked list'], 'b'],
                ['Struktur data yang menerapkan prinsip FIFO adalah...', ['Queue', 'Stack', 'Tree', 'Graph'], 'a'],
                ['Perulangan yang memeriksa kondisi setelah badan perulangan dijalankan minimal sekali adalah...', ['for', 'while', 'foreach', 'do-while'], 'd'],
                ['Fungsi yang memanggil dirinya sendiri disebut fungsi...', ['Rekursif', 'Iteratif', 'Prosedural', 'Anonim'], 'a'],
                ['Kompleksitas waktu terburuk bubble sort adalah...', ['O(n)', 'O(log n)', 'O(n log n)', 'O(n²)'], 'd'],
                ['Simbol flowchart berbentuk belah ketupat dipakai untuk...', ['Proses', 'Keputusan', 'Input/output', 'Terminal'], 'b'],
                ['Indeks elemen pertama array pada sebagian besar bahasa pemrograman seperti C dan PHP adalah...', ['1', '-1', '0', 'Bebas'], 'c'],
                ['Hasil dari 17 % 5 (modulo) adalah...', ['2', '3', '3,4', '12'], 'a'],
                ['Tipe data yang hanya memiliki dua nilai, benar atau salah, adalah...', ['Integer', 'Char', 'Float', 'Boolean'], 'd'],
            ]],
            'SI104' => ['Matematika Diskrit', 1, 'Logika, Himpunan, dan Graf', [
                ['Nilai kebenaran p → q bernilai salah hanya jika...', ['p salah dan q benar', 'p benar dan q salah', 'p dan q salah', 'p dan q benar'], 'b'],
                ['Jika A = {1, 2, 3}, banyaknya himpunan bagian dari A adalah...', ['3', '6', '8', '9'], 'c'],
                ['Negasi dari "semua mahasiswa hadir" adalah...', ['Ada mahasiswa yang tidak hadir', 'Semua mahasiswa tidak hadir', 'Tidak ada mahasiswa yang hadir', 'Ada mahasiswa yang hadir'], 'a'],
                ['Banyaknya permutasi 3 huruf dari huruf A, B, C, D adalah...', ['4', '12', '64', '24'], 'd'],
                ['Graf yang setiap pasang simpulnya terhubung langsung oleh satu sisi disebut graf...', ['Lengkap', 'Bipartit', 'Berarah', 'Pohon'], 'a'],
                ['Pohon dengan n simpul selalu memiliki sisi sebanyak...', ['n', 'n + 1', 'n - 1', '2n'], 'c'],
                ['Pernyataan majemuk yang selalu bernilai benar untuk semua kemungkinan disebut...', ['Kontradiksi', 'Kontingensi', 'Implikasi', 'Tautologi'], 'd'],
                ['Jumlah derajat seluruh simpul pada graf tak berarah sama dengan...', ['Banyak simpul', 'Dua kali banyak sisi', 'Banyak sisi', 'Banyak simpul dikali sisi'], 'b'],
                ['Hasil A ∩ B jika A = {1, 2, 3, 4} dan B = {3, 4, 5} adalah...', ['{1, 2}', '{1, 2, 3, 4, 5}', '{3, 4}', '{5}'], 'c'],
                ['Nilai C(5, 2) atau kombinasi 2 dari 5 adalah...', ['10', '20', '25', '5'], 'a'],
            ]],
            'SI201' => ['Analisis dan Perancangan Sistem', 2, 'SDLC dan Pemodelan Sistem', [
                ['Tahapan SDLC yang menghasilkan dokumen kebutuhan pengguna adalah...', ['Implementasi', 'Analisis', 'Pemeliharaan', 'Pengujian'], 'b'],
                ['Model pengembangan yang tahapannya berurutan dan tidak kembali ke tahap sebelumnya adalah...', ['Waterfall', 'Prototyping', 'Spiral', 'Scrum'], 'a'],
                ['Diagram UML yang menggambarkan interaksi aktor dengan fungsi sistem adalah...', ['Class diagram', 'Sequence diagram', 'Use case diagram', 'Deployment diagram'], 'c'],
                ['Pada DFD, simbol yang menggambarkan tempat penyimpanan data disebut...', ['Proses', 'Entitas eksternal', 'Aliran data', 'Data store'], 'd'],
                ['Kebutuhan seperti "sistem harus merespons dalam 2 detik" termasuk kebutuhan...', ['Fungsional', 'Non-fungsional', 'Bisnis', 'Pengguna'], 'b'],
                ['Teknik pengumpulan kebutuhan dengan mengamati langsung pekerjaan pengguna disebut...', ['Wawancara', 'Kuesioner', 'Observasi', 'Studi pustaka'], 'c'],
                ['Diagram UML yang menunjukkan urutan pesan antarobjek berdasarkan waktu adalah...', ['Sequence diagram', 'Activity diagram', 'Class diagram', 'State diagram'], 'a'],
                ['Studi kelayakan yang menilai apakah manfaat sistem sebanding dengan biayanya adalah kelayakan...', ['Teknis', 'Operasional', 'Hukum', 'Ekonomi'], 'd'],
                ['DFD level 0 yang hanya berisi satu proses dan entitas eksternal disebut juga...', ['Diagram konteks', 'Diagram rinci', 'Flowchart', 'ERD'], 'a'],
                ['Pada use case diagram, relasi yang menandakan use case selalu memanggil use case lain adalah...', ['<<extend>>', 'Generalisasi', '<<include>>', 'Asosiasi'], 'c'],
            ]],
            'SI202' => ['Pemrograman Web', 2, 'HTML, CSS, PHP, dan HTTP', [
                ['Tag HTML untuk membuat tautan adalah...', ['<link>', '<a>', '<href>', '<url>'], 'b'],
                ['Method HTTP yang biasa dipakai untuk mengirim data form yang mengubah data di server adalah...', ['GET', 'HEAD', 'POST', 'OPTIONS'], 'c'],
                ['Kode status HTTP yang berarti halaman tidak ditemukan adalah...', ['404', '200', '500', '301'], 'a'],
                ['Properti CSS untuk mengubah warna teks adalah...', ['font-color', 'text-color', 'background', 'color'], 'd'],
                ['Di PHP, variabel superglobal untuk mengambil data dari form ber-method POST adalah...', ['$_GET', '$_POST', '$_FORM', '$_REQUEST_POST'], 'b'],
                ['Pola arsitektur yang dipakai Laravel adalah...', ['MVC', 'MVVM', 'Client-server murni', 'Event sourcing'], 'a'],
                ['Pada Laravel, perintah untuk menjalankan migrasi database adalah...', ['php artisan serve', 'php artisan make:model', 'php artisan route:list', 'php artisan migrate'], 'd'],
                ['Selektor CSS untuk memilih elemen dengan id "menu" adalah...', ['.menu', 'menu', '#menu', '*menu'], 'c'],
                ['Token yang dipakai Laravel untuk mencegah serangan CSRF pada form ditulis dengan direktif...', ['@method', '@csrf', '@auth', '@token'], 'b'],
                ['Atribut HTML yang membuat input wajib diisi sebelum form dikirim adalah...', ['required', 'readonly', 'disabled', 'checked'], 'a'],
            ]],
            'SI203' => ['Jaringan Komputer', 2, 'Model OSI dan TCP/IP', [
                ['Lapisan OSI yang bertugas menentukan rute paket antarjaringan adalah...', ['Data link', 'Transport', 'Network', 'Session'], 'c'],
                ['Protokol yang menerjemahkan nama domain menjadi alamat IP adalah...', ['DHCP', 'DNS', 'FTP', 'ARP'], 'b'],
                ['Port standar untuk HTTPS adalah...', ['443', '80', '21', '25'], 'a'],
                ['Protokol transport yang berorientasi koneksi dan menjamin urutan data adalah...', ['UDP', 'ICMP', 'IP', 'TCP'], 'd'],
                ['Perangkat yang meneruskan frame berdasarkan alamat MAC adalah...', ['Hub', 'Switch', 'Repeater', 'Modem'], 'b'],
                ['Jumlah host yang dapat dipakai pada subnet /24 adalah...', ['256', '255', '254', '128'], 'c'],
                ['Protokol yang memberikan alamat IP secara otomatis ke perangkat adalah...', ['DHCP', 'DNS', 'SMTP', 'SNMP'], 'a'],
                ['Topologi jaringan yang semua perangkatnya terhubung ke satu titik pusat adalah...', ['Bus', 'Ring', 'Mesh', 'Star'], 'd'],
                ['Alamat IP 192.168.1.10 termasuk ke dalam...', ['IP publik kelas A', 'IP privat', 'IP multicast', 'IP loopback'], 'b'],
                ['Perintah yang dipakai untuk menguji konektivitas ke host lain menggunakan ICMP adalah...', ['ipconfig', 'netstat', 'ping', 'nslookup'], 'c'],
            ]],
            'SI301' => ['Manajemen Proyek SI', 3, 'Perencanaan dan Pengendalian Proyek', [
                ['Tiga batasan utama proyek (triple constraint) adalah...', ['Ruang lingkup, waktu, biaya', 'Kualitas, risiko, SDM', 'Waktu, SDM, teknologi', 'Biaya, vendor, kontrak'], 'a'],
                ['Dokumen yang memecah pekerjaan proyek menjadi bagian-bagian kecil yang terkelola disebut...', ['Gantt chart', 'Project charter', 'WBS', 'RACI'], 'c'],
                ['Jalur terpanjang pada jaringan aktivitas yang menentukan durasi minimum proyek disebut...', ['Slack path', 'Critical path', 'Float path', 'Milestone'], 'b'],
                ['Diagram batang yang menampilkan jadwal aktivitas terhadap waktu adalah...', ['PERT', 'Fishbone', 'Histogram', 'Gantt chart'], 'd'],
                ['Bertambahnya ruang lingkup proyek tanpa kendali disebut...', ['Scope creep', 'Gold plating', 'Crashing', 'Fast tracking'], 'a'],
                ['Dalam Scrum, periode kerja tetap untuk menghasilkan increment disebut...', ['Backlog', 'Sprint', 'Daily standup', 'Epic'], 'b'],
                ['Peran Scrum yang bertanggung jawab memprioritaskan product backlog adalah...', ['Scrum master', 'Developer', 'Stakeholder', 'Product owner'], 'd'],
                ['Pada matriks RACI, huruf A berarti...', ['Assisted', 'Approved', 'Accountable', 'Assigned'], 'c'],
                ['Dokumen yang secara resmi mengesahkan dimulainya proyek adalah...', ['Project charter', 'Lessons learned', 'Change request', 'Status report'], 'a'],
                ['Teknik memperpendek jadwal dengan menambah sumber daya pada aktivitas kritis disebut...', ['Fast tracking', 'Crashing', 'Leveling', 'Baseline'], 'b'],
            ]],
            'SI302' => ['Interaksi Manusia dan Komputer', 3, 'Prinsip Usability dan Desain Antarmuka', [
                ['Heuristik Nielsen yang meminta sistem selalu memberi tahu pengguna apa yang sedang terjadi adalah...', ['Visibility of system status', 'User control and freedom', 'Consistency and standards', 'Aesthetic design'], 'a'],
                ['Hukum yang menyatakan waktu menjangkau target dipengaruhi jarak dan ukuran target adalah...', ['Hukum Hick', 'Hukum Miller', 'Hukum Fitts', 'Hukum Jakob'], 'c'],
                ['Hukum Hick menyatakan waktu mengambil keputusan bertambah seiring...', ['Ukuran tombol', 'Jumlah pilihan', 'Kecerahan warna', 'Jarak kursor'], 'b'],
                ['Gambaran fiktif pengguna sasaran yang dipakai dalam desain disebut...', ['Storyboard', 'Wireframe', 'Mockup', 'Persona'], 'd'],
                ['Rancangan kasar tata letak halaman tanpa detail visual disebut...', ['Wireframe', 'Prototype hi-fi', 'Style guide', 'Moodboard'], 'a'],
                ['Kuesioner standar 10 butir untuk mengukur usability adalah...', ['NPS', 'SUS', 'KPI', 'CSAT'], 'b'],
                ['Rasio kontras minimal teks normal menurut WCAG level AA adalah...', ['2:1', '3:1', '7:1', '4,5:1'], 'd'],
                ['Prinsip menyediakan tombol "batal" dan "undo" sesuai heuristik...', ['Recognition rather than recall', 'User control and freedom', 'Flexibility and efficiency', 'Help and documentation'], 'b'],
                ['Metode evaluasi saat pengguna diminta menyuarakan pikirannya sambil memakai sistem disebut...', ['Card sorting', 'A/B testing', 'Think aloud', 'Eye tracking'], 'c'],
                ['Petunjuk visual yang menyiratkan cara sebuah elemen dipakai, misalnya tombol tampak bisa ditekan, disebut...', ['Affordance', 'Feedback', 'Mapping', 'Constraint'], 'a'],
            ]],
            'SI401' => ['Keamanan Sistem Informasi', 4, 'Prinsip dan Ancaman Keamanan', [
                ['Tiga pilar keamanan informasi (CIA triad) adalah...', ['Control, integrity, audit', 'Confidentiality, integrity, availability', 'Cipher, identity, access', 'Compliance, inspection, authority'], 'b'],
                ['Serangan yang menyisipkan perintah SQL lewat input form disebut...', ['XSS', 'CSRF', 'SQL injection', 'Phishing'], 'c'],
                ['Serangan yang menyisipkan skrip berbahaya ke halaman web yang dilihat pengguna lain adalah...', ['Cross-site scripting', 'Brute force', 'Man in the middle', 'DDoS'], 'a'],
                ['Algoritma enkripsi yang memakai pasangan kunci publik dan privat disebut...', ['Simetris', 'Hashing', 'Steganografi', 'Asimetris'], 'd'],
                ['Fungsi yang mengubah data menjadi nilai tetap satu arah, cocok untuk menyimpan password, adalah...', ['Encoding Base64', 'Hashing (misal bcrypt)', 'Kompresi', 'Enkripsi simetris'], 'b'],
                ['Penipuan lewat email palsu untuk mencuri kredensial disebut...', ['Phishing', 'Spoofing ARP', 'Sniffing', 'Ransomware'], 'a'],
                ['Autentikasi yang memakai lebih dari satu faktor, misalnya password dan kode OTP, disebut...', ['SSO', 'RBAC', 'MFA', 'OAuth'], 'c'],
                ['Malware yang mengenkripsi data korban lalu meminta tebusan adalah...', ['Worm', 'Adware', 'Spyware', 'Ransomware'], 'd'],
                ['Prinsip memberi pengguna hak akses seminimal yang dibutuhkan disebut...', ['Defense in depth', 'Least privilege', 'Separation of duties', 'Zero day'], 'b'],
                ['Serangan yang membanjiri server dari banyak sumber sehingga layanan tidak tersedia adalah...', ['DDoS', 'Keylogging', 'Session hijacking', 'Social engineering'], 'a'],
            ]],
            'SI402' => ['Audit Sistem Informasi', 4, 'Konsep dan Proses Audit SI', [
                ['Tujuan utama audit sistem informasi adalah...', ['Membuat aplikasi baru', 'Menilai pengendalian dan keandalan sistem', 'Mengganti perangkat keras', 'Menghitung laba perusahaan'], 'b'],
                ['Bukti audit yang paling kuat umumnya diperoleh dari...', ['Keterangan lisan karyawan', 'Asumsi auditor', 'Observasi dan dokumen independen', 'Rumor internal'], 'c'],
                ['Pengendalian yang dirancang untuk mencegah kesalahan sebelum terjadi disebut...', ['Preventive control', 'Detective control', 'Corrective control', 'Compensating control'], 'a'],
                ['Jejak catatan aktivitas sistem yang dapat ditelusuri auditor disebut...', ['Backup', 'Patch', 'Firewall', 'Audit trail'], 'd'],
                ['Teknik audit yang memakai perangkat lunak untuk menguji data dalam jumlah besar disebut...', ['CAAT', 'SWOT', 'BPMN', 'PERT'], 'a'],
                ['Pemisahan tugas agar satu orang tidak memegang seluruh proses transaksi disebut...', ['Least privilege', 'Rotasi jabatan', 'Segregation of duties', 'Dual control'], 'c'],
                ['Risiko bahwa auditor gagal mendeteksi salah saji material disebut...', ['Inherent risk', 'Detection risk', 'Control risk', 'Business risk'], 'b'],
                ['Dokumen yang memuat temuan dan rekomendasi audit di akhir penugasan adalah...', ['Kertas kerja', 'Surat tugas', 'Program audit', 'Laporan audit'], 'd'],
                ['Organisasi profesi internasional yang menerbitkan sertifikasi CISA adalah...', ['ISACA', 'PMI', 'ISO', 'IEEE'], 'a'],
                ['Pengendalian yang memastikan data yang masuk ke sistem valid, misalnya cek format tanggal, termasuk...', ['Output control', 'Input control', 'Processing control', 'Physical control'], 'b'],
            ]],
            'SI501' => ['Tata Kelola TI', 5, 'Kerangka Kerja Tata Kelola TI', [
                ['Kerangka kerja tata kelola dan manajemen TI yang diterbitkan ISACA adalah...', ['ITIL', 'COBIT', 'TOGAF', 'PRINCE2'], 'b'],
                ['Kerangka kerja yang berfokus pada praktik manajemen layanan TI adalah...', ['ITIL', 'COBIT', 'Zachman', 'Six Sigma'], 'a'],
                ['Standar internasional untuk sistem manajemen keamanan informasi adalah...', ['ISO 9001', 'ISO 14001', 'ISO/IEC 27001', 'ISO 31000'], 'c'],
                ['Tujuan utama tata kelola TI adalah...', ['Menambah jumlah server', 'Mengganti seluruh staf TI', 'Mengurangi pemakaian internet', 'Menyelaraskan TI dengan tujuan bisnis'], 'd'],
                ['Pada COBIT 2019, domain yang berisi tujuan tata kelola (bukan manajemen) adalah...', ['APO', 'BAI', 'EDM', 'DSS'], 'c'],
                ['Kerangka kerja arsitektur enterprise yang memakai siklus ADM adalah...', ['TOGAF', 'ITIL', 'COSO', 'CMMI'], 'a'],
                ['Tingkat kapabilitas proses tertinggi pada skala COBIT 2019 adalah level...', ['3', '4', '5', '6'], 'c'],
                ['Pihak yang paling bertanggung jawab atas tata kelola TI di organisasi adalah...', ['Staf helpdesk', 'Dewan direksi dan manajemen puncak', 'Vendor perangkat lunak', 'Pengguna akhir'], 'b'],
                ['Ukuran kuantitatif untuk menilai pencapaian tujuan TI disebut...', ['SOP', 'SLA', 'RFC', 'KPI'], 'd'],
                ['Kesepakatan tertulis tingkat layanan antara penyedia layanan TI dan pelanggan disebut...', ['SLA', 'NDA', 'MoU', 'RACI'], 'a'],
            ]],
        ];
    }
}
