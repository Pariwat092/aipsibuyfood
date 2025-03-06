<?php
// เปิดการแสดงข้อผิดพลาด
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// เชื่อมต่อฐานข้อมูล (ตัวอย่างเชื่อมต่อแบบพื้นฐาน)
require_once __DIR__ . '/DbHandler.php';
$db = new DbHandler();

// ตรวจสอบว่าคำขอ (Request) เป็น POST หรือไม่
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // รับข้อมูลที่ส่งเข้ามาจาก Omise
    $input = file_get_contents('php://input');
    $event = json_decode($input, true);

    // ตรวจสอบว่ามีข้อมูล Charge ID และสถานะหรือไม่
    if (isset($event['data']['id']) && isset($event['data']['status'])) {
        $chargeId = $event['data']['id'];
        $status = $event['data']['status'];

        echo "<h2>Payment Callback Received</h2>";
        echo "<p>Charge ID: " . $chargeId . "</p>";
        echo "<p>Status: " . $status . "</p>";

        // อัปเดตสถานะการชำระเงินในฐานข้อมูล
        $updateStatus = $db->updatePaymentStatus($chargeId, $status);

        if ($updateStatus) {
            echo "<p>Updated payment status in database successfully.</p>";
        } else {
            echo "<p>Failed to update payment status in database.</p>";
        }
    } else {
        echo "<p>Invalid data received.</p>";
    }
} else {
    echo "<h2>Callback URL is working!</h2>";
}
?>
