<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// ini_set('memory_limit', '512M'); // เพิ่มหน่วยความจำเป็น 512MB
// ini_set('max_execution_time', '300'); // เพิ่มเวลารันสูงสุดเป็น 300 วินาที (5 นาที)
// ini_set('max_input_vars', '10000'); //เพิ่มจำนวนตัวแปรอินพุตที่รับได้

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Credentials: true");
header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
header('Access-Control-Max-Age: 1000');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token , Authorization');

require_once './DbHandler.php';
require_once '../include/Config.php';
// require '../vendor/autoload.php';
require_once __DIR__ . '/../../vendor/autoload.php';






define('OMISE_PUBLIC_KEY', 'pkey_test_62y5cae8l0id0e8x15k');
define('OMISE_SECRET_KEY', 'skey_test_62hy9gjjvithz8bycfg');

use Omise\OmiseCharge;
use Omise\OmiseCustomer;
use Omise\OmiseSource;


use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Factory\AppFactory;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;



$app = AppFactory::create();
$app->setBasePath('/aipsibuyfood/api/v1');



// $app->get('/check-settings', function (Request $request, $response, $args) {
//     $settings = [
//         'memory_limit' => ini_get('memory_limit'),
//         'max_execution_time' => ini_get('max_execution_time'),
//         'max_input_vars' => ini_get('max_input_vars')
//     ];
//     $response->getBody()->write(json_encode($settings));
//     return $response->withHeader('Content-Type', 'application/json');
// });


// ตรวจสอบ api
$app->get('/checkapi', function($request, $response, $args) use ($app) {
    $data = array();
    $data["res_code"] = "00";
    $data["res_text"] = "แสดงข้อมูลสำเร็จ";
    return echoRespnse($response, 200, $data);
});

// user_customer//////////////////////

$app->post('/register', function($request, $response, $args) use ($app) {
    $username = $request->getParsedBody()['username'];
    $password = $request->getParsedBody()['password']; 
    $email = $request->getParsedBody()['email'];
    $phone = $request->getParsedBody()['phone'];
    $uploadedFiles = $request->getUploadedFiles();
    $image = $uploadedFiles['image'];

 
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $data["res_code"] = "01";
        $data["res_text"] = "รูปแบบอีเมลไม่ถูกต้อง";
        return echoRespnse($response, 200, $data);
    }

    if ($image->getError() === UPLOAD_ERR_OK) {
        $directory = __DIR__ . '/image_profile_user';  
        $filename = moveUploadedFile($directory, $image);  
        $image_path = '/aipsibuyfood/api/v1/image_profile_user/' . $filename;  
    } else {
        return $response->withJson(['status' => 'error', 'message' => 'File upload error']);
    }

    $dsaprs = password_hash($password, PASSWORD_BCRYPT);

    $uuid = generateUUID();

    $db = new DbHandler();

  
    $result = $db->create_members($uuid, $username, $dsaprs, $email, $phone, $image_path);

    if ($result === true) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'sibuyfoodnoti@gmail.com';  
            $mail->Password = 'kxsi xipr gdkg ocrm';     
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('sibuyfoodnoti@gmail.com', 'sibuyfood');
            $mail->addAddress($email);  

            $mail->isHTML(true);
            $mail->Subject = 'ยืนยันการสมัครสมาชิก';
            $mail->Body    = 'กรุณาคลิกลิงค์นี้เพื่อยืนยันตัวตนของคุณ: <a href="http://localhost/aipsibuyfood/api/v1/verifyuser?token=' . $uuid . '">ยืนยันตัวตน</a>';
            $mail->send();

            $data["res_code"] = "00";
            $data["res_text"] = "สมัครสำเร็จและส่งอีเมลยืนยันแล้ว";
        } catch (Exception $e) {
            $data["res_code"] = "01";
            $data["res_text"] = "สมัครสำเร็จ แต่ไม่สามารถส่งอีเมลยืนยันได้: " . $mail->ErrorInfo;
        }
    } else {
      
        if ($result == 'username_exists') {
            $data["res_code"] = "01";
            $data["res_text"] = "ชื่อผู้ใช้ซ้ำ";
        } elseif ($result == 'email_exists') {
            $data["res_code"] = "01";
            $data["res_text"] = "อีเมลซ้ำ";
        } else {
            $data["res_code"] = "01";
            $data["res_text"] = "สมัครไม่สำเร็จ";
        }
    }

    return echoRespnse($response, 200, $data);
});

