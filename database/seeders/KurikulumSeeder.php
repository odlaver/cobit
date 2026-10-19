<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Material;
use Illuminate\Database\Seeder;

class KurikulumSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->katalog() as $code => [$name, $semester, $prodi, $origin, $materials]) {
            $course = Course::firstOrNew(['code' => $code]);
            $course->fill(['name' => $name, 'semester' => $semester, 'prodi' => $prodi]);

            if (! $course->exists) {
                $course->is_active = true;
            }

            if (blank($course->description)) {
                $course->description = $origin;
            }

            $course->save();

            foreach ($materials as [$title, $description]) {
                Material::firstOrCreate(['course_id' => $course->id, 'title' => $title], ['description' => $description]);
            }
        }
    }

    // Gabungan kurikulum Ilkom 2020 dan SI 2024, semester 1-5
    private function katalog(): array
    {
        return [
            // Semester 1
            'DPA' => ['Dasar Pemrograman dan Algoritma', 1, 'SI & Ilkom', 'Ilkom: Dasar-Dasar Pemrograman (sem 1). SI: Dasar Pemrograman dan Algoritma (sem 1).', [
                ['Dasar Algoritma', 'Flowchart, pseudocode, dan kompleksitas sederhana.'],
                ['Variabel, Tipe Data, dan Operator', 'Deklarasi variabel, tipe data primitif, dan ekspresi.'],
                ['Percabangan dan Perulangan', 'Struktur if/else, switch, for, dan while.'],
            ]],
            'KSI' => ['Konsep Sistem Informasi', 1, 'SI & Ilkom', 'SI: Konsep Sistem Informasi (sem 1). Ilkom: Pengantar Sistem Informasi (sem 2).', [
                ['Konsep Dasar Sistem Informasi', 'Komponen, jenis, dan peran sistem informasi.'],
                ['Jenis-jenis Sistem Informasi', 'TPS, MIS, DSS, ESS, dan sistem enterprise.'],
                ['Sistem Informasi dalam Organisasi', 'Dukungan sistem informasi terhadap proses bisnis dan keputusan.'],
            ]],
            'LOGMAT' => ['Logika Matematika', 1, 'SI & Ilkom', 'Ilkom: Logika (sem 1). SI: Logika Matematika (sem 1).', [
                ['Logika Proposisi', 'Proposisi, operator logika, dan tabel kebenaran.'],
                ['Ekuivalensi dan Inferensi', 'Hukum logika, tautologi, dan aturan inferensi.'],
                ['Logika Predikat', 'Predikat serta kuantor universal dan eksistensial.'],
            ]],
            'STATPROB' => ['Statistika dan Probabilitas', 1, 'SI & Ilkom', 'Ilkom: Statistika dan Probabilitas (sem 1). SI: Statistika dan Probabilitas (sem 2).', [
                ['Statistika Deskriptif', 'Ukuran pemusatan, penyebaran, dan penyajian data.'],
                ['Peluang dan Distribusi', 'Aturan peluang, distribusi binomial, dan distribusi normal.'],
                ['Statistika Inferensial', 'Estimasi, uji hipotesis, dan regresi sederhana.'],
            ]],
            'POK' => ['Pengantar Organisasi Komputer', 1, 'SI & Ilkom', 'SI: Pengantar Organisasi Komputer (sem 1). Ilkom: Pengantar Organisasi Komputer (sem 2).', [
                ['Arsitektur Von Neumann', 'CPU, memori, perangkat I/O, dan bus.'],
                ['Representasi Data', 'Bilangan biner, oktal, heksadesimal, dan komplemen dua.'],
                ['Hierarki Memori', 'Register, cache, RAM, dan penyimpanan sekunder.'],
            ]],
            'PTI' => ['Pengantar Teknologi Informasi', 1, 'SI', 'SI: Pengantar Teknologi Informasi (sem 1).', [
                ['Perangkat Keras dan Perangkat Lunak', 'Komponen komputer dan jenis perangkat lunak.'],
                ['Internet dan Layanan Digital', 'Cara kerja internet, web, dan layanan cloud.'],
                ['Dampak dan Etika TI', 'Privasi, keamanan, dan etika penggunaan teknologi.'],
            ]],
            'ALIN' => ['Aljabar Linear', 1, 'Ilkom', 'Ilkom: Aljabar Linear (sem 1).', [
                ['Matriks dan Operasinya', 'Penjumlahan, perkalian, transpose, determinan, dan invers.'],
                ['Sistem Persamaan Linear', 'Eliminasi Gauss dan Gauss-Jordan.'],
                ['Vektor dan Ruang Vektor', 'Operasi vektor, kombinasi linear, basis, dan dimensi.'],
            ]],
            'MATDAS' => ['Matematika Dasar', 1, 'Ilkom', 'Ilkom: Matematika (sem 1).', [
                ['Fungsi dan Limit', 'Jenis fungsi, limit, dan kontinuitas.'],
                ['Turunan', 'Aturan turunan dan penerapannya.'],
                ['Integral', 'Integral tak tentu, integral tentu, dan penerapannya.'],
            ]],

            // Semester 2
            'MATDIS' => ['Matematika Diskrit', 2, 'SI & Ilkom', 'Ilkom: Matematika Diskrit (sem 2, wajib). SI: Matematika Diskrit (sem 3, pilihan).', [
                ['Logika, Himpunan, dan Graf', 'Proposisi, operasi himpunan, dan dasar teori graf.'],
                ['Relasi dan Fungsi', 'Sifat relasi serta fungsi injektif, surjektif, dan bijektif.'],
                ['Kombinatorika', 'Aturan perkalian, permutasi, dan kombinasi.'],
            ]],
            'PT' => ['Pemrograman Terstruktur', 2, 'SI & Ilkom', 'Ilkom dan SI: Pemrograman Terstruktur (sem 2).', [
                ['Fungsi dan Prosedur', 'Parameter, nilai balik, dan ruang lingkup variabel.'],
                ['Array dan String', 'Array satu dan dua dimensi serta manipulasi string.'],
                ['Rekursi dan Pointer', 'Fungsi rekursif dan konsep alamat memori.'],
            ]],
            'SO' => ['Sistem Operasi', 2, 'SI & Ilkom', 'Ilkom dan SI: Sistem Operasi (sem 2).', [
                ['Proses dan Thread', 'Status proses, PCB, dan multithreading.'],
                ['Penjadwalan CPU', 'FCFS, SJF, Round Robin, dan penjadwalan prioritas.'],
                ['Manajemen Memori', 'Paging, segmentasi, dan memori virtual.'],
            ]],
            'SDA' => ['Struktur Data dan Algoritma', 2, 'SI & Ilkom', 'Ilkom: Struktur Data dan Algoritma (sem 2). SI: Struktur Data (sem 3).', [
                ['Stack dan Queue', 'Operasi push, pop, enqueue, dan dequeue.'],
                ['Linked List', 'Singly, doubly, dan circular linked list.'],
                ['Tree dan Graph', 'Binary tree, BST, traversal, BFS, dan DFS.'],
            ]],
            'RPL' => ['Rekayasa Perangkat Lunak', 2, 'SI & Ilkom', 'Ilkom: Rekayasa Perangkat Lunak (sem 2). SI: Rekayasa Perangkat Lunak (sem 3).', [
                ['Model Proses Perangkat Lunak', 'Waterfall, incremental, spiral, dan agile.'],
                ['Rekayasa Kebutuhan', 'Elisitasi, analisis, dan spesifikasi kebutuhan.'],
                ['Kualitas Perangkat Lunak', 'Atribut kualitas dan dasar pengujian.'],
            ]],
            'BD' => ['Basis Data', 2, 'SI & Ilkom', 'SI: Konsep Basis Data (sem 2) dan Sistem Basis Data (sem 3). Ilkom: Basis Data (sem 3).', [
                ['Model Relasional dan SQL', 'Tabel, kunci, relasi, dan perintah SQL.'],
                ['Entity Relationship Diagram', 'Entitas, atribut, relasi, dan kardinalitas.'],
                ['Normalisasi', 'Bentuk normal 1NF, 2NF, 3NF, dan BCNF.'],
            ]],
            'SIM' => ['Sistem Informasi Manajemen', 2, 'SI', 'SI: Sistem Informasi Manajemen (sem 2).', [
                ['Peran SIM dalam Organisasi', 'Informasi untuk perencanaan, pengendalian, dan keputusan.'],
                ['Sistem Pendukung Keputusan', 'DSS, ESS, dan business intelligence.'],
                ['Sistem Enterprise', 'ERP, CRM, dan SCM.'],
            ]],

            // Semester 3
            'PBO' => ['Pemrograman Berorientasi Objek', 3, 'SI & Ilkom', 'Ilkom dan SI: Pemrograman Berorientasi Objek (sem 3).', [
                ['Kelas dan Objek', 'Atribut, method, dan konstruktor.'],
                ['Pilar OOP', 'Enkapsulasi, pewarisan, polimorfisme, dan abstraksi.'],
                ['Interface dan Exception', 'Kelas abstrak, interface, dan penanganan exception.'],
            ]],
            'JARKOM' => ['Jaringan Komputer dan Komunikasi Data', 3, 'SI & Ilkom', 'Ilkom: Komunikasi Data dan Jaringan Komputer (sem 3). SI: Jaringan Komputer dan Komunikasi Data (sem 4).', [
                ['Model OSI dan TCP/IP', 'Lapisan jaringan dan fungsinya.'],
                ['Pengalamatan IP dan Subnetting', 'IPv4, subnet mask, dan CIDR.'],
                ['Perangkat dan Media Transmisi', 'Switch, router, kabel, dan jaringan nirkabel.'],
            ]],
            'IMK' => ['Interaksi Manusia dan Komputer (UI/UX)', 3, 'SI & Ilkom', 'SI: UI/UX Design (sem 3, pilihan). Ilkom: Sistem Interaksi (sem 5).', [
                ['Prinsip Usability dan Desain Antarmuka', 'Heuristik usability dan prinsip desain antarmuka.'],
                ['User Experience dan Riset Pengguna', 'Persona, user journey, dan wawancara pengguna.'],
                ['Prototyping dan Evaluasi', 'Wireframe, prototipe, dan uji usability.'],
            ]],
            'APB' => ['Analisis Proses Bisnis', 3, 'SI', 'SI: Analisis Proses Bisnis (sem 3).', [
                ['Konsep Proses Bisnis', 'Elemen, jenis, dan siklus hidup proses bisnis.'],
                ['Pemodelan dengan BPMN', 'Event, activity, gateway, dan swimlane.'],
                ['Perbaikan Proses Bisnis', 'Analisis as-is, to-be, dan business process reengineering.'],
            ]],
            'KMO' => ['Kepemimpinan dan Manajemen Organisasi', 3, 'SI', 'SI: Kepemimpinan dan Manajemen Organisasi (sem 3).', [
                ['Fungsi Manajemen', 'Perencanaan, pengorganisasian, pengarahan, dan pengendalian.'],
                ['Gaya Kepemimpinan', 'Teori dan gaya kepemimpinan.'],
                ['Struktur dan Budaya Organisasi', 'Bentuk struktur organisasi dan budaya kerja.'],
            ]],
            'DAA' => ['Desain dan Analisis Algoritma', 3, 'Ilkom', 'Ilkom: Desain dan Analisis Algoritma (sem 3).', [
                ['Kompleksitas Algoritma', 'Notasi Big-O, Omega, dan Theta.'],
                ['Divide and Conquer', 'Merge sort, quick sort, dan binary search.'],
                ['Greedy dan Dynamic Programming', 'Strategi greedy dan pemrograman dinamis.'],
            ]],
            'ANUM' => ['Analisis Numerik', 3, 'Ilkom', 'Ilkom: Analisis Numerik (sem 3).', [
                ['Galat dan Akar Persamaan', 'Jenis galat, metode biseksi, dan Newton-Raphson.'],
                ['Interpolasi', 'Interpolasi linear, Lagrange, dan Newton.'],
                ['Integrasi Numerik', 'Metode trapesium dan Simpson.'],
            ]],
            'SISDIG' => ['Sistem Digital', 3, 'Ilkom', 'Ilkom: Pengantar Sistem Digital (sem 3).', [
                ['Gerbang Logika', 'AND, OR, NOT, NAND, NOR, dan XOR.'],
                ['Aljabar Boolean dan K-Map', 'Penyederhanaan fungsi logika.'],
                ['Rangkaian Kombinasional dan Sekuensial', 'Adder, multiplexer, flip-flop, dan counter.'],
            ]],
            'TBA' => ['Teori Bahasa dan Automata', 3, 'Ilkom', 'Ilkom: Teori Bahasa dan Automata (sem 3).', [
                ['Automata Hingga', 'DFA dan NFA.'],
                ['Ekspresi Reguler', 'Bahasa reguler dan ekspresi reguler.'],
                ['Tata Bahasa Bebas Konteks', 'CFG, pohon penurunan, dan pushdown automata.'],
            ]],
            'MULMED' => ['Multimedia', 3, 'SI & Ilkom', 'Pilihan di kedua prodi. Ilkom: Multimedia (sem 3). SI: Multimedia (sem 4).', [
                ['Elemen Multimedia', 'Teks, gambar, audio, video, dan animasi.'],
                ['Kompresi Data Multimedia', 'Kompresi lossy, lossless, dan format berkas.'],
                ['Produksi Multimedia', 'Tahap pra-produksi, produksi, dan pasca-produksi.'],
            ]],
            'PPL' => ['Pengujian Perangkat Lunak', 3, 'SI & Ilkom', 'Ilkom: Pengujian Perangkat Lunak (sem 3, pilihan). SI: Software Testing dan Quality Assurance (sem 6).', [
                ['Dasar Pengujian', 'Verifikasi, validasi, dan level pengujian.'],
                ['Black Box dan White Box', 'Equivalence partitioning, boundary value, dan basis path.'],
                ['Otomasi dan Penjaminan Mutu', 'Unit test, test case, dan quality assurance.'],
            ]],
            'RO' => ['Riset Operasi', 3, 'SI & Ilkom', 'Pilihan di kedua prodi. SI: Riset Operasi (sem 3). Ilkom: Operasi Riset (sem 6).', [
                ['Program Linear', 'Model program linear, metode grafik, dan simpleks.'],
                ['Transportasi dan Penugasan', 'Metode NWC, VAM, dan Hungarian.'],
                ['Analisis Jaringan', 'CPM, PERT, dan lintasan terpendek.'],
            ]],
            'MANRIS' => ['Manajemen Risiko', 3, 'SI & Ilkom', 'Pilihan di kedua prodi. SI: Manajemen Resiko (sem 3). Ilkom: Manajemen Risiko (sem 6).', [
                ['Konsep Risiko', 'Jenis risiko dan proses manajemen risiko.'],
                ['Identifikasi dan Analisis Risiko', 'Matriks risiko, kemungkinan, dan dampak.'],
                ['Mitigasi dan Standar', 'Strategi penanganan risiko dan ISO 31000.'],
            ]],

            // Semester 4
            'APSI' => ['Analisis dan Perancangan Sistem Informasi', 4, 'SI & Ilkom', 'Ilkom: Analisis dan Desain SI (sem 4). SI: Analisis dan Perancangan Sistem Informasi (sem 4).', [
                ['SDLC dan Pemodelan Sistem', 'Tahapan SDLC dan pemodelan sistem.'],
                ['Pemodelan UML', 'Use case, activity, class, dan sequence diagram.'],
                ['Analisis Kebutuhan Sistem', 'Kebutuhan fungsional, non-fungsional, dan studi kelayakan.'],
            ]],
            'PEMWEB' => ['Pemrograman Web', 4, 'SI & Ilkom', 'Ilkom: Pemrograman Web (sem 4). SI: Pemrograman berbasis Web (sem 4).', [
                ['HTML, CSS, PHP, dan HTTP', 'Struktur halaman, gaya tampilan, dan protokol web.'],
                ['JavaScript dan DOM', 'Event, manipulasi DOM, dan fetch.'],
                ['Form dan Database', 'Validasi form, session, dan koneksi database.'],
            ]],
            'PEMMOB' => ['Pemrograman Mobile', 4, 'SI & Ilkom', 'Ilkom: Teknologi dan Aplikasi Mobile (sem 4) dan Pemrograman Mobile Lanjut (sem 5). SI: Pemrograman Mobile (sem 4).', [
                ['Platform dan Arsitektur Mobile', 'Android, iOS, dan aplikasi lintas platform.'],
                ['Antarmuka Aplikasi Mobile', 'Layout, komponen antarmuka, dan navigasi.'],
                ['Data dan Layanan', 'Penyimpanan lokal, REST API, dan notifikasi.'],
            ]],
            'KB' => ['Kecerdasan Buatan', 4, 'Ilkom', 'Ilkom: Kecerdasan Buatan (sem 4).', [
                ['Agen Cerdas dan Pencarian', 'Agen, BFS, DFS, dan A*.'],
                ['Representasi Pengetahuan', 'Logika, aturan produksi, dan semantic network.'],
                ['Logika Fuzzy', 'Himpunan fuzzy dan inferensi fuzzy.'],
            ]],
            'PM' => ['Pembelajaran Mesin', 4, 'Ilkom', 'Ilkom: Pembelajaran Mesin (sem 4).', [
                ['Supervised Learning', 'Regresi, klasifikasi, dan decision tree.'],
                ['Unsupervised Learning', 'Clustering K-Means dan reduksi dimensi.'],
                ['Evaluasi Model', 'Akurasi, presisi, recall, overfitting, dan cross validation.'],
            ]],
            'TEOINF' => ['Teori Informasi', 4, 'Ilkom', 'Ilkom: Teori Informasi (sem 4).', [
                ['Entropi dan Informasi', 'Ukuran informasi dan entropi Shannon.'],
                ['Pengodean Sumber', 'Kode Huffman dan Shannon-Fano.'],
                ['Kanal dan Deteksi Kesalahan', 'Kapasitas kanal, paritas, dan kode Hamming.'],
            ]],
            'KEAMJAR' => ['Keamanan Jaringan', 4, 'SI', 'SI: Keamanan Jaringan (sem 4).', [
                ['Ancaman Jaringan', 'Sniffing, spoofing, DoS, dan man-in-the-middle.'],
                ['Firewall dan IDS', 'Jenis firewall, IDS, dan IPS.'],
                ['Protokol Aman', 'VPN, TLS, dan WPA.'],
            ]],
            'TKTI' => ['Tata Kelola Teknologi Informasi', 4, 'SI', 'SI: Tata Kelola Teknologi Informasi (sem 4).', [
                ['Kerangka Kerja Tata Kelola TI', 'COBIT, ITIL, dan standar terkait.'],
                ['Penyelarasan Bisnis dan TI', 'Strategi TI, penyelarasan, dan nilai TI.'],
                ['Pengukuran Kinerja TI', 'Maturity level, KPI, dan balanced scorecard TI.'],
            ]],
            'EPP' => ['Etika Profesi', 4, 'SI & Ilkom', 'SI: Etika Profesi dan Profesional (sem 4). Ilkom: Etika Profesi (sem 6).', [
                ['Etika dan Profesi TI', 'Kode etik profesi dan tanggung jawab profesional.'],
                ['Hukum dan Regulasi TI', 'UU ITE, UU PDP, dan hak kekayaan intelektual.'],
                ['Isu Etika Teknologi', 'Privasi data, plagiarisme, dan kejahatan siber.'],
            ]],
            'KWU' => ['Kewirausahaan dan Bisnis Digital', 4, 'SI & Ilkom', 'SI: Kewirausahaan dan Bisnis Digital (sem 4). Ilkom: Kewirausahaan (sem 5).', [
                ['Konsep Kewirausahaan', 'Karakter wirausaha dan peluang usaha.'],
                ['Model Bisnis', 'Business Model Canvas dan value proposition.'],
                ['Bisnis Digital', 'E-commerce, startup, dan pemasaran digital.'],
            ]],
            'IOT' => ['Internet of Things', 4, 'SI & Ilkom', 'Pilihan di kedua prodi. Ilkom: Internet of Things (sem 4). SI: IoT (sem 5).', [
                ['Arsitektur IoT', 'Perangkat, gateway, cloud, dan aplikasi.'],
                ['Sensor dan Aktuator', 'Jenis sensor, aktuator, dan mikrokontroler.'],
                ['Protokol IoT', 'MQTT, HTTP, dan CoAP.'],
            ]],

            // Semester 5
            'PWL' => ['Pemrograman Web Lanjut', 5, 'SI & Ilkom', 'Ilkom: Pemrograman Web Lanjut (sem 5, wajib). SI: Pemrograman Web Lanjut (sem 5, pilihan).', [
                ['Framework dan Pola MVC', 'Konsep MVC, routing, dan controller.'],
                ['ORM dan Migrasi', 'Model, relasi, migration, dan seeder.'],
                ['Autentikasi dan Keamanan Web', 'Session, middleware, CSRF, dan XSS.'],
            ]],
            'KEAMSI' => ['Keamanan Sistem Informasi', 5, 'SI & Ilkom', 'SI: Keamanan Sistem Informasi (sem 5). Ilkom: Keamanan Sistem Informasi (sem 6, pilihan).', [
                ['Prinsip dan Ancaman Keamanan', 'CIA triad, ancaman, dan kerentanan.'],
                ['Kriptografi', 'Enkripsi simetris, asimetris, dan fungsi hash.'],
                ['Pengendalian Akses', 'Autentikasi, otorisasi, dan kebijakan keamanan.'],
            ]],
            'MPSI' => ['Manajemen Proyek Sistem Informasi', 5, 'SI & Ilkom', 'SI: Manajemen Proyek Sistem Informasi (sem 5). Ilkom: Manajemen Proyek TI (sem 6).', [
                ['Perencanaan dan Pengendalian Proyek', 'Ruang lingkup, jadwal, dan biaya proyek.'],
                ['Penjadwalan Proyek', 'WBS, Gantt chart, dan critical path.'],
                ['Manajemen Risiko dan Mutu Proyek', 'Risiko, kualitas, dan komunikasi proyek.'],
            ]],
            'GENAI' => ['Penggunaan Generative AI', 5, 'SI', 'SI: Penggunaan Generative Artificial Intelligence (sem 5).', [
                ['Konsep Generative AI', 'Large language model dan model generatif.'],
                ['Prompt Engineering', 'Teknik menyusun prompt yang efektif.'],
                ['Etika dan Risiko Generative AI', 'Halusinasi, bias, privasi, dan hak cipta.'],
            ]],
            'SISPAK' => ['Sistem Pakar', 5, 'Ilkom', 'Ilkom: Sistem Pakar (sem 5).', [
                ['Konsep Sistem Pakar', 'Basis pengetahuan dan mesin inferensi.'],
                ['Metode Inferensi', 'Forward chaining dan backward chaining.'],
                ['Ketidakpastian', 'Certainty factor dan teorema Bayes.'],
            ]],
            'METPEN' => ['Metodologi Penelitian', 5, 'SI & Ilkom', 'Ilkom: Metodologi Penelitian (sem 5). SI: Metodologi Penelitian dan Penulisan Ilmiah (sem 6).', [
                ['Konsep Penelitian', 'Jenis penelitian, rumusan masalah, dan hipotesis.'],
                ['Metode Pengumpulan Data', 'Kuesioner, wawancara, observasi, dan sampling.'],
                ['Penulisan Ilmiah', 'Struktur proposal, sitasi, dan daftar pustaka.'],
            ]],
            'CLOUD' => ['Cloud Computing', 5, 'SI & Ilkom', 'Pilihan di kedua prodi: Cloud Computing (sem 5).', [
                ['Model Layanan Cloud', 'IaaS, PaaS, dan SaaS.'],
                ['Model Deployment', 'Public, private, hybrid, dan community cloud.'],
                ['Virtualisasi dan Container', 'Hypervisor, virtual machine, dan Docker.'],
            ]],
            'SIG' => ['Sistem Informasi Geografis', 5, 'SI & Ilkom', 'Pilihan di kedua prodi: Sistem Informasi Geografis (sem 5).', [
                ['Data Spasial', 'Data vektor, raster, dan atribut.'],
                ['Sistem Koordinat dan Proyeksi', 'Datum, koordinat geografis, dan UTM.'],
                ['Analisis Spasial', 'Buffer, overlay, dan query spasial.'],
            ]],
        ];
    }
}
