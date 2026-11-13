<?php
require_once __DIR__ . '/config/database.php';
$messages = [];
$error = '';
try {
    $pdo = db();
    $pdo->beginTransaction();
    // Pastikan akun demo selalu tersedia. Password demo disetel ulang saat installer dijalankan.
    $accounts = [
        ['Administrator RYZEN','admin@ryzen.sch.id','Admin123!','admin'],
        ['Budi Santoso','guru@ryzen.sch.id','Guru123!','guru'],
        ['Andi Pratama','siswa@ryzen.sch.id','Siswa123!','siswa'],
        ['Orang Tua Andi','ortu@ryzen.sch.id','Ortu123!','orangtua'],
    ];
    $stmt = $pdo->prepare("INSERT INTO users(name,email,password,role) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE name=VALUES(name), password=VALUES(password), role=VALUES(role)");
    foreach ($accounts as $a) $stmt->execute([$a[0],$a[1],password_hash($a[2], PASSWORD_DEFAULT),$a[3]]);
        $studentUser = (int)$pdo->query("SELECT id FROM users WHERE email='siswa@ryzen.sch.id'")->fetchColumn();
        $parentUser = (int)$pdo->query("SELECT id FROM users WHERE email='ortu@ryzen.sch.id'")->fetchColumn();
        $studentExists = $pdo->prepare('SELECT id FROM students WHERE user_id=?');
        $studentExists->execute([$studentUser]);
        if (!$studentExists->fetchColumn()) {
            $pdo->prepare("INSERT INTO students(user_id,nis,class_name,parent_user_id) VALUES(?,?,?,?)")
                ->execute([$studentUser,'2026001','XI RPL 2',$parentUser]);
        }
        $adminId = (int)$pdo->query("SELECT id FROM users WHERE email='admin@ryzen.sch.id'")->fetchColumn();
        $announcementExists = (int)$pdo->query('SELECT COUNT(*) FROM announcements')->fetchColumn();
        if (!$announcementExists) $pdo->prepare("INSERT INTO announcements(title,category,body,author_id) VALUES(?,?,?,?)")->execute([
            'Selamat datang di portal RYZEN','Informasi',
            'Portal RYZEN menyatukan informasi kehadiran, jadwal pelajaran, dan pengumuman sekolah dalam satu tempat. Data contoh ini dapat diubah oleh administrator.',
            $adminId
        ]);
        $scheduleExists = (int)$pdo->query('SELECT COUNT(*) FROM schedules')->fetchColumn();
        if (!$scheduleExists) {
        $sched = $pdo->prepare("INSERT INTO schedules(class_name,day_name,start_time,end_time,subject,teacher,room) VALUES(?,?,?,?,?,?,?)");
        $rows = [
            ['XI RPL 2','Senin','07:15','08:45','Pemrograman Web','Budi Santoso','Lab RPL'],
            ['XI RPL 2','Senin','09:00','10:30','Basis Data','Budi Santoso','Lab RPL'],
            ['XI RPL 2','Selasa','07:15','08:45','Matematika','Ibu Sari','R. 12'],
            ['XI RPL 2','Rabu','07:15','08:45','Bahasa Indonesia','Ibu Rina','R. 12'],
            ['XI RPL 2','Kamis','08:00','09:30','Pemrograman Berorientasi Objek','Budi Santoso','Lab RPL'],
            ['XI RPL 2','Jumat','07:15','08:00','Pendidikan Pancasila','Pak Agus','R. 12'],
        ];
        foreach ($rows as $r) $sched->execute($r);
        }
        $pdo->commit();
        $messages[] = 'Akun demo dipastikan tersedia. Data contoh yang sudah ada tetap dipertahankan.';
} catch (Throwable $e) { if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack(); $error = $e->getMessage(); }
?>
<!doctype html><html lang="id"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Instalasi RYZEN</title>
<link rel="stylesheet" href="assets/style.css"><body class="install-body"><div class="install-card"><div class="brand-mark large">R</div><p class="eyebrow">PENGATURAN AWAL</p><h1>Instalasi RYZEN</h1>
<?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><p>Pastikan database <b>ryzen_school</b> sudah dibuat dan file SQL sudah diimpor.</p><?php else: foreach($messages as $m): ?><div class="alert success"><?= htmlspecialchars($m) ?></div><?php endforeach; ?>
<h3>Akun demo</h3><div class="credentials"><p><b>Admin</b><br>admin@ryzen.sch.id / Admin123!</p><p><b>Guru</b><br>guru@ryzen.sch.id / Guru123!</p><p><b>Siswa</b><br>siswa@ryzen.sch.id / Siswa123!</p><p><b>Orang tua</b><br>ortu@ryzen.sch.id / Ortu123!</p></div>
<a class="btn primary full" href="index.php">Buka RYZEN</a><?php endif; ?>
<p class="muted tiny">Setelah berhasil, hapus file install.php demi keamanan.</p></div></body></html>