$app->get('/verifyuser', function($request, $response, $args) {
    $userid = $request->getQueryParams()['token']; 

    $db = new DbHandler();

  
    $status = $db->checkUserStatus($userid);

    if ($status === 1) {
        $data["res_code"] = "02";
        $data["res_text"] = "บัญชีนี้ได้รับการยืนยันตัวตนแล้ว";
    } else {
        $result = $db->verifyUser($userid);

        if ($result) {
            $data["res_code"] = "00";
            $data["res_text"] = "ยืนยันตัวตนสำเร็จ";
        } else {
            $data["res_code"] = "01";
            $data["res_text"] = "ไม่พบ ID นี้ในฐานข้อมูล หรือไม่สามารถยืนยันตัวตนได้";
        }
    }

    return echoRespnse($response, 200, $data);
});

$app->post('/editname', function($request, $response, $args) {
    $params = $request->getParsedBody(); 
    $newmname = $params['edit_name'] ?? null; 
    $userid = $params['user_id'] ?? null; 

    if (!$newmname || !$userid) {
        return echoRespnse($response, 400, [
            "res_code" => "03",
            "res_text" => "กรุณาระบุ user_id และ edit_name"
        ]);
    }

    $db = new DbHandler();
    
    $result = $db->editname($userid, $newmname);

    if ($result === "duplicate") {
        return echoRespnse($response, 409, [
            "res_code" => "02",
            "res_text" => "ชื่อผู้ใช้นี้ถูกใช้แล้ว กรุณาเลือกชื่ออื่น"
        ]);
    } elseif ($result === "success") {
        return echoRespnse($response, 200, [
            "res_code" => "00",
            "res_text" => "เปลี่ยนชื่อสำเร็จ"
        ]);
    } else {
        return echoRespnse($response, 500, [
            "res_code" => "01",
            "res_text" => "เกิดข้อผิดพลาด ไม่สามารถเปลี่ยนชื่อได้"
        ]);
    }
});


$app->post('/ediphone', function($request, $response, $args) {
    $params = $request->getParsedBody(); 
    $newPhone = $params['edit_phone'] ?? null; 
    $userid = $params['user_id'] ?? null; 

    if (!$newPhone || !$userid) {
        return echoRespnse($response, 400, [
            "res_code" => "03",
            "res_text" => "กรุณาระบุ user_id และ edit_phone"
        ]);
    }

    $db = new DbHandler();
    
    $result = $db->editPhone($userid, $newPhone);

    if ($result === "duplicate") {
        return echoRespnse($response, 409, [
            "res_code" => "02",
            "res_text" => "หมายเลขโทรศัพท์นี้ถูกใช้แล้ว กรุณาเลือกหมายเลขอื่น"
        ]);
    } elseif ($result === "success") {
        return echoRespnse($response, 200, [
            "res_code" => "00",
            "res_text" => "เปลี่ยนหมายเลขโทรศัพท์สำเร็จ"
        ]);
    } else {
        return echoRespnse($response, 500, [
            "res_code" => "01",
            "res_text" => "เกิดข้อผิดพลาด ไม่สามารถเปลี่ยนหมายเลขโทรศัพท์ได้"
        ]);
    }
});



function moveUploadedFile($directory, $uploadedFile) {
    
    $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
    $basename = bin2hex(random_bytes(8));  
    $filename = sprintf('%s.%0.8s', $basename, $extension);  

    
    $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);

    return $filename;
}


function generateUUID() {
    
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), 
        mt_rand(0, 0xffff), 
        mt_rand(0, 0x0fff) | 0x4000, 
        mt_rand(0, 0x3fff) | 0x8000, 
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}



