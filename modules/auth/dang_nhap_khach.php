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
    PRIMARY KEY (id), UNIQUE KEY email (email), UNIQUE KEY id_khach_hang (id_khach_hang),
    CONSTRAINT tai_khoan_khach_hang_fk FOREIGN KEY (id_khach_hang) REFERENCES khach_hang (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $mat_khau = $_POST['mat_khau'] ?? '';
    $stmt = $conn->prepare('SELECT tk.*, kh.ho_ten, kh.sdt FROM tai_khoan_khach_hang tk JOIN khach_hang kh ON kh.id = tk.id_khach_hang WHERE tk.email = ?');
    $stmt->execute([$email]);
    $account = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($account && password_verify($mat_khau, $account['mat_khau'])) {
        $_SESSION['khach_hang'] = $account;
        header('Location: ' . BASE_URL . 'modules/dat-ve/');
        exit();
    }
    $error = 'Email hoặc mật khẩu không đúng.';
}
?>
<!DOCTYPE html>
<html lang="vi"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Đăng nhập khách hàng</title><link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css"><style>
.auth-card{max-width:430px;margin:70px auto;padding:36px;background:#fff;border-radius:12px;box-shadow:0 5px 20px rgba(0,0,0,.08)}.auth-card label{display:block;font-weight:600;color:#34495e;margin:20px 0}.auth-card input{width:100%;padding:12px;margin-top:8px;border:1px solid #ced4da;border-radius:6px;background:#fbfdfc}.auth-button{width:100%;padding:13px;border:0;border-radius:6px;background:#1f7a65;color:#fff;font-weight:700;cursor:pointer;margin-top:6px}.auth-error{padding:12px;color:#842029;background:#f8d7da;border-radius:6px;margin-bottom:18px}
</style></head><body><div class="auth-card"><h1>Đăng nhập khách hàng</h1><?php if ($error): ?><div class="auth-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?><?php if (isset($_GET['registered'])): ?><p style="color:#1f7a65;">Đăng ký thành công. Hãy đăng nhập để đặt vé.</p><?php endif; ?><form method="post"><label>Email<input type="email" name="email" required></label><label>Mật khẩu<input type="password" name="mat_khau" required></label><button class="auth-button" type="submit">Đăng nhập</button></form><p style="text-align:center;"><a href="dang_ky.php">Tạo tài khoản mới</a> · <a href="<?php echo BASE_URL; ?>">Trang chủ</a></p></div></body></html>
