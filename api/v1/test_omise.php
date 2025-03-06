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

// สร้าง Charge ใหม่ด้วย Omise
try {
    $charge = OmiseCharge::create([
        'amount' => 50000, // 500 บาท (หน่วยเป็นสตางค์)
        'currency' => 'THB',
        'source' => 'src_test_62y6clcu424jdi11pb5', // Source ID ใหม่ที่ได้มา
        'return_uri' => 'http://localhost/aipsibuyfood/api/v1/callback.php', // URL ที่ให้ผู้ใช้กลับหลังชำระเงินสำเร็จ
        'metadata' => [
            'order_id' => 'ORDER123',
            'description' => 'ชำระเงินค่าสินค้าผ่านพร้อมเพย์'
        ]
    ]);

    // แสดงข้อมูลการสร้าง Charge
    echo "<h2>Omise Charge Created Successfully!</h2>";
    echo "Charge Status: " . $charge['status'] . "<br>";
    echo "Charge ID: " . $charge['id'] . "<br>";
    
    // แสดงลิงก์ไปยัง QR Code สำหรับการชำระเงิน
    if (!empty($charge['authorize_uri'])) {
        echo "<a href='" . $charge['authorize_uri'] . "' target='_blank'>คลิกที่นี่เพื่อชำระเงินผ่าน PromptPay</a><br>";
    } else {
        echo "ไม่มีลิงก์สำหรับการชำระเงิน (Authorize URI ไม่พบ)<br>";
    }

} catch (Exception $e) {
    // แสดงข้อผิดพลาดถ้ามีปัญหาในการเชื่อมต่อกับ Omise
    echo "Omise Connection Error: " . $e->getMessage() . "<br>";
}

// ตรวจสอบการโหลด Class และแสดง Path
echo "Current Directory: " . __DIR__ . "<br>";
echo "Autoload Path: " . realpath(__DIR__ . '/../../vendor/autoload.php') . "<br>";

if (class_exists('OmiseCharge')) {
    echo "Class OmiseCharge Loaded Successfully!<br>";
} else {
    echo "Class OmiseCharge Not Found!<br>";
}
?>