$app->post('/login', function($request, $response, $args) {
    $username = $request->getParsedBody()['username'];
    $password = $request->getParsedBody()['password'];

    $db = new DbHandler();
    $result = $db->login($username, $password);

 
    if ($result['res_code'] == "00") {
        
        $data["res_code"] = "00";
        $data["res_text"] = $result['res_text']; 
        $data["user"] = $result["user"];
    } else {
       
        $data["res_code"] = $result['res_code'];
        $data["res_text"] = $result['res_text'];
    }

    return echoRespnse($response, 200, $data);
});



//add_address
$app->post('/add_address', function($request, $response, $args) {
    $id = $request->getParsedBody()['id'];
    $address = $request->getParsedBody()['address'];

    $db = new DbHandler();
    $result = $db->add_address($id, $address);

    if ($result) {
        $data["res_code"] = "00";
        $data["res_text"] = "เพิ่มที่อยู่สำเร็จ";
        $data["address"] = $result; 
    } else {
        $data["res_code"] = "01";
        $data["res_text"] = "เพิ่มที่อยู่ไม่สำเร็จ";
    }

    return echoRespnse($response, 200, $data);
});

$app->post('/get_datauser', function($request, $response, $args) {

    $id = $request->getParsedBody()['user_id'] ?? null;

    if (!$id) {
        return $response->withJson([
            "res_code" => "02",
            "res_text" => "ไม่พบ user_id"
        ], 400); 
    }

    $db = new DbHandler();
    $result = $db->get_datauser($id);

    if ($result) {
        $data = [
            "res_code" => "00",
            "res_text" => "ดึงข้อมูลสำเร็จ",
            "data" => [
                "profile_image" => $result['profile_image']  ,
                "username" => $result['username'],  
                "address" => $result['address'],
                "email" => $result['email'] ,
                "phone" => $result['phone'] 
            ]
        ];
    } else {
        $data = [
            "res_code" => "01",
            "res_text" => "ดึงข้อมูลไม่สำเร็จ"
        ];
    }

    return echoRespnse($response, 200, $data);
});

$app->post('/add_cart', function($request, $response, $args) {

    $body = $request->getParsedBody();

    if (!isset($body['user_id'], $body['store_id'], $body['product_id'])) {
        $data["res_code"] = "02"; 
        $data["res_text"] = "ข้อมูลไม่ครบถ้วน";
        return echoRespnse($response, 200, $data);
    }

    $user_id = $body['user_id'];
    $store_id = $body['store_id'];
    $product_id = $body['product_id'];


    $quantity = isset($body['quantity']) ? $body['quantity'] : 1;

    if (!function_exists('generateidcart') || !function_exists('generateidcart_item')) {
        $data["res_code"] = "03"; 
        $data["res_text"] = "ฟังก์ชัน UUID ไม่ถูกต้อง";
        return echoRespnse($response, 200, $data);
    }

    $cart_id = generateidcart();
    $cart_item_id = generateidcart_item();

    $db = new DbHandler();
    $result = $db->add_cart($user_id, $store_id, $product_id,  $quantity, $cart_id, $cart_item_id);

    if ($result) {
        $data["res_code"] = "00";
        $data["res_text"] = "เพิ่มสินค้าลงตะกร้าสำเร็จ";
    } else {
        $data["res_code"] = "01";
        $data["res_text"] = "เพิ่มสินค้าลงตะกร้าไม่สำเร็จ";
    }

    return echoRespnse($response, 200, $data);
});

// เส้นทาง API สำหรับลบสินค้า
$app->post('/delete_Products', function($request, $response, $args) {
    $id = $request->getParsedBody()['product_id'];

    $db = new DbHandler();
    $result = $db->delete_Products($id);

    if ($result) {
        $data["res_code"] = "00";
        $data["res_text"] = "ลบสินค้าสำเร็จ";
    } else {
        $data["res_code"] = "01";
        $data["res_text"] = "ลบสินค้าไม่สำเร็จ";
    }

    return echoRespnse($response, 200, $data);
});

// ฟังก์ชันสำหรับลบสินค้าใน DbHandler



