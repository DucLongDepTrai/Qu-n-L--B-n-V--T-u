<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/includes/header.php';
?>
<div class="container">
    <div class="main-content" style="text-align: center;">
        <h1>Hệ thống đặt vé tàu</h1>
        <p style="font-size: 18px; color: #52616b; margin-bottom: 24px;">Tìm lịch trình, chọn ghế và gửi yêu cầu đặt vé trực tuyến.</p>
        <?php if (isset($_SESSION['khach_hang'])): ?>
            <a href="<?php echo BASE_URL; ?>modules/dat-ve/" style="display: inline-block; padding: 12px 22px; background: #1f7a65; color: #fff; border-radius: 6px; text-decoration: none; font-weight: 600;">Đặt vé ngay</a>
        <?php else: ?>
            <a href="<?php echo BASE_URL; ?>modules/auth/dang_ky.php" style="display: inline-block; padding: 12px 22px; background: #1f7a65; color: #fff; border-radius: 6px; text-decoration: none; font-weight: 600;">Đăng ký để đặt vé</a>
            <a href="<?php echo BASE_URL; ?>modules/auth/dang_nhap_khach.php" style="display: inline-block; padding: 12px 22px; border: 1px solid #1f7a65; color: #1f7a65; border-radius: 6px; text-decoration: none; font-weight: 600; margin-left: 8px;">Đăng nhập</a>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>