<?php
// เปิดการแสดงข้อผิดพลาด
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// เรียกใช้ autoload.php ด้วยเส้นทางที่ถูกต้อง
require_once __DIR__ . '/../../vendor/autoload.php';

// ตั้งค่า Omise API Key สำหรับ Test Mode
define('OMISE_PUBLIC_KEY', 'pkey_test_62y5mnseze4nbq0negp');
define('OMISE_SECRET_KEY', 'skey_test_62y5momcxu2at1pmrf1');

header('Content-Type: application/json; charset=utf-8');

try {
    // รับจำนวนเงินจากการร้องขอ (รับจาก Flutter ผ่าน POST)
    $amount = isset($_POST['amount']) ? intval($_POST['amount']) : 0;
    
    if ($amount <= 0) {
        echo json_encode([
            'status' => 'failed',
            'message' => 'Invalid amount'
        ]);
        exit;
    }

    // สร้าง Source ใหม่สำหรับ PromptPay
    $source = OmiseSource::create([
        'amount' => $amount, // จำนวนเงินในหน่วยสตางค์
        'currency' => 'THB',
        'type' => 'promptpay', // ประเภทการชำระเงินเป็น PromptPay
    ]);

    // ดึงค่า Source ID มาใช้งาน
    $sourceId = $source['id'];

    // สร้าง Charge ใหม่โดยใช้ Source ID ที่เพิ่งสร้างขึ้น
    $charge = OmiseCharge::create([
        'amount' => $amount, // จำนวนเงินในหน่วยสตางค์
        'currency' => 'THB',
        'source' => $sourceId, 
        'return_uri' => 'http://localhost/aipsibuyfood/api/v1/callback.php',
        'metadata' => [
            'order_id' => 'ORDER123',
            'description' => 'ชำระเงินค่าสินค้าผ่านพร้อมเพย์'
        ]
    ]);

    if ($charge['status'] === 'pending' && !empty($charge['authorize_uri'])) {
        // ส่งข้อมูลเป็น JSON กลับไปยัง Flutter
        echo json_encode([
            'status' => 'success',
            'charge_id' => $charge['id'],
            'qr_code_url' => "https://api.qrserver.com/v1/create-qr-code/?data=" . urlencode($charge['authorize_uri']) . "&size=300x300",
            'authorize_uri' => $charge['authorize_uri']
        ]);
    } else {
        echo json_encode([
            'status' => 'failed',
            'message' => $charge['failure_message'] ?? 'Unable to create charge'
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'status' => 'failed',
        'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
    ]);
}