$app->post('/add_orders', function($request, $response, $args) {

    $rawData = $request->getBody()->getContents(); // ดึงข้อมูลแบบ Raw
    $data = json_decode($rawData, true); // แปลง JSON ให้เป็น Array

    if (json_last_error() !== JSON_ERROR_NONE) {
        $data["res_code"] = "01";
        $data["res_text"] = "รูปแบบ JSON ไม่ถูกต้อง";
        return echoRespnse($response, 400, $data);
    }

    $user_id = $data['user_id'] ?? null;
    $total_price = $data['total_price'] ?? null;
    $order_items = $data['order_items'] ?? [];

    if (!$user_id || !$total_price || empty($order_items)) {
        $data["res_code"] = "01";
        $data["res_text"] = "ข้อมูลไม่ครบถ้วน กรุณาตรวจสอบการส่งข้อมูล";
        return echoRespnse($response, 400, $data);
    }

    $order_id = generateOrderId();

    $db = new DbHandler();
    $result = $db->add_order($order_id, $user_id, $total_price, $order_items);

    if ($result) {
        $data["res_code"] = "00";
        $data["res_text"] = "สั่งซื้อสินค้าสำเร็จ";
    } else {
        $data["res_code"] = "01";
        $data["res_text"] = "สั่งซื้อสินค้าไม่สำเร็จ";
    }

    return echoRespnse($response, 200, $data);
});


function generateOrderId() {
    return 'OP' . bin2hex(random_bytes(8)); 
}







$app->post('/getcartuser', function($request, $response, $args) {

    $user_id = $request->getParsedBody()['user_id'];

    $db = new DbHandler();
    $result = $db->getUserCartWithProducts($user_id);

    if ($result) {
        $data["res_code"] = "00";
        $data["res_text"] = "ดึงข้อมูลสินค้าในตะกร้าสำเร็จ";
        $data["carts"] = $result; 
    } else {
        $data["res_code"] = "01";
        $data["res_text"] = "คุณยังไม่มีสินค้าในตะกร้า";
    }

    return echoRespnse($response, 200, $data);
});


$app->post('/get_orders', function($request, $response, $args) {

    $user_id = $request->getParsedBody()['user_id'];

    $db = new DbHandler();
    $result = $db->getUserOrdersWithItems($user_id);

    if ($result) {
        $data["res_code"] = "00";
        $data["res_text"] = "ดึงข้อมูลคำสั่งซื้อสำเร็จ";
        $data["orders"] = $result; 
    } else {
        $data["res_code"] = "01";
        $data["res_text"] = "คุณยังไม่มีคำสั่งซื้อในระบบ";
    }

    return echoRespnse($response, 200, $data);
});








function generateidcart() {
    return 'C' . sprintf('%04x-%04x-%04x',
        mt_rand(0, 0xffff), 
        mt_rand(0, 0xffff), 
        mt_rand(0, 0xffff)
    );
}


function  generateidcart_item() {
    return 'CI' . sprintf('%04x-%04x-%04x',
    mt_rand(0, 0xffff), 
    mt_rand(0, 0xffff), 
    mt_rand(0, 0xffff)
);
}

$app->post('/deletecartitem', function($request, $response, $args) {

    $parsedBody = $request->getParsedBody();
    $cart_id = $parsedBody['cart_id'];
    $product_id = $parsedBody['product_id'];

    $db = new DbHandler();
    $result = $db->deleteProductFromCart($cart_id, $product_id);

    if ($result) {
        $data["res_code"] = "00";
        $data["res_text"] = "ลบสินค้าจากตะกร้าสำเร็จ";
        $data["cart_id"] = $cart_id;
        $data["product_id"] = $product_id;
    } else {
        $data["res_code"] = "01";
        $data["res_text"] = "ไม่สามารถลบสินค้าได้ หรือไม่มีสินค้านี้ในตะกร้า";
    }

    return echoRespnse($response, 200, $data);
});








// // user_customer//////////////////////

// //addimage_banber
$app->post('/addimage_banber', function($request, $response, $args) {
   
    $uploadedFiles = $request->getUploadedFiles();
    $image = $uploadedFiles['image'];

    if ($image->getError() === UPLOAD_ERR_OK) {
        $directory = __DIR__ . '/image_banners';  
        $filename = moveUploadedFile($directory, $image);  
        $image_path = '/aipsibuyfood/api/v1/image_banners/' . $filename;  
    } else {
        return $response->withJson(['status' => 'error', 'message' => 'File upload error']);
    }

    $db = new DbHandler();
    $result = $db->get_banner($image_path);

    if ($result) {
        $data["res_code"] = "00";
        $data["res_text"] = "เพิ่มรูปภาพสำเร็จ";
        $data["address"] = $result; 
    } else {
        $data["res_code"] = "01";
        $data["res_text"] = "เพิ่มรูปภาพไม่สำเร็จ";
    }

    return echoRespnse($response, 200, $data);
});





