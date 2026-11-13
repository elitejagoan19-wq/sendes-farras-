<?php
require_once __DIR__ . '/includes/functions.php';

if (isset($_GET['logout'])) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time()-42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

if (!logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
    verify_csrf();
    $loginId = post('email');
    // Mendukung email dan nama pengguna singkat untuk akun demo.
    $aliases = ['admin' => 'admin@ryzen.sch.id', 'guru' => 'guru@ryzen.sch.id', 'siswa' => 'siswa@ryzen.sch.id', 'orangtua' => 'ortu@ryzen.sch.id'];
    $email = $aliases[strtolower($loginId)] ?? $loginId;
    $password = (string)($_POST['password'] ?? '');
    $stmt = db()->prepare('SELECT id,name,email,password,role FROM users WHERE email=? LIMIT 1');
    $stmt->execute([$email]);
    $u = $stmt->fetch();
    if ($u && password_verify($password, $u['password'])) {
        session_regenerate_id(true);
        unset($u['password']);
        $_SESSION['user'] = $u;
        redirect('dashboard');
    }
    set_flash('error', 'Email atau kata sandi tidak sesuai.');
}

if (!logged_in()) {
    $flash = get_flash();
    ?>
    <!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Masuk · RYZEN</title><link rel="preconnect" href="https://fonts.googleapis.com"><link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet"><link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css"></head>
    <body class="login-body"><div class="login-layout"><section class="login-visual"><div class="login-brand"><span class="brand-mark">R</span><span><b>RYZEN</b><small>EDUCATION PORTAL</small></span></div><div class="visual-copy"><span class="pill-light">PORTAL INFORMASI PENDIDIKAN</span><h1>Semua informasi sekolah,<br><em>dalam satu tempat.</em></h1><p>Memudahkan siswa, orang tua, guru, dan administrasi mengakses informasi pendidikan secara cepat dan teratur.</p><div class="visual-stats"><div><b>01</b><small>Informasi terpusat</small></div><div><b>24/7</b><small>Akses informasi</small></div><div><b>4</b><small>Jenis pengguna</small></div></div></div><div class="visual-footer">SMKN 1 TANJUNG SELOR · BULUNGAN</div></section>
    <section class="login-panel"><div class="login-form-wrap"><div class="mobile-brand"><span class="brand-mark">R</span><b>RYZEN</b></div><div class="eyebrow">SELAMAT DATANG KEMBALI</div><h2>Masuk ke akun</h2><p class="muted">Gunakan akun yang telah diberikan oleh administrator sekolah.</p>
    <?php if ($flash): ?><div class="alert <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div><?php endif; ?>
    <form method="post" class="form-stack"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="login"><label>Email atau username</label><input type="text" name="email" placeholder="admin atau admin@ryzen.sch.id" required autocomplete="username"><label>Kata sandi</label><input type="password" name="password" placeholder="Masukkan kata sandi" required autocomplete="current-password"><button class="btn primary full" type="submit">Masuk ke RYZEN <span>→</span></button></form><div class="login-note"><b>Belum memiliki akun?</b><br>Hubungi administrator sekolah untuk mendapatkan akses.</div></div><div class="login-copyright">© <?= date('Y') ?> RYZEN · SMKN 1 Tanjung Selor</div></section></div></body></html>
    <?php
    exit;
}

