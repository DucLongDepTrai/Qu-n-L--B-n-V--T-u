<?php
require_once __DIR__ . '/../../bootstrap.php';

if (!isset($_SESSION['khach_hang'])) {
    header('Location: ' . BASE_URL . 'modules/auth/dang_nhap_khach.php');
    exit();
}

$conn = $db->getConnection();
$customer = $_SESSION['khach_hang'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_ticket'])) {
    $ticketId = (int)($_POST['ticket_id'] ?? 0);
    $cancel = $conn->prepare("UPDATE ve_tau SET trang_thai = 'Đã hủy' WHERE id = ? AND id_khach_hang = ? AND trang_thai = 'Chờ xác nhận'");
    $cancel->execute([$ticketId, $customer['id_khach_hang']]);
    $message = $cancel->rowCount() ? 'Đã hủy vé thành công.' : 'Vé này không thể hủy hoặc đã được xử lý.';
}

$stmt = $conn->prepare("SELECT vt.id, vt.ma_ve, vt.ngay_dat, vt.gia_ve, vt.trang_thai, lt.ma_lich_trinh, lt.ngay_di, lt.ngay_den,
        td.ten_tuyen, t.ma_tau, g.so_ghe, toa.ma_toa, loai.ten_loai
    FROM ve_tau vt JOIN lich_trinh lt ON lt.id = vt.id_lich_trinh JOIN tuyen_duong td ON td.id = lt.id_tuyen_duong
    JOIN tau t ON t.id = lt.id_tau JOIN ghe g ON g.id = vt.id_ghe JOIN toa_tau toa ON toa.id = g.id_toa_tau
    JOIN loai_toa loai ON loai.id = toa.id_loai_toa
    WHERE vt.id_khach_hang = ? ORDER BY vt.ngay_dat DESC");
$stmt->execute([$customer['id_khach_hang']]);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Vé của tôi</title><link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css"><style>
.ticket-page{width:min(1000px,calc(100% - 32px));margin:34px auto 48px}.ticket-head{display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:24px}.ticket-head h1{margin:0;color:#173f3a}.ticket-card{background:#fff;border:1px solid #e1ebe8;border-radius:10px;padding:22px;margin-bottom:16px;box-shadow:0 5px 16px rgba(0,0,0,.05)}.ticket-top{display:flex;justify-content:space-between;gap:16px;align-items:center;border-bottom:1px solid #e7efed;padding-bottom:14px;margin-bottom:14px}.ticket-code{font-weight:700;color:#1f7a65}.ticket-status{padding:6px 10px;border-radius:20px;background:#fff3cd;color:#664d03;font-size:13px}.ticket-status.paid{background:#d1e7dd;color:#0f5132}.ticket-status.cancelled{background:#f8d7da;color:#842029}.ticket-details{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;color:#526661}.ticket-details strong{display:block;color:#173f3a;margin-top:4px}.ticket-actions{display:flex;justify-content:flex-end;margin-top:18px}.cancel{border:1px solid #dc3545;background:#fff;color:#dc3545;padding:9px 14px;border-radius:6px;cursor:pointer}.empty{background:#fff;padding:32px;text-align:center;border-radius:10px;border:1px solid #e1ebe8}@media(max-width:650px){.ticket-page{width:calc(100% - 24px);margin-top:24px}.ticket-head,.ticket-top{align-items:flex-start;flex-direction:column}.ticket-details{grid-template-columns:1fr 1fr}}
</style></head><body><main class="ticket-page"><div class="ticket-head"><h1>Vé của tôi</h1><a class="button" href="<?php echo BASE_URL; ?>modules/dat-ve/">Đặt vé mới</a></div><?php if ($message): ?><div class="notice"><?php echo htmlspecialchars($message); ?></div><?php endif; ?><?php if (!$tickets): ?><div class="empty"><p>Bạn chưa có vé nào.</p><a href="<?php echo BASE_URL; ?>modules/dat-ve/">Tìm chuyến để đặt vé</a></div><?php else: ?><?php foreach ($tickets as $ticket): ?><article class="ticket-card"><div class="ticket-top"><span class="ticket-code"><?php echo htmlspecialchars($ticket['ma_ve']); ?></span><?php $statusClass = $ticket['trang_thai'] === 'Đã thanh toán' ? 'paid' : ($ticket['trang_thai'] === 'Đã hủy' ? 'cancelled' : ''); ?><span class="ticket-status <?php echo $statusClass; ?>"><?php echo htmlspecialchars($ticket['trang_thai']); ?></span></div><div class="ticket-details"><div>Chuyến<strong><?php echo htmlspecialchars($ticket['ma_lich_trinh'] . ' · ' . $ticket['ma_tau']); ?></strong></div><div>Tuyến<strong><?php echo htmlspecialchars($ticket['ten_tuyen']); ?></strong></div><div>Khởi hành<strong><?php echo date('d/m/Y H:i', strtotime($ticket['ngay_di'])); ?></strong></div><div>Vị trí<strong><?php echo htmlspecialchars($ticket['ma_toa'] . ' - Ghế ' . $ticket['so_ghe']); ?></strong></div><div>Loại toa<strong><?php echo htmlspecialchars($ticket['ten_loai']); ?></strong></div><div>Giá vé<strong><?php echo number_format($ticket['gia_ve'], 0, ',', '.'); ?> đ</strong></div></div><?php if ($ticket['trang_thai'] === 'Chờ xác nhận'): ?><div class="ticket-actions"><form method="post" onsubmit="return confirm('Bạn có chắc muốn hủy vé này?');"><input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>"><button class="cancel" name="cancel_ticket" type="submit">Hủy vé</button></form></div><?php endif; ?></article><?php endforeach; ?><?php endif; ?></main></body></html>