// function_store

$app->post('/register_store', function($request, $response, $args) use ($app) {

    $parsedBody = $request->getParsedBody();
    $storeName = $parsedBody['storeName'] ?? null;
    $ownerName = $parsedBody['ownername'] ?? null;
    $email = $parsedBody['email'] ?? null;
    $password = $parsedBody['password'] ?? null;
    $description = $parsedBody['description'] ?? null;
    $storePhone = $parsedBody['store_phone'] ?? null;
    $storeAddress = $parsedBody['store_address'] ?? null;
    $storeAddressLink = $parsedBody['store_address_link'] ?? null;
    $bank_name = $parsedBody['bank_name'] ?? null;
    $bank_account_number = $parsedBody['bank_account_number'] ?? null;
    $account_holder_name = $parsedBody['account_holder_name'] ?? null;
    $promptpay_number = $parsedBody['promptpay_number'] ?? null;
    $delivery_person = $parsedBody['delivery_person'] ?? null;
    $latitude = $parsedBody['latitude'] ?? null;
    $longitude = $parsedBody['longitude'] ?? null;

    // ตรวจสอบอีเมล
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return echoRespnse($response, 200, [
            "res_code" => "01",
            "res_text" => "รูปแบบอีเมลไม่ถูกต้อง"
        ]);
    }

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    $storeId = generateastore(); 

 
    $uploadedFiles = $request->getUploadedFiles();
    $image_path = null;

    if (isset($uploadedFiles['store_image']) && $uploadedFiles['store_image']->getError() === UPLOAD_ERR_OK) {
        $directory = __DIR__ . '/image_store_all';
        $filename = moveUploadedFile($directory, $uploadedFiles['store_image']);
        $image_path = '/aipsibuyfood/api/v1/image_store_all/' . $filename;
    } else {
        return echoRespnse($response, 200, [
            "res_code" => "01",
            "res_text" => "อัปโหลดรูปภาพไม่สำเร็จ"
        ]);
    }

    // เชื่อมต่อฐานข้อมูล
    $db = new DbHandler();
    $result = $db->create_store(
        $storeId, $storeName, $ownerName, $email, $hashedPassword, $description, 
        $storePhone, $storeAddress, $storeAddressLink, $image_path, 
        $bank_name, $bank_account_number, $account_holder_name, 
        $promptpay_number, $delivery_person, $latitude, $longitude
    );

    if ($result === true) {
        // ส่งอีเมลยืนยัน
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'sibuyfoodnoti@gmail.com';
            $mail->Password = 'kxsi xipr gdkg ocrm';  
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('sibuyfoodnoti@gmail.com', 'sibuyfood');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'ยืนยันการสมัครร้านค้า';
            $mail->Body = 'กรุณาคลิกลิงค์นี้เพื่อยืนยันตัวตนของร้านค้าของคุณ: <a href="http://localhost/aipsibuyfood/api/v1/verifystore?token=' . $storeId . '">ยืนยันตัวตน</a>';
            $mail->send();

            return echoRespnse($response, 200, [
                "res_code" => "00",
                "res_text" => "ร้านค้าถูกสมัครสำเร็จและส่งอีเมลยืนยันแล้ว"
            ]);
        } catch (Exception $e) {
            return echoRespnse($response, 200, [
                "res_code" => "01",
                "res_text" => "สมัครสำเร็จ แต่ไม่สามารถส่งอีเมลยืนยันได้: " . $mail->ErrorInfo
            ]);
        }
    } else {
        $errorMsg = "สมัครร้านค้าไม่สำเร็จ";
        if ($result == 'store_name_exists') $errorMsg = "ชื่อร้านค้าซ้ำ";
        if ($result == 'email_exists') $errorMsg = "อีเมลซ้ำ";

        return echoRespnse($response, 200, [
            "res_code" => "01",
            "res_text" => $errorMsg
        ]);
    }
});



