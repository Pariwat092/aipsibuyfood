<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Credentials: true");
header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
header('Access-Control-Max-Age: 1000');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token , Authorization');
require_once './DbHandler.php';
require_once '../include/Config.php';
// require '../vendor/autoload.php';
require_once __DIR__ . '/../../vendor/autoload.php';




use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Factory\AppFactory;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$app = AppFactory::create();
$app->setBasePath('/aipsibuyfood/api/v1');






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
                "address" => $result['address']  
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
    $isyollow = $request->getParsedBody()['isyollow'];
   
    

    


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