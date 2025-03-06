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

try {
    // สร้าง Source ใหม่สำหรับ PromptPay
    $source = OmiseSource::create([
        'amount' => 50000, // จำนวนเงินในหน่วยสตางค์ (500.00 บาท)
        'currency' => 'THB',
        'type' => 'promptpay', // ประเภทการชำระเงินเป็น PromptPay
    ]);

    // ดึงค่า Source ID มาใช้งาน
    $sourceId = $source['id'];

    // สร้าง Charge ใหม่โดยใช้ Source ID ที่เพิ่งสร้างขึ้น
    $charge = OmiseCharge::create([
        'amount' => 50000, // จำนวนเงินในหน่วยสตางค์
        'currency' => 'THB',
        'source' => $sourceId, // ใช้ Source ID ที่ได้มาจากการสร้าง Source ข้างต้น
        'return_uri' => 'http://localhost/aipsibuyfood/api/v1/callback.php',
        'metadata' => [
            'order_id' => 'ORDER123',
            'description' => 'ชำระเงินค่าสินค้าผ่านพร้อมเพย์'
        ]
    ]);

    if ($charge['status'] === 'pending' && !empty($charge['authorize_uri'])) {
        echo "<h1>Omise Charge Created Successfully!</h1>";
        echo "<p>Charge Status: " . $charge['status'] . "</p>";
        echo "<p>Charge ID: " . $charge['id'] . "</p>";
        
        echo "<h2>สแกน QR Code ชำระเงินผ่าน PromptPay:</h2>";
    
        // เปลี่ยนการแสดงผล QR Code ให้ถูกต้อง
        echo "<a href='" . $charge['authorize_uri'] . "' target='_blank'>";
        echo "<img src='https://api.qrserver.com/v1/create-qr-code/?data=" . urlencode($charge['authorize_uri']) . "&size=300x300' alt='QR Code' style='width:300px;height:300px;'>";
        echo "</a>";
    
        echo "<br><a href='" . $charge['authorize_uri'] . "' target='_blank'>หรือคลิกที่นี่เพื่อชำระเงินผ่าน PromptPay</a>";
    } else {
        echo "ไม่สามารถสร้าง Charge ได้: " . $charge['failure_message'];
    }

} catch (Exception $e) {
}
?>