function generateastore() {
    return 'S' . sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), 
        mt_rand(0, 0xffff), 
        mt_rand(0, 0x0fff) | 0x4000, 
        mt_rand(0, 0x3fff) | 0x8000, 
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}




$app->get('/verifystore', function($request, $response, $args) {
    $userstoreid = $request->getQueryParams()['token']; 

    $db = new DbHandler();

    // ตรวจสอบสถานะก่อน
    $status = $db->checkStoreStatus($userstoreid);

    if ($status === 1) {
        $data["res_code"] = "02";
        $data["res_text"] = "ร้านค้านี้ได้รับการยืนยันตัวตนแล้ว";
    } else {
        $result = $db->verifyStore($userstoreid);

        if ($result) {
            $data["res_code"] = "00";
            $data["res_text"] = "ยืนยันการลงทะเบียนร้านค้าสำเร็จ";
        } else {
            $data["res_code"] = "01";
            $data["res_text"] = "ไม่พบ ID นี้ในฐานข้อมูล หรือไม่สามารถยืนยันตัวตนได้";
        }
    }

    return echoRespnse($response, 200, $data);
});

$app->post('/login_strore', function($request, $response, $args) {
    $email_store = $request->getParsedBody()['email'];
    $password_store = $request->getParsedBody()['password'];

    $db = new DbHandler();
    $result = $db->login_store( $email_store,  $password_store);

 
    if ($result['res_code'] == "00") {
        
        $data["res_code"] = "00";
        $data["res_text"] = $result['res_text']; 
        $data["user"] = $result["user"];
    } else {
       
        $data["res_code"] = $result['res_code'];
        $data["res_text"] = $result['res_text'];
    }

    return echoRespnse($response, 200, $data);
});

$app->post('/get_datastore', function ($request, $response, $args) {
    $id = $request->getParsedBody()['store_id'] ?? null;

   
    
    if (!$id) {
        $data = [
            "res_code" => "02",
            "res_text" => "ไม่พบ store_id"
        ];
        return echoRespnse($response, 200, $data);
    }
    
    

    $db = new DbHandler();
    $result = $db->get_datauseassar($id);

    if ($result) {
        $data = [
            "res_code" => "00",
            "res_text" => "ดึงข้อมูลสำเร็จ",
            "data" => $result  
        ];
    } else {
        $data = [
            "res_code" => "01",
            "res_text" => "ดึงข้อมูลไม่สำเร็จ"
        ];
    }

    return echoRespnse($response, 200, $data);
});





// add_product

$app->post('/add_product', function($request, $response, $args) use ($app) {

    $storeId = $request->getParsedBody()['storeId'];
    $productName = $request->getParsedBody()['productName'];
    $price = $request->getParsedBody()['price'];
    $expirationDays = $request->getParsedBody()['expirationDays'];
    $productDescription = $request->getParsedBody()['productDescription'];
    $uploadedFiles = $request->getUploadedFiles();
    $image = $uploadedFiles['image'];
    $categoryId = $request->getParsedBody()['categoryId'];
    $quantity = $request->getParsedBody()['quantity'];
    $isyollow = $request->getParsedBody()['isyellow'];
   
    

    


    if ($image->getError() === UPLOAD_ERR_OK) {
        $directory = __DIR__ . '/Image_products';  
        $filename = moveUploadedFile($directory, $image);  
        $image_path = '/aipsibuyfood/api/v1/Image_products/' . $filename;  
    } else {
        return $response->withJson(['status' => 'error', 'message' => 'File upload error']);
    }

   

   
    $pbid = generatestoreId();

    $db = new DbHandler();

 
    $result = $db->create_product( $pbid, $storeId, $productName, $price,$expirationDays, $productDescription, 
        $image_path, $categoryId ,$quantity ,$isyollow
    );
   
    if ($result != NULL && $result == true) {
        $data["res_code"] = "00";
        $data["res_text"] = "เพิ่มสินค้าสำเร็จ";
    } else {
        $data["res_code"] = "01";
        $data["res_text"] = "เพิ่มสินค้าไม่สำเร็จ";
    }
    return echoRespnse($response, 200, $data);
});



