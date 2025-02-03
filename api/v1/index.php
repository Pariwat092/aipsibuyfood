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

   
    $result = $db->verifyUser($userid);

    if ($result) {
        $data["res_code"] = "00";
        $data["res_text"] = "ยืนยันตัวตนสำเร็จ";
    } else {
        $data["res_code"] = "01";
        $data["res_text"] = "ไม่พบ IDนี้ในฐานข้อมูล หรือไม่สามารถยืนยันตัวตนได้";
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


//addimage_banber
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


// add_store


$app->post('/register_store', function($request, $response, $args) use ($app) {
   
    $storeName = $request->getParsedBody()['store_name']; 
    $ownerName = $request->getParsedBody()['owner_name'];  
    $email = $request->getParsedBody()['email'];  
    $password = $request->getParsedBody()['password'];  
    $description = $request->getParsedBody()['description'];
    $storePhone = $request->getParsedBody()['store_phone'];
    $storeAddress = $request->getParsedBody()['store_address'];
    $storeAddressLink = $request->getParsedBody()['store_address_link'];
    $uploadedFiles = $request->getUploadedFiles();
    $image = $uploadedFiles['image'];
    $bankAccountNumber = $request->getParsedBody()['bank_account_number'];
    $accountHolderName = $request->getParsedBody()['account_holder_name'];
    $deliveryPerson = $request->getParsedBody()['delivery_person'];
    $promptpayNumber = $request->getParsedBody()['promptpay_number'];
    $bankName = $request->getParsedBody()['bank_name'];  
    $latitude = $request->getParsedBody()['latitude'];
    $longitude = $request->getParsedBody()['longitude'];

    if ($image->getError() === UPLOAD_ERR_OK) {
        $directory = __DIR__ . '/image_store_all';  
        $filename = moveUploadedFile($directory, $image);  
        $image_path = '/aipsibuyfood/api/v1/image_store_all/' . $filename;  
    } else {
        return $response->withJson(['status' => 'error', 'message' => 'File upload error']);
    }

    $uuid = ggenerateastore();
    $dsaprs = password_hash($password, PASSWORD_BCRYPT);
    $db = new DbHandler();

    $result = $db->create_store(
      
    );

    if ($result != NULL && $result == true) {
        $data["res_code"] = "00";
        $data["res_text"] = "สมัครสำเร็จ";
    } else {
        $data["res_code"] = "01";
        $data["res_text"] = "สมัครไม่สำเร็จ";
    }
    return echoRespnse($response, 200, $data);
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







// add_product

$app->post('/add_product', function($request, $response, $args) use ($app) {
   
    
    $storeId = $request->getParsedBody()['store_id'];  
    $productName = $request->getParsedBody()['product_name'];  
    $price = $request->getParsedBody()['price'];  
    $expirationDays = $request->getParsedBody()['expiration_days'];  
    $productDescription = $request->getParsedBody()['description']; 
    $uploadedFiles = $request->getUploadedFiles();  
    $image = $uploadedFiles['image'];  
    $categoryId = $request->getParsedBody()['category_id'];  
    


    if ($image->getError() === UPLOAD_ERR_OK) {
        $directory = __DIR__ . '/Image_products';  
        $filename = moveUploadedFile($directory, $image);  
        $image_path = '/aipsibuyfood/api/v1/Image_products/' . $filename;  
    } else {
        return $response->withJson(['status' => 'error', 'message' => 'File upload error']);
    }

   

   
    $uuid = generatestoreId();

    $db = new DbHandler();

 
    $result = $db->create_product( $uuid, $storeId, $productName, $price,$expirationDays, $productDescription, 
        $image_path, $categoryId
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