$page = $_GET['page'] ?? 'dashboard';
$allowed = ['dashboard','announcements','schedule','attendance','my-attendance','students','users','child'];
if (!in_array($page, $allowed, true)) $page = 'dashboard';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] !== 'login') {
    verify_csrf();
    $action = post('action');
    try {
        if ($action === 'save-announcement') {
            require_role(['admin']);
            $id = (int)post('id','0'); $title=post('title'); $category=post('category','Informasi'); $body=post('body');
            if ($title==='' || $body==='') throw new RuntimeException('Judul dan isi pengumuman wajib diisi.');
            if ($id) db()->prepare('UPDATE announcements SET title=?,category=?,body=? WHERE id=?')->execute([$title,$category,$body,$id]);
            else db()->prepare('INSERT INTO announcements(title,category,body,author_id) VALUES(?,?,?,?)')->execute([$title,$category,$body,current_user()['id']]);
            set_flash('success','Pengumuman berhasil disimpan.');
            redirect('announcements');
        }
        if ($action === 'delete-announcement') {
            require_role(['admin']);
            db()->prepare('DELETE FROM announcements WHERE id=?')->execute([(int)post('id')]);
            set_flash('success','Pengumuman berhasil dihapus.'); redirect('announcements');
        }
        if ($action === 'save-schedule') {
            require_role(['admin']);
            $id=(int)post('id','0'); $data=[post('class_name'),post('day_name'),post('start_time'),post('end_time'),post('subject'),post('teacher'),post('room','-')];
            if (in_array('',array_slice($data,0,6),true)) throw new RuntimeException('Lengkapi semua data jadwal.');
            if ($id) { $data[]=$id; db()->prepare('UPDATE schedules SET class_name=?,day_name=?,start_time=?,end_time=?,subject=?,teacher=?,room=? WHERE id=?')->execute($data); }
            else db()->prepare('INSERT INTO schedules(class_name,day_name,start_time,end_time,subject,teacher,room) VALUES(?,?,?,?,?,?,?)')->execute($data);
            set_flash('success','Jadwal berhasil disimpan.'); redirect('schedule');
        }
        if ($action === 'delete-schedule') {
            require_role(['admin']); db()->prepare('DELETE FROM schedules WHERE id=?')->execute([(int)post('id')]); set_flash('success','Jadwal dihapus.'); redirect('schedule');
        }
        if ($action === 'save-attendance') {
            require_role(['admin','guru']);
            $studentId=(int)post('student_id'); $date=post('attendance_date'); $status=post('status'); $note=post('note');
            if (!$studentId || !$date || !in_array($status,['Hadir','Izin','Sakit','Alpa'],true)) throw new RuntimeException('Data absensi tidak valid.');
            db()->prepare("INSERT INTO attendance(student_id,attendance_date,status,note,recorded_by) VALUES(?,?,?,?,?) ON DUPLICATE KEY UPDATE status=VALUES(status),note=VALUES(note),recorded_by=VALUES(recorded_by)")
                ->execute([$studentId,$date,$status,$note,current_user()['id']]);
            set_flash('success','Data kehadiran berhasil disimpan.'); redirect('attendance');
        }
        if ($action === 'save-student') {
            require_role(['admin']);
            $id=(int)post('id','0'); $name=post('name'); $email=post('email'); $nis=post('nis'); $class=post('class_name'); $parent=(int)post('parent_user_id','0');
            if (!$name || !$email || !$nis || !$class) throw new RuntimeException('Lengkapi nama, email, NIS, dan kelas.');
            if ($id) {
                $student = db()->prepare('SELECT user_id FROM students WHERE id=?'); $student->execute([$id]); $old=$student->fetch();
                if (!$old) throw new RuntimeException('Data siswa tidak ditemukan.');
                db()->prepare('UPDATE users SET name=?,email=? WHERE id=?')->execute([$name,$email,$old['user_id']]);
                db()->prepare('UPDATE students SET nis=?,class_name=?,parent_user_id=? WHERE id=?')->execute([$nis,$class,$parent ?: null,$id]);
            } else {
                $tempPassword = 'Siswa' . random_int(1000,9999) . '!';
                $pdo=db(); $pdo->beginTransaction();
                $pdo->prepare("INSERT INTO users(name,email,password,role) VALUES(?,?,?,'siswa')")->execute([$name,$email,password_hash($tempPassword,PASSWORD_DEFAULT)]);
                $uid=(int)$pdo->lastInsertId();
                $pdo->prepare('INSERT INTO students(user_id,nis,class_name,parent_user_id) VALUES(?,?,?,?)')->execute([$uid,$nis,$class,$parent ?: null]);
                $pdo->commit();
                set_flash('success','Siswa ditambahkan. Kata sandi awal: '.$tempPassword.' (catat dan berikan dengan aman).'); redirect('students');
            }
            set_flash('success','Data siswa diperbarui.'); redirect('students');
        }
        if ($action === 'delete-student') {
            require_role(['admin']); db()->prepare('DELETE FROM students WHERE id=?')->execute([(int)post('id')]); set_flash('success','Data siswa dihapus.'); redirect('students');
        }
        if ($action === 'save-user') {
            require_role(['admin']);
            $id=(int)post('id','0'); $name=post('name'); $email=post('email'); $role=post('role'); $password=(string)($_POST['password'] ?? '');
            if (!$name || !$email || !in_array($role,['admin','guru','siswa','orangtua'],true)) throw new RuntimeException('Data pengguna belum lengkap.');
            if ($id) {
                if ($password!=='') db()->prepare('UPDATE users SET name=?,email=?,role=?,password=? WHERE id=?')->execute([$name,$email,$role,password_hash($password,PASSWORD_DEFAULT),$id]);
                else db()->prepare('UPDATE users SET name=?,email=?,role=? WHERE id=?')->execute([$name,$email,$role,$id]);
            } else {
                if (strlen($password)<8) throw new RuntimeException('Kata sandi pengguna baru minimal 8 karakter.');
                db()->prepare('INSERT INTO users(name,email,role,password) VALUES(?,?,?,?)')->execute([$name,$email,$role,password_hash($password,PASSWORD_DEFAULT)]);
            }
            set_flash('success','Pengguna berhasil disimpan.'); redirect('users');
        }
        if ($action === 'delete-user') {
            require_role(['admin']); $id=(int)post('id');
            if ($id===current_user()['id']) throw new RuntimeException('Akun yang sedang digunakan tidak dapat dihapus.');
            db()->prepare('DELETE FROM users WHERE id=?')->execute([$id]); set_flash('success','Pengguna dihapus.'); redirect('users');
        }
    } catch (Throwable $ex) {
        if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
        set_flash('error', $ex instanceof PDOException ? 'Operasi gagal. Periksa kemungkinan email atau NIS sudah digunakan.' : $ex->getMessage());
        redirect($page);
    }
}