function generatestoreId($prefix = 'p', $length = 12) {
   
    if ($length % 2 !== 0) {
        $length++; 
    }

    
    $randomString = strtoupper(bin2hex(random_bytes($length / 2)));
    return $prefix . $randomString;
}

$app->post('/update_product', function($request, $response, $args) use ($app) {

    $productId = $request->getParsedBody()['product_id'];
    $productName = $request->getParsedBody()['productName'];
    $price = $request->getParsedBody()['price'];
    $expirationDays = $request->getParsedBody()['expirationDays'];
    $productDescription = $request->getParsedBody()['productDescription'];
    $uploadedFiles = $request->getUploadedFiles();
    $image = $uploadedFiles['image'] ?? null;
    $categoryId = $request->getParsedBody()['categoryId'];
    $quantity = $request->getParsedBody()['quantity'];
    $isyellow = $request->getParsedBody()['isyellow'];

    $image_path = null;

    if ($image && $image->getError() === UPLOAD_ERR_OK) {
        $directory = __DIR__ . '/Image_products';  
        $filename = moveUploadedFile($directory, $image);  
        $image_path = '/aipsibuyfood/api/v1/Image_products/' . $filename;  
    }

    $db = new DbHandler();

    $result = $db->update_product($productId, $productName, $price, $expirationDays, $productDescription, 
        $image_path, $categoryId, $quantity, $isyellow
    );

    if ($result != NULL && $result == true) {
        $data["res_code"] = "00";
        $data["res_text"] = "แก้ไขสินค้าสำเร็จ";
    } else {
        $data["res_code"] = "01";
        $data["res_text"] = "แก้ไขสินค้าไม่สำเร็จ";
    }
    return echoRespnse($response, 200, $data);
});

// function_store /////




// function_app

$app->post('/popular_stores', function($request, $response, $args) use ($app) {

    $db = new DbHandler(); 
    
    $result = $db->getpularstore(); 

    $data =array();

    if ($result != NULL) {
        $data["res_code"] = "00";
        $data["res_text"] = "แสดงข้อมูลสำเร็จ";
        $data["storespopular"] = $result; 
    } else {
        $data["res_code"] = "01";
        $data["res_text"] = "ไม่มีข้อมูลสินค้า";
    }

    return echoRespnse($response, 200, $data);
});

$app->post('/getstoreall', function($request, $response, $args) use ($app) {

    $db = new DbHandler(); 
    
    $result = $db->getstoreall(); 

    $data =array();

    if ($result != NULL) {
        $data["res_code"] = "00";
        $data["res_text"] = "แสดงข้อมูลสำเร็จ";
        $data["storespopular"] = $result; 
    } else {
        $data["res_code"] = "01";
        $data["res_text"] = "ไม่มีข้อมูลสินค้า";
    }

    return echoRespnse($response, 200, $data);
});



$app->post('/viewProductsall', function($request, $response, $args) use ($app) {

    $db = new DbHandler(); 
    
    $result = $db->viewProducts(); 

    $data = array();

    if ($result != NULL) {
        $data["res_code"] = "00";
        $data["res_text"] = "แสดงข้อมูลสำเร็จ";
        $data["products"] = $result; 
    } else {
        $data["res_code"] = "01";
        $data["res_text"] = "ไม่มีข้อมูลสินค้า";
    }

    return echoRespnse($response, 200, $data);
});

$app->post('/viewProductsToday', function($request, $response, $args) use ($app) {

    $db = new DbHandler(); 
    
    $result = $db->viewProductsToday(); 
    $data = array();

    if ($result != NULL) {
        $data["res_code"] = "00";
        $data["res_text"] = "แสดงข้อมูลสินค้าวันนี้สำเร็จ";
        $data["products"] = $result; 
    } else {
        $data["res_code"] = "01";
        $data["res_text"] = "ไม่มีข้อมูลสินค้าวันนี้";
    }

    return echoRespnse($response, 200, $data);
});


