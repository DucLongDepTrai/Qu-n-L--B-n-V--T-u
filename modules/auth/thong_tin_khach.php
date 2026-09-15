<?php
require_once __DIR__ . '/../../bootstrap.php';

if (!isset($_SESSION['khach_hang'])) {
    header('Location: ' . BASE_URL . 'modules/auth/dang_nhap_khach.php');
    exit();
}

$conn = $db->getConnection();
$customerId = (int)$_SESSION['khach_hang']['id_khach_hang'];
$message = '';
$error = '';

$stmt = $conn->prepare('SELECT kh.*, tk.email FROM khach_hang kh JOIN tai_khoan_khach_hang tk ON tk.id_khach_hang = kh.id WHERE kh.id = ?');
$stmt->execute([$customerId]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    session_unset();
    header('Location: ' . BASE_URL . 'modules/auth/dang_nhap_khach.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hoTen = trim($_POST['ho_ten'] ?? '');
    $sdt = trim($_POST['sdt'] ?? '');
    $cccd = trim($_POST['cccd'] ?? '');
    $ngaySinh = trim($_POST['ngay_sinh'] ?? '');
    $gioiTinh = trim($_POST['gioi_tinh'] ?? 'Khác');
    $diaChi = trim($_POST['dia_chi'] ?? '');

    if ($hoTen === '' || $sdt === '' || $diaChi === '') {
        $error = 'Vui lòng điền đầy đủ họ tên, số điện thoại và địa chỉ.';
    } elseif (!preg_match(PHONE_NUM_FORMAT, $sdt)) {
        $error = 'Số điện thoại phải gồm 10 chữ số và bắt đầu bằng 0.';
    } elseif ($ngaySinh === '') {
        $error = 'Vui lòng nhập ngày sinh.';
    } else {
        try {
            $update = $conn->prepare('UPDATE khach_hang SET cccd = ?, ho_ten = ?, ngay_sinh = ?, gioi_tinh = ?, sdt = ?, dia_chi = ? WHERE id = ?');
            $update->execute([$cccd !== '' ? $cccd : null, $hoTen, $ngaySinh, $gioiTinh, $sdt, $diaChi, $customerId]);
            $_SESSION['khach_hang']['ho_ten'] = $hoTen;
            $_SESSION['khach_hang']['sdt'] = $sdt;
            $message = 'Đã cập nhật thông tin cá nhân.';
            $stmt->execute([$customerId]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            $error = 'Không thể cập nhật thông tin lúc này.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông tin cá nhân</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css">
    <style>
        .profile-card { max-width: 760px; margin: 36px auto; padding: 32px; background: #fff; border: 1px solid #e1ebe8; border-radius: 12px; box-shadow: 0 8px 24px rgba(23, 63, 58, .07); }
        .profile-card h1 { margin-top: 0; color: #173f3a; }
        .profile-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 18px; }
        .profile-grid label { display: block; color: #34495e; font-weight: 600; }
        .profile-grid input, .profile-grid select { width: 100%; box-sizing: border-box; margin-top: 7px; padding: 11px 12px; border: 1px solid #ced4da; border-radius: 6px; background: #fbfdfc; }
        .profile-grid .full { grid-column: 1 / -1; }
        .profile-actions { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-top: 24px; }
        .profile-actions a { color: #1f7a65; }
        .profile-button { padding: 12px 20px; border: 0; border-radius: 6px; background: #1f7a65; color: #fff; font-weight: 700; cursor: pointer; }
        .profile-message { padding: 12px; margin-bottom: 18px; border-radius: 6px; color: #0f5132; background: #d1e7dd; }
        .profile-error { padding: 12px; margin-bottom: 18px; border-radius: 6px; color: #842029; background: #f8d7da; }
        @media (max-width: 640px) { .profile-card { margin: 24px 12px; padding: 22px; } .profile-grid { grid-template-columns: 1fr; } .profile-grid .full { grid-column: auto; } .profile-actions { align-items: stretch; flex-direction: column-reverse; } }
    </style>
</head>
<body>
<main class="profile-card">
    <h1>Thông tin cá nhân</h1>
    <p>Cập nhật thông tin để việc đặt vé và liên hệ được chính xác.</p>
    <?php if ($message): ?><div class="profile-message"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="profile-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <form method="post">
        <div class="profile-grid">
            <label>Họ tên *<input name="ho_ten" required value="<?php echo htmlspecialchars($customer['ho_ten']); ?>"></label>
            <label>Email tài khoản<input type="email" value="<?php echo htmlspecialchars($customer['email']); ?>" disabled></label>
            <label>Số điện thoại *<input name="sdt" required pattern="0[0-9]{9}" value="<?php echo htmlspecialchars($customer['sdt']); ?>"></label>
            <label>CCCD<input name="cccd" value="<?php echo htmlspecialchars($customer['cccd'] ?? ''); ?>"></label>
            <label>Ngày sinh *<input type="date" name="ngay_sinh" required value="<?php echo htmlspecialchars($customer['ngay_sinh']); ?>"></label>
            <label>Giới tính<select name="gioi_tinh"><option <?php echo $customer['gioi_tinh'] === 'Nam' ? 'selected' : ''; ?>>Nam</option><option <?php echo $customer['gioi_tinh'] === 'Nữ' ? 'selected' : ''; ?>>Nữ</option><option <?php echo $customer['gioi_tinh'] === 'Khác' ? 'selected' : ''; ?>>Khác</option></select></label>
            <label class="full">Địa chỉ *<input name="dia_chi" required value="<?php echo htmlspecialchars($customer['dia_chi']); ?>"></label>
        </div>
        <div class="profile-actions"><a href="<?php echo BASE_URL; ?>modules/dat-ve/">Quay lại đặt vé</a><button class="profile-button" type="submit">Lưu thay đổi</button></div>
    </form>
</main>
</body>
</html>