$pageTitles = ['dashboard'=>'Ringkasan','announcements'=>'Pengumuman Sekolah','schedule'=>'Jadwal Pelajaran','attendance'=>'Kelola Absensi','my-attendance'=>'Riwayat Kehadiran','students'=>'Data Siswa','users'=>'Manajemen Pengguna','child'=>'Informasi Anak'];
$pageTitle = $pageTitles[$page];
require __DIR__ . '/includes/header.php';

$role = current_user()['role'];
$pdo = db();

if ($page === 'dashboard') {
    $totalStudents=(int)$pdo->query('SELECT COUNT(*) FROM students')->fetchColumn();
    $totalAnnouncements=(int)$pdo->query('SELECT COUNT(*) FROM announcements')->fetchColumn();
    $today=date('Y-m-d');
    $q=$pdo->prepare('SELECT COUNT(*) FROM attendance WHERE attendance_date=? AND status="Hadir"'); $q->execute([$today]); $presentToday=(int)$q->fetchColumn();
    $latest=$pdo->query('SELECT a.*,u.name author FROM announcements a LEFT JOIN users u ON u.id=a.author_id ORDER BY a.created_at DESC LIMIT 4')->fetchAll();
    $upcoming=$pdo->query("SELECT * FROM schedules ORDER BY FIELD(day_name,'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'),start_time LIMIT 5")->fetchAll();
    ?>
    <div class="welcome-banner"><div><span class="pill-light">PORTAL PENDIDIKAN TERPADU</span><h1>Halo, <?= e(explode(' ',current_user()['name'])[0]) ?> 👋</h1><p>Selamat datang di RYZEN. Akses informasi sekolah kini lebih mudah dan teratur.</p></div><div class="banner-art"><div class="orb orb-one"></div><div class="orb orb-two"></div><div class="art-card">R<span>+</span><small>LEARN · CONNECT · GROW</small></div></div></div>
    <div class="stat-grid">
      <div class="stat-card"><div class="stat-icon violet">♧</div><div><span>Total siswa</span><b><?= $totalStudents ?></b><small>Terdaftar di sistem</small></div></div>
      <div class="stat-card"><div class="stat-icon green">✓</div><div><span>Hadir hari ini</span><b><?= $presentToday ?></b><small>Data kehadiran tercatat</small></div></div>
      <div class="stat-card"><div class="stat-icon orange">▤</div><div><span>Pengumuman</span><b><?= $totalAnnouncements ?></b><small>Informasi sekolah</small></div></div>
      <div class="stat-card"><div class="stat-icon blue">◷</div><div><span>Tanggal hari ini</span><b class="date-stat"><?= date('d') ?></b><small><?= date('F Y') ?></small></div></div>
    </div>
    <div class="section-grid"><section class="panel"><div class="panel-heading"><div><span class="eyebrow">INFORMASI TERBARU</span><h3>Pengumuman sekolah</h3></div><a class="text-link" href="<?= BASE_URL ?>/index.php?page=announcements">Lihat semua →</a></div>
    <?php if (!$latest): ?><div class="empty-state">Belum ada pengumuman.</div><?php endif; foreach($latest as $a): ?><article class="announcement-row"><div class="announcement-symbol">▤</div><div class="row-main"><div class="row-meta"><span class="tag"><?= e($a['category']) ?></span><span><?= date('d M Y',strtotime($a['created_at'])) ?></span></div><b><?= e($a['title']) ?></b><p><?= e(mb_strimwidth($a['body'],0,115,'…')) ?></p></div></article><?php endforeach; ?>
    </section><section class="panel"><div class="panel-heading"><div><span class="eyebrow">AKADEMIK</span><h3>Jadwal pelajaran</h3></div><a class="text-link" href="<?= BASE_URL ?>/index.php?page=schedule">Semua →</a></div>
    <?php foreach($upcoming as $s): ?><div class="mini-schedule"><div class="day-chip"><?= e(substr($s['day_name'],0,3)) ?><b><?= e(substr($s['start_time'],0,5)) ?></b></div><div><b><?= e($s['subject']) ?></b><small><?= e($s['class_name']) ?> · <?= e($s['teacher']) ?></small></div></div><?php endforeach; ?>
    </section></div>
    <div class="info-strip"><span class="info-strip-icon">✦</span><div><b>Informasi terpusat, komunikasi lebih mudah.</b><p>RYZEN membantu siswa, orang tua, guru, dan administrasi mengakses informasi pendidikan dalam satu portal.</p></div></div>
    <?php
} elseif ($page === 'announcements') {
    require_login();
    $edit=null;
    if ($role==='admin' && isset($_GET['edit'])) { $st=$pdo->prepare('SELECT * FROM announcements WHERE id=?'); $st->execute([(int)$_GET['edit']]); $edit=$st->fetch(); }
    $items=$pdo->query('SELECT a.*,u.name author FROM announcements a LEFT JOIN users u ON u.id=a.author_id ORDER BY a.created_at DESC')->fetchAll();
    ?>
    <?php if ($role==='admin'): ?><section class="panel form-panel"><div class="panel-heading"><div><span class="eyebrow">PUSAT INFORMASI</span><h3><?= $edit?'Edit pengumuman':'Buat pengumuman baru' ?></h3></div></div><form method="post" class="form-grid"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="save-announcement"><input type="hidden" name="id" value="<?= (int)($edit['id']??0) ?>"><div class="field"><label>Judul pengumuman</label><input name="title" required value="<?= e($edit['title']??'') ?>" placeholder="Contoh: Jadwal ujian semester"></div><div class="field"><label>Kategori</label><select name="category"><option>Informasi</option><option <?= (($edit['category']??'')==='Akademik')?'selected':'' ?>>Akademik</option><option <?= (($edit['category']??'')==='Kegiatan')?'selected':'' ?>>Kegiatan</option><option <?= (($edit['category']??'')==='Penting')?'selected':'' ?>>Penting</option></select></div><div class="field full-field"><label>Isi pengumuman</label><textarea name="body" rows="4" required placeholder="Tuliskan informasi dengan jelas..."><?= e($edit['body']??'') ?></textarea></div><div class="form-actions"><button class="btn primary">Simpan pengumuman</button><?php if($edit): ?><a class="btn light" href="<?= BASE_URL ?>/index.php?page=announcements">Batal</a><?php endif; ?></div></form></section><?php endif; ?>
    <div class="panel"><div class="panel-heading"><div><span class="eyebrow">PAPAN INFORMASI</span><h3>Semua pengumuman</h3></div><span class="count-badge"><?= count($items) ?> informasi</span></div>
    <?php foreach($items as $a): ?><article class="announcement-card"><div class="announcement-symbol large-symbol">▤</div><div class="announcement-content"><div class="row-meta"><span class="tag"><?= e($a['category']) ?></span><span><?= date('d F Y · H:i',strtotime($a['created_at'])) ?></span></div><h3><?= e($a['title']) ?></h3><p><?= nl2br(e($a['body'])) ?></p><small>Dipublikasikan oleh <?= e($a['author']??'Administrator') ?></small></div><?php if($role==='admin'): ?><div class="item-actions"><a class="icon-btn" href="<?= BASE_URL ?>/index.php?page=announcements&edit=<?= (int)$a['id'] ?>">✎</a><form method="post" onsubmit="return confirm('Hapus pengumuman ini?')"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete-announcement"><input type="hidden" name="id" value="<?= (int)$a['id'] ?>"><button class="icon-btn danger-text">×</button></form></div><?php endif; ?></article><?php endforeach; if(!$items): ?><div class="empty-state">Belum ada pengumuman.</div><?php endif; ?></div>
    <?php
} elseif ($page === 'schedule') {
    $edit=null; if($role==='admin' && isset($_GET['edit'])){$st=$pdo->prepare('SELECT * FROM schedules WHERE id=?');$st->execute([(int)$_GET['edit']]);$edit=$st->fetch();}
    $items=$pdo->query("SELECT * FROM schedules ORDER BY FIELD(day_name,'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'),start_time")->fetchAll();
    ?>
    <?php if($role==='admin'): ?><section class="panel form-panel"><div class="panel-heading"><div><span class="eyebrow">MANAJEMEN AKADEMIK</span><h3><?= $edit?'Edit jadwal':'Tambah jadwal pelajaran' ?></h3></div></div><form method="post" class="form-grid"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="save-schedule"><input type="hidden" name="id" value="<?= (int)($edit['id']??0) ?>"><div class="field"><label>Kelas</label><input name="class_name" required value="<?= e($edit['class_name']??'XI RPL 2') ?>"></div><div class="field"><label>Hari</label><select name="day_name"><?php foreach(['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'] as $d): ?><option <?= (($edit['day_name']??'')===$d)?'selected':'' ?>><?= $d ?></option><?php endforeach; ?></select></div><div class="field"><label>Jam mulai</label><input type="time" name="start_time" required value="<?= e(isset($edit['start_time'])?substr($edit['start_time'],0,5):'07:15') ?>"></div><div class="field"><label>Jam selesai</label><input type="time" name="end_time" required value="<?= e(isset($edit['end_time'])?substr($edit['end_time'],0,5):'08:45') ?>"></div><div class="field"><label>Mata pelajaran</label><input name="subject" required value="<?= e($edit['subject']??'') ?>"></div><div class="field"><label>Nama guru</label><input name="teacher" required value="<?= e($edit['teacher']??'') ?>"></div><div class="field"><label>Ruangan</label><input name="room" value="<?= e($edit['room']??'-') ?>"></div><div class="form-actions"><button class="btn primary">Simpan jadwal</button><?php if($edit): ?><a class="btn light" href="<?= BASE_URL ?>/index.php?page=schedule">Batal</a><?php endif; ?></div></form></section><?php endif; ?>
    <section class="panel"><div class="panel-heading"><div><span class="eyebrow">KALENDER AKADEMIK</span><h3>Jadwal pelajaran</h3></div><span class="count-badge"><?= count($items) ?> sesi</span></div><div class="table-wrap"><table><thead><tr><th>Hari</th><th>Waktu</th><th>Mata pelajaran</th><th>Kelas</th><th>Guru</th><th>Ruang</th><?php if($role==='admin'): ?><th>Aksi</th><?php endif; ?></tr></thead><tbody><?php foreach($items as $s): ?><tr><td><b><?= e($s['day_name']) ?></b></td><td><?= e(substr($s['start_time'],0,5)) ?>–<?= e(substr($s['end_time'],0,5)) ?></td><td><?= e($s['subject']) ?></td><td><?= e($s['class_name']) ?></td><td><?= e($s['teacher']) ?></td><td><?= e($s['room']) ?></td><?php if($role==='admin'): ?><td class="actions-cell"><a class="text-link" href="<?= BASE_URL ?>/index.php?page=schedule&edit=<?= (int)$s['id'] ?>">Edit</a><form method="post" onsubmit="return confirm('Hapus jadwal?')"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete-schedule"><input type="hidden" name="id" value="<?= (int)$s['id'] ?>"><button class="text-button danger-text">Hapus</button></form></td><?php endif; ?></tr><?php endforeach; if(!$items): ?><tr><td colspan="7" class="empty-cell">Belum ada jadwal.</td></tr><?php endif; ?></tbody></table></div></section>
    <?php
} elseif ($page === 'attendance') {
    require_role(['admin','guru']);
    $students=$pdo->query('SELECT s.id,s.nis,s.class_name,u.name FROM students s JOIN users u ON u.id=s.user_id ORDER BY s.class_name,u.name')->fetchAll();
    $date=$_GET['date']??date('Y-m-d');
    $st=$pdo->prepare('SELECT a.*,s.nis,s.class_name,u.name FROM attendance a JOIN students s ON s.id=a.student_id JOIN users u ON u.id=s.user_id WHERE a.attendance_date=? ORDER BY s.class_name,u.name');$st->execute([$date]);$records=$st->fetchAll();
    ?>
    <section class="panel form-panel"><div class="panel-heading"><div><span class="eyebrow">PENCATATAN HARIAN</span><h3>Input kehadiran siswa</h3></div></div><form method="post" class="form-grid"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="save-attendance"><div class="field"><label>Nama siswa</label><select name="student_id" required><option value="">Pilih siswa</option><?php foreach($students as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e($s['name'].' · '.$s['class_name'].' · '.$s['nis']) ?></option><?php endforeach; ?></select></div><div class="field"><label>Tanggal</label><input type="date" name="attendance_date" value="<?= e($date) ?>" required></div><div class="field"><label>Status</label><select name="status"><option>Hadir</option><option>Izin</option><option>Sakit</option><option>Alpa</option></select></div><div class="field"><label>Keterangan (opsional)</label><input name="note" placeholder="Keterangan tambahan"></div><div class="form-actions"><button class="btn primary">Simpan kehadiran</button></div></form></section>
    <section class="panel"><div class="panel-heading"><div><span class="eyebrow">REKAP KEHADIRAN</span><h3>Data absensi</h3></div><form method="get" class="inline-filter"><input type="hidden" name="page" value="attendance"><input type="date" name="date" value="<?= e($date) ?>"><button class="btn light small-btn">Tampilkan</button></form></div><div class="table-wrap"><table><thead><tr><th>Nama siswa</th><th>NIS</th><th>Kelas</th><th>Status</th><th>Keterangan</th></tr></thead><tbody><?php foreach($records as $r): ?><tr><td><b><?= e($r['name']) ?></b></td><td><?= e($r['nis']) ?></td><td><?= e($r['class_name']) ?></td><td><span class="status <?= strtolower($r['status']) ?>"><?= e($r['status']) ?></span></td><td><?= e($r['note']) ?></td></tr><?php endforeach; if(!$records): ?><tr><td colspan="5" class="empty-cell">Belum ada data absensi pada tanggal ini.</td></tr><?php endif; ?></tbody></table></div></section>
    <?php
} elseif ($page === 'my-attendance') {
    require_role(['siswa']);
    $st=$pdo->prepare('SELECT a.* FROM attendance a JOIN students s ON s.id=a.student_id WHERE s.user_id=? ORDER BY attendance_date DESC LIMIT 60');$st->execute([current_user()['id']]);$records=$st->fetchAll();
    ?>
    <section class="panel"><div class="panel-heading"><div><span class="eyebrow">REKAM KEHADIRAN</span><h3>Riwayat kehadiran saya</h3></div><span class="count-badge"><?= count($records) ?> catatan</span></div><div class="table-wrap"><table><thead><tr><th>Tanggal</th><th>Status</th><th>Keterangan</th></tr></thead><tbody><?php foreach($records as $r): ?><tr><td><?= date('d M Y',strtotime($r['attendance_date'])) ?></td><td><span class="status <?= strtolower($r['status']) ?>"><?= e($r['status']) ?></span></td><td><?= e($r['note']) ?></td></tr><?php endforeach; if(!$records): ?><tr><td colspan="3" class="empty-cell">Belum ada riwayat kehadiran yang dicatat.</td></tr><?php endif; ?></tbody></table></div></section>
    <?php
} elseif ($page === 'students') {
    require_role(['admin']);
    $edit=null;if(isset($_GET['edit'])){$st=$pdo->prepare('SELECT s.*,u.name,u.email FROM students s JOIN users u ON u.id=s.user_id WHERE s.id=?');$st->execute([(int)$_GET['edit']]);$edit=$st->fetch();}
    $parents=$pdo->query("SELECT id,name FROM users WHERE role='orangtua' ORDER BY name")->fetchAll();
    $items=$pdo->query('SELECT s.*,u.name,u.email,p.name parent_name FROM students s JOIN users u ON u.id=s.user_id LEFT JOIN users p ON p.id=s.parent_user_id ORDER BY s.class_name,u.name')->fetchAll();
    ?>
    <section class="panel form-panel"><div class="panel-heading"><div><span class="eyebrow">DATA PESERTA DIDIK</span><h3><?= $edit?'Edit data siswa':'Tambah siswa baru' ?></h3></div></div><form method="post" class="form-grid"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="save-student"><input type="hidden" name="id" value="<?= (int)($edit['id']??0) ?>"><div class="field"><label>Nama lengkap</label><input name="name" required value="<?= e($edit['name']??'') ?>"></div><div class="field"><label>Email akun siswa</label><input type="email" name="email" required value="<?= e($edit['email']??'') ?>"></div><div class="field"><label>NIS</label><input name="nis" required value="<?= e($edit['nis']??'') ?>"></div><div class="field"><label>Kelas</label><input name="class_name" required value="<?= e($edit['class_name']??'XI RPL 2') ?>"></div><div class="field"><label>Akun orang tua</label><select name="parent_user_id"><option value="0">Belum dihubungkan</option><?php foreach($parents as $p): ?><option value="<?= (int)$p['id'] ?>" <?= ((int)($edit['parent_user_id']??0)===(int)$p['id'])?'selected':'' ?>><?= e($p['name']) ?></option><?php endforeach; ?></select></div><div class="form-actions"><button class="btn primary"><?= $edit?'Simpan perubahan':'Tambah siswa' ?></button><?php if($edit): ?><a class="btn light" href="<?= BASE_URL ?>/index.php?page=students">Batal</a><?php endif; ?></div><?php if(!$edit): ?><p class="form-hint full-field">Akun siswa baru akan dibuat otomatis dengan kata sandi awal acak. Catat kata sandi yang ditampilkan setelah disimpan.</p><?php endif; ?></form></section>
    <section class="panel"><div class="panel-heading"><div><span class="eyebrow">DIREKTORI SEKOLAH</span><h3>Daftar siswa</h3></div><span class="count-badge"><?= count($items) ?> siswa</span></div><div class="table-wrap"><table><thead><tr><th>Nama</th><th>NIS</th><th>Kelas</th><th>Email</th><th>Orang tua</th><th>Aksi</th></tr></thead><tbody><?php foreach($items as $s): ?><tr><td><b><?= e($s['name']) ?></b></td><td><?= e($s['nis']) ?></td><td><?= e($s['class_name']) ?></td><td><?= e($s['email']) ?></td><td><?= e($s['parent_name']??'—') ?></td><td class="actions-cell"><a class="text-link" href="<?= BASE_URL ?>/index.php?page=students&edit=<?= (int)$s['id'] ?>">Edit</a><form method="post" onsubmit="return confirm('Hapus data siswa? Akun siswa tidak otomatis terhapus.')"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete-student"><input type="hidden" name="id" value="<?= (int)$s['id'] ?>"><button class="text-button danger-text">Hapus</button></form></td></tr><?php endforeach; if(!$items): ?><tr><td colspan="6" class="empty-cell">Belum ada siswa.</td></tr><?php endif; ?></tbody></table></div></section>
    <?php
} elseif ($page === 'users') {
    require_role(['admin']);
    $edit=null;if(isset($_GET['edit'])){$st=$pdo->prepare('SELECT id,name,email,role FROM users WHERE id=?');$st->execute([(int)$_GET['edit']]);$edit=$st->fetch();}
    $items=$pdo->query('SELECT id,name,email,role,created_at FROM users ORDER BY role,name')->fetchAll();
    ?>
    <section class="panel form-panel"><div class="panel-heading"><div><span class="eyebrow">AKSES SISTEM</span><h3><?= $edit?'Edit pengguna':'Tambah pengguna' ?></h3></div></div><form method="post" class="form-grid"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="save-user"><input type="hidden" name="id" value="<?= (int)($edit['id']??0) ?>"><div class="field"><label>Nama lengkap</label><input name="name" required value="<?= e($edit['name']??'') ?>"></div><div class="field"><label>Email</label><input type="email" name="email" required value="<?= e($edit['email']??'') ?>"></div><div class="field"><label>Jenis akun</label><select name="role"><?php foreach(['admin','guru','siswa','orangtua'] as $r): ?><option value="<?= $r ?>" <?= (($edit['role']??'')===$r)?'selected':'' ?>><?= e(role_label($r)) ?></option><?php endforeach; ?></select></div><div class="field"><label>Kata sandi <?= $edit?'(kosongkan jika tidak diubah)':'' ?></label><input type="password" name="password" <?= $edit?'':'required' ?> minlength="8" placeholder="Minimal 8 karakter"></div><div class="form-actions"><button class="btn primary">Simpan pengguna</button><?php if($edit): ?><a class="btn light" href="<?= BASE_URL ?>/index.php?page=users">Batal</a><?php endif; ?></div></form></section>
    <section class="panel"><div class="panel-heading"><div><span class="eyebrow">DAFTAR AKUN</span><h3>Pengguna terdaftar</h3></div><span class="count-badge"><?= count($items) ?> akun</span></div><div class="table-wrap"><table><thead><tr><th>Nama</th><th>Email</th><th>Peran</th><th>Dibuat</th><th>Aksi</th></tr></thead><tbody><?php foreach($items as $u): ?><tr><td><b><?= e($u['name']) ?></b></td><td><?= e($u['email']) ?></td><td><span class="tag"><?= e(role_label($u['role'])) ?></span></td><td><?= date('d M Y',strtotime($u['created_at'])) ?></td><td class="actions-cell"><a class="text-link" href="<?= BASE_URL ?>/index.php?page=users&edit=<?= (int)$u['id'] ?>">Edit</a><?php if($u['id']!==current_user()['id']): ?><form method="post" onsubmit="return confirm('Hapus akun ini?')"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete-user"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>"><button class="text-button danger-text">Hapus</button></form><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div></section>
    <?php
} elseif ($page === 'child') {
    require_role(['orangtua']);
    $st=$pdo->prepare('SELECT s.*,u.name FROM students s JOIN users u ON u.id=s.user_id WHERE s.parent_user_id=?');$st->execute([current_user()['id']]);$children=$st->fetchAll();
    ?>
    <?php foreach($children as $child): ?><section class="panel child-card"><div class="child-avatar"><?= e(initials($child['name'])) ?></div><div><span class="eyebrow">INFORMASI PESERTA DIDIK</span><h2><?= e($child['name']) ?></h2><p><?= e($child['class_name']) ?> · NIS <?= e($child['nis']) ?></p></div></section><section class="panel"><div class="panel-heading"><div><span class="eyebrow">PEMANTAUAN</span><h3>Riwayat kehadiran</h3></div></div><?php $st2=$pdo->prepare('SELECT * FROM attendance WHERE student_id=? ORDER BY attendance_date DESC LIMIT 30');$st2->execute([$child['id']]);$hist=$st2->fetchAll(); ?><div class="table-wrap"><table><thead><tr><th>Tanggal</th><th>Status</th><th>Keterangan</th></tr></thead><tbody><?php foreach($hist as $h): ?><tr><td><?= date('d M Y',strtotime($h['attendance_date'])) ?></td><td><span class="status <?= strtolower($h['status']) ?>"><?= e($h['status']) ?></span></td><td><?= e($h['note']) ?></td></tr><?php endforeach; if(!$hist): ?><tr><td colspan="3" class="empty-cell">Belum ada data kehadiran anak.</td></tr><?php endif; ?></tbody></table></div></section><?php endforeach; if(!$children): ?><div class="empty-state panel">Akun orang tua belum dihubungkan ke data siswa. Hubungi administrator sekolah.</div><?php endif; ?>
    <?php
}
require __DIR__ . '/includes/footer.php';