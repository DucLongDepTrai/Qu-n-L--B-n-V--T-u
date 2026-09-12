<?php
require_once __DIR__ . '/../../bootstrap.php';

if (isset($_SESSION['khach_hang'])) {
    header('Location: ' . BASE_URL . 'modules/dat-ve/');
    exit();
}

$conn = $db->getConnection();
$conn->exec("CREATE TABLE IF NOT EXISTS tai_khoan_khach_hang (
    id INT NOT NULL AUTO_INCREMENT,
    id_khach_hang INT NOT NULL,
    email VARCHAR(100) NOT NULL,
    mat_khau VARCHAR(255) NOT NULL,
    ngay_tao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY email (email),
    UNIQUE KEY id_khach_hang (id_khach_hang),
    CONSTRAINT tai_khoan_khach_hang_fk FOREIGN KEY (id_khach_hang) REFERENCES khach_hang (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$errors = [];
$old = ['ho_ten' => '', 'email' => '', 'sdt' => '', 'cccd' => '', 'dia_chi' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($old as $key => $_) {
        $old[$key] = trim($_POST[$key] ?? '');
    }
    $mat_khau = $_POST['mat_khau'] ?? '';
    $xac_nhan_mat_khau = $_POST['xac_nhan_mat_khau'] ?? '';

    if ($old['ho_ten'] === '' || $old['email'] === '' || $old['sdt'] === '' || $old['dia_chi'] === '' || $mat_khau === '') {
        $errors[] = 'Vui lòng điền đầy đủ thông tin bắt buộc.';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email không hợp lệ.';
    }
    if (!preg_match(PHONE_NUM_FORMAT, $old['sdt'])) {
        $errors[] = 'Số điện thoại phải gồm 10 chữ số và bắt đầu bằng 0.';
    }
    if (strlen($mat_khau) < 6) {
        $errors[] = 'Mật khẩu phải có ít nhất 6 ký tự.';
    }
    if ($mat_khau !== $xac_nhan_mat_khau) {
        $errors[] = 'Mật khẩu xác nhận không khớp.';
    }

    if (!$errors) {
        try {
            $conn->beginTransaction();
            $check = $conn->prepare('SELECT id FROM tai_khoan_khach_hang WHERE email = ?');
            $check->execute([$old['email']]);
            if ($check->fetch()) {
                throw new RuntimeException('Email này đã được đăng ký.');
            }

            $insertCustomer = $conn->prepare(
                'INSERT INTO khach_hang (cccd, ho_ten, ngay_sinh, gioi_tinh, sdt, dia_chi) VALUES (?, ?, ?, ?, ?, ?)'
            );
            $insertCustomer->execute([
                $old['cccd'] !== '' ? $old['cccd'] : null,
                $old['ho_ten'],
                $_POST['ngay_sinh'] ?: date('Y-m-d'),
                $_POST['gioi_tinh'] ?: 'Khác',
                $old['sdt'],
                $old['dia_chi']
            ]);
            $customerId = $conn->lastInsertId();

            $insertAccount = $conn->prepare('INSERT INTO tai_khoan_khach_hang (id_khach_hang, email, mat_khau) VALUES (?, ?, ?)');
            $insertAccount->execute([$customerId, $old['email'], password_hash($mat_khau, PASSWORD_DEFAULT)]);
            $conn->commit();

            header('Location: ' . BASE_URL . 'modules/auth/dang_nhap_khach.php?registered=1');
            exit();
        } catch (Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $errors[] = $e instanceof RuntimeException ? $e->getMessage() : 'Không thể tạo tài khoản. Email, số điện thoại hoặc CCCD có thể đã tồn tại.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký khách hàng</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css">
    <style>
        .auth-card { max-width: 760px; margin: 40px auto; padding: 36px; background: #fff; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,.08); }
        .auth-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px 18px; }
        .auth-grid label { display: block; font-weight: 600; color: #34495e; }
        .auth-grid input, .auth-grid select { width: 100%; padding: 11px 12px; margin-top: 8px; border: 1px solid #ced4da; border-radius: 6px; background: #fbfdfc; }
        .auth-grid .full { grid-column: 1 / -1; }
        .auth-button { width: 100%; padding: 13px; border: 0; border-radius: 6px; background: #1f7a65; color: #fff; font-weight: 700; cursor: pointer; margin-top: 22px; }
        .auth-error { padding: 12px; margin-bottom: 16px; color: #842029; background: #f8d7da; border-radius: 6px; }
        @media (max-width: 640px) { .auth-grid { grid-template-columns: 1fr; } .auth-grid .full { grid-column: auto; } }
    </style>
</head>
<body>
<div class="auth-card">
    <h1>Đăng ký tài khoản khách hàng</h1>
    <?php if ($errors): ?><div class="auth-error"><?php echo htmlspecialchars(implode(' ', $errors)); ?></div><?php endif; ?>
    <form method="post">
        <div class="auth-grid">
            <label>Họ tên *<input name="ho_ten" required value="<?php echo htmlspecialchars($old['ho_ten']); ?>"></label>
            <label>Email *<input type="email" name="email" required value="<?php echo htmlspecialchars($old['email']); ?>"></label>
            <label>Số điện thoại *<input name="sdt" required pattern="0[0-9]{9}" value="<?php echo htmlspecialchars($old['sdt']); ?>"></label>
            <label>CCCD<input name="cccd" value="<?php echo htmlspecialchars($old['cccd']); ?>"></label>
            <label>Ngày sinh<input type="date" name="ngay_sinh"></label>
            <label>Giới tính<select name="gioi_tinh"><option>Nam</option><option>Nữ</option><option>Khác</option></select></label>
            <label class="full">Địa chỉ *<input name="dia_chi" required value="<?php echo htmlspecialchars($old['dia_chi']); ?>"></label>
            <label>Mật khẩu *<input type="password" name="mat_khau" required minlength="6"></label>
            <label>Nhập lại mật khẩu *<input type="password" name="xac_nhan_mat_khau" required minlength="6"></label>
        </div>
        <button class="auth-button" type="submit">Đăng ký</button>
    </form>
    <p style="text-align:center;">Đã có tài khoản? <a href="dang_nhap_khach.php">Đăng nhập</a></p>
</div>
</body>
</html>