$app->post('/viewProductsdrinks', function($request, $response, $args) use ($app) {

    $db = new DbHandler(); 
    
    $result = $db->viewProducts_drinks(); 

    $data = array();

    if ($result != NULL) {
        $data["res_code"] = "00";
        $data["res_text"] = "แสดงข้อมูลสำเร็จ";
        $data["products"] = $result; 
    } else {
        $data["res_code"] = "01";
        $data["res_text"] = "ไม่มีข้อมูลสินค้า";
    }

    return echoRespnse($response, 200, $data);
});

$app->post('/viewProducts_dessert', function($request, $response, $args) use ($app) {

    $db = new DbHandler(); 
    
    $result = $db->viewProducts_dessert(); 

    $data = array();

    if ($result != NULL) {
        $data["res_code"] = "00";
        $data["res_text"] = "แสดงข้อมูลสำเร็จ";
        $data["products"] = $result; 
    } else {
        $data["res_code"] = "01";
        $data["res_text"] = "ไม่มีข้อมูลสินค้า";
    }

    return echoRespnse($response, 200, $data);
});

$app->post('/viewProducts_fresh_food', function($request, $response, $args) use ($app) {

    $db = new DbHandler(); 
    
    $result = $db->viewProducts_fresh_food(); 

    $data = array();

    if ($result != NULL) {
        $data["res_code"] = "00";
        $data["res_text"] = "แสดงข้อมูลสำเร็จ";
        $data["products"] = $result; 
    } else {
        $data["res_code"] = "01";
        $data["res_text"] = "ไม่มีข้อมูลสินค้า";
    }

    return echoRespnse($response, 200, $data);
});

$app->post('/viewProducts_other', function($request, $response, $args) use ($app) {

    $db = new DbHandler(); 
    
    $result = $db->viewProducts_other(); 

    $data = array();

    if ($result != NULL) {
        $data["res_code"] = "00";
        $data["res_text"] = "แสดงข้อมูลสำเร็จ";
        $data["products"] = $result; 
    } else {
        $data["res_code"] = "01";
        $data["res_text"] = "ไม่มีข้อมูลสินค้า";
    }

    return echoRespnse($response, 200, $data);
});

  

$app->post('/searchProducts', function($request, $response, $args) use ($app) {
   
    $data = $request->getParsedBody();

    
    $searchTerm = $data['searchTerm'] ?? '';

    
    $db = new DbHandler();
    $result = $db->searchProducts($searchTerm);

    $data = array();
    if ($result != NULL) {
        $data["res_code"] = "00";
        $data["res_text"] = "แสดงข้อมูลสำเร็จ";
        $data["searchproducts"] = $result;
    } else {
        $data["res_code"] = "01";
        $data["res_text"] = "ไม่มีข้อมูลสินค้า";
    }

    return echoRespnse($response, 200, $data);
});

$app->post('/suggestProducts', function($request, $response, $args) use ($app) {
    $queryParams = $request->getParsedBody();
    $searchTerm = $queryParams['searchTerm'] ?? '';

    if (!$searchTerm) {
        return echoRespnse($response, 200, []);
    }

    $db = new DbHandler();
    $result = $db->suggestProducts($searchTerm);

    return echoRespnse($response, 200, $result);
});


$app->post('/get_data_products_isstore', function($request, $response, $args) use ($app) {
   
    $data = $request->getParsedBody();

    
    $store_id = $data['store_id'] ?? '';

    
    $db = new DbHandler();
    $result = $db->get_data_products_isstore($store_id);

    $data = array();
    if ($result != NULL) {
        $data["res_code"] = "00";
        $data["res_text"] = "แสดงข้อมูลสำเร็จ";
        $data["isstoreproducts"] = $result;
    } else {
        $data["res_code"] = "01";
        $data["res_text"] = "ไม่มีข้อมูลสินค้า";
    }

    return echoRespnse($response, 200, $data);
});

// function_app


// ***************************************************************************************************
// ***************************************************************************************************
// ***************************************************************************************************

        /*** แสดงผล json ***/
        function echoRespnse($response, $status_code, $data) {
            $response = $response->withStatus($status_code)
                                ->withHeader('Content-Type', 'application/json');
            $response->getBody()->write(json_encode($data));
            return $response;
        }

// ***************************************************************************************************
// ***************************************************************************************************
// ***************************************************************************************************




$app->run();



?>



