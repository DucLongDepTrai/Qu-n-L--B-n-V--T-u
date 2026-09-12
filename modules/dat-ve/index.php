<?php
require_once __DIR__ . '/../../bootstrap.php';

if (!isset($_SESSION['khach_hang'])) {
    header('Location: ' . BASE_URL . 'modules/auth/dang_nhap_khach.php');
    exit();
}

$conn = $db->getConnection();
$customer = $_SESSION['khach_hang'];
$message = '';
$error = '';
$selectedSchedule = (int)($_GET['lich_trinh'] ?? $_POST['id_lich_trinh'] ?? 0);
$selectedSeat = (int)($_POST['id_ghe'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book'])) {
    $selectedSchedule = (int)($_POST['id_lich_trinh'] ?? 0);
    $selectedSeat = (int)($_POST['id_ghe'] ?? 0);
    try {
        if (!$selectedSchedule || !$selectedSeat) {
            throw new RuntimeException('Vui lòng chọn lịch trình và ghế.');
        }
        $conn->beginTransaction();
        $scheduleStmt = $conn->prepare("SELECT lt.id, lt.ma_lich_trinh, lt.ngay_di, lt.trang_thai,
                    td.gia_co_ban, g.id AS id_ghe, g.so_ghe, toa.ma_toa, loai.he_so_gia
                FROM lich_trinh lt
                JOIN tuyen_duong td ON td.id = lt.id_tuyen_duong
                JOIN tau ON tau.id = lt.id_tau
                JOIN ghe g ON g.id_toa_tau IN (SELECT id FROM toa_tau WHERE id_tau = lt.id_tau)
                JOIN toa_tau toa ON toa.id = g.id_toa_tau
                JOIN loai_toa loai ON loai.id = toa.id_loai_toa
                WHERE lt.id = ? AND g.id = ? AND lt.ngay_di > NOW()
                FOR UPDATE");
        $scheduleStmt->execute([$selectedSchedule, $selectedSeat]);
        $seat = $scheduleStmt->fetch(PDO::FETCH_ASSOC);
        if (!$seat) {
            throw new RuntimeException('Lịch trình không tồn tại, đã khởi hành hoặc ghế không thuộc tàu này.');
        }
        $occupied = $conn->prepare("SELECT id FROM ve_tau WHERE id_lich_trinh = ? AND id_ghe = ? AND trang_thai <> 'Đã hủy' FOR UPDATE");
        $occupied->execute([$selectedSchedule, $selectedSeat]);
        if ($occupied->fetch()) {
            throw new RuntimeException('Ghế này vừa được người khác đặt. Vui lòng chọn ghế khác.');
        }
        $ticketCode = 'KH-' . date('ymdHis') . '-' . random_int(100, 999);
        $price = $seat['gia_co_ban'] * $seat['he_so_gia'];
        $insert = $conn->prepare("INSERT INTO ve_tau (ma_ve, id_khach_hang, id_lich_trinh, id_ghe, id_nhan_vien, gia_ve, trang_thai) VALUES (?, ?, ?, ?, NULL, ?, 'Chờ xác nhận')");
        $insert->execute([$ticketCode, $customer['id_khach_hang'], $selectedSchedule, $selectedSeat, $price]);
        $conn->commit();
        $message = "Đặt vé thành công. Mã vé của bạn: {$ticketCode}. Nhân viên sẽ xác nhận sau.";
        $selectedSeat = 0;
    } catch (Throwable $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        $error = $e instanceof RuntimeException ? $e->getMessage() : 'Không thể đặt vé lúc này.';
    }
}

$schedules = $conn->query("SELECT lt.id, lt.ma_lich_trinh, lt.ngay_di, lt.ngay_den, t.ma_tau, td.ten_tuyen
    FROM lich_trinh lt JOIN tau t ON t.id = lt.id_tau JOIN tuyen_duong td ON td.id = lt.id_tuyen_duong
    WHERE lt.ngay_di > NOW() ORDER BY lt.ngay_di")->fetchAll(PDO::FETCH_ASSOC);

$seats = [];
$chosen = null;
if ($selectedSchedule) {
    $scheduleStmt = $conn->prepare("SELECT lt.id, lt.ma_lich_trinh, lt.ngay_di, lt.ngay_den, td.ten_tuyen, td.gia_co_ban,
            g.id AS id_ghe, g.so_ghe, toa.ma_toa, loai.ten_loai, loai.he_so_gia,
            CASE WHEN EXISTS (SELECT 1 FROM ve_tau vt WHERE vt.id_lich_trinh = lt.id AND vt.id_ghe = g.id AND vt.trang_thai <> 'Đã hủy') THEN 1 ELSE 0 END AS da_dat
        FROM lich_trinh lt JOIN tuyen_duong td ON td.id = lt.id_tuyen_duong
        JOIN ghe g ON g.id_toa_tau IN (SELECT id FROM toa_tau WHERE id_tau = lt.id_tau)
        JOIN toa_tau toa ON toa.id = g.id_toa_tau JOIN loai_toa loai ON loai.id = toa.id_loai_toa
        WHERE lt.id = ? ORDER BY toa.ma_toa, g.so_ghe");
    $scheduleStmt->execute([$selectedSchedule]);
    $seats = $scheduleStmt->fetchAll(PDO::FETCH_ASSOC);
    $chosen = $seats[0] ?? null;
}
?>
<!DOCTYPE html>
<html lang="vi"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Đặt vé tàu</title><link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css"><style>
.booking{max-width:1100px;margin:30px auto 60px}.booking h1{color:#173f3a;font-size:34px;letter-spacing:-.3px}.booking h2{color:#173f3a}.booking .panel{background:#fff;padding:28px;border:1px solid #e1ebe8;border-radius:14px;box-shadow:0 10px 30px rgba(23,63,58,.07);margin-bottom:24px}.booking .panel > p{color:#60716e;line-height:1.6}.schedule-list{display:grid;gap:14px}.schedule{display:flex;justify-content:space-between;align-items:center;padding:20px;border:1px solid #d9e8e3;border-radius:12px;gap:24px;background:linear-gradient(115deg,#fff,#f7fbf9);transition:.2s}.schedule:hover{border-color:#1f7a65;box-shadow:0 8px 20px rgba(31,122,101,.1);transform:translateY(-2px)}.schedule strong{color:#1f7a65;font-size:16px}.schedule-route{display:flex;gap:10px;align-items:center;margin:10px 0 5px;font-weight:700;color:#173f3a}.schedule-route span{color:#9aada8}.schedule-meta{color:#60716e;font-size:14px}.button{display:inline-block;padding:11px 18px;background:#1f7a65;color:#fff;text-decoration:none;border:0;border-radius:7px;cursor:pointer;font-weight:700;transition:.2s}.button:hover{background:#165a4b;transform:translateY(-1px)}.booking .legend{display:flex;flex-wrap:wrap;gap:16px;margin:18px 0 4px;color:#60716e;font-size:13px}.legend-item{display:flex;align-items:center;gap:7px}.legend-dot{width:12px;height:12px;border-radius:3px;border:1px solid #1f7a65;background:#fff}.legend-dot.selected{background:#1f7a65}.legend-dot.busy{background:#e9ecef;border-color:#adb5bd}.seat{padding:14px 8px;text-align:center;border:1px solid #1f7a65;border-radius:9px;background:#fff;cursor:pointer;min-height:78px;transition:.15s}.seat:hover:not(.busy){background:#eaf4f0;transform:translateY(-2px)}.seat input{display:none}.seat:has(input:checked){background:#1f7a65;color:#fff;box-shadow:0 5px 12px rgba(31,122,101,.25)}.seat.busy{background:#e9ecef;border-color:#adb5bd;color:#6c757d;cursor:not-allowed}.booking .selection-bar{display:flex;justify-content:space-between;align-items:center;gap:16px;margin-top:24px;padding:16px 18px;border-radius:10px;background:#f0f7f4;color:#526d66}.booking .selection-bar strong{color:#173f3a}.notice{padding:15px 18px;border-radius:9px;background:#d1e7dd;color:#0f5132;margin-bottom:18px}.warning{padding:15px 18px;border-radius:9px;background:#f8d7da;color:#842029;margin-bottom:18px}.empty-state{text-align:center;padding:32px;background:#f8fbfa;border:1px dashed #b8ccc5;border-radius:10px;color:#60716e}@media(max-width:650px){.schedule{align-items:stretch;flex-direction:column;padding:16px}.schedule .button{width:100%;text-align:center}.booking .panel{padding:20px 16px}.booking .selection-bar{align-items:stretch;flex-direction:column}.booking h1{font-size:28px}}
</style></head><body><div class="booking"><div style="display:flex;justify-content:space-between;gap:15px;align-items:center;flex-wrap:wrap"><h1>Đặt vé tàu</h1><span>Xin chào, <?php echo htmlspecialchars($customer['ho_ten']); ?> · <a href="<?php echo BASE_URL; ?>modules/auth/dang_xuat_khach.php">Đăng xuất</a></span></div>
<?php if ($message): ?><div class="notice"><?php echo htmlspecialchars($message); ?></div><?php endif; ?><?php if ($error): ?><div class="warning"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<div class="panel"><h2>Chọn lịch trình</h2><p>Chọn chuyến phù hợp, sau đó chọn toa và vị trí ngồi.</p><?php if (!$schedules): ?><div class="empty-state">Hiện chưa có lịch trình sắp tới.</div><?php else: ?><div class="schedule-list"><?php foreach ($schedules as $schedule): ?><div class="schedule"><div><strong><?php echo htmlspecialchars($schedule['ma_lich_trinh']); ?> · <?php echo htmlspecialchars($schedule['ma_tau']); ?></strong><div class="schedule-route"><?php echo htmlspecialchars($schedule['ten_tuyen']); ?> <span>→</span> <?php echo date('d/m/Y', strtotime($schedule['ngay_di'])); ?></div><div class="schedule-meta">Khởi hành <?php echo date('H:i', strtotime($schedule['ngay_di'])); ?> · Dự kiến đến <?php echo date('d/m/Y H:i', strtotime($schedule['ngay_den'])); ?></div></div><a class="button" href="?lich_trinh=<?php echo $schedule['id']; ?>">Xem ghế trống</a></div><?php endforeach; ?></div><?php endif; ?></div>
<?php if ($selectedSchedule && $seats): ?>
    <div class="panel">
        <h2>Chọn toa và ghế</h2>
        <p>Chọn đúng loại toa bên dưới. Giá vé hiển thị theo từng toa để bạn dễ so sánh.</p>
        <div class="legend">
            <span class="legend-item"><i class="legend-dot"></i> Còn trống</span>
            <span class="legend-item"><i class="legend-dot selected"></i> Đang chọn</span>
            <span class="legend-item"><i class="legend-dot busy"></i> Đã đặt</span>
        </div>
        <form method="post">
            <input type="hidden" name="id_lich_trinh" value="<?php echo $selectedSchedule; ?>">
            <div class="coach-list">
                <?php $currentCoach = null; foreach ($seats as $seat): ?>
                    <?php if ($currentCoach !== $seat['ma_toa']): ?>
                        <?php if ($currentCoach !== null): ?></div></div><?php endif; ?>
                        <?php $currentCoach = $seat['ma_toa']; ?>
                        <div class="coach">
                            <div class="coach-header">
                                <span class="coach-name"><?php echo htmlspecialchars($seat['ma_toa']); ?></span>
                                <span class="coach-type"><?php echo htmlspecialchars($seat['ten_loai']); ?> · <?php echo number_format($seat['gia_co_ban'] * $seat['he_so_gia'], 0, ',', '.'); ?> đ</span>
                            </div>
                            <div class="coach-seats">
                    <?php endif; ?>
                    <label class="seat <?php echo $seat['da_dat'] ? 'busy' : ''; ?>">
                        <input type="radio" name="id_ghe" value="<?php echo $seat['id_ghe']; ?>" <?php echo (!$seat['da_dat'] && $selectedSeat === (int)$seat['id_ghe']) ? 'checked' : ''; ?> <?php echo $seat['da_dat'] ? 'disabled' : ''; ?>>
                        <b><?php echo htmlspecialchars($seat['so_ghe']); ?></b>
                        <small><?php echo $seat['da_dat'] ? 'Đã đặt' : 'Còn trống'; ?></small>
                    </label>
                <?php endforeach; ?>
                <?php if ($currentCoach !== null): ?></div></div><?php endif; ?>
            </div>
            <div class="selection-bar">
                <span><strong>Đã chọn:</strong> <span id="selected-seat-label">Chưa chọn ghế</span></span>
                <button class="button" name="book" type="submit">Xác nhận đặt vé</button>
            </div>
        </form>
    </div>
<?php elseif ($selectedSchedule): ?>
    <div class="panel"><p>Không tìm thấy ghế cho lịch trình này.</p></div>
<?php endif; ?>
</div><script>document.querySelectorAll('.seat input').forEach(function(input){input.addEventListener('change',function(){var label=document.getElementById('selected-seat-label');label.textContent=this.closest('.seat').querySelector('b').textContent;});});</script></body></html>
