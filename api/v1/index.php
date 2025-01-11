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
require '../../vendor/autoload.php';


use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Factory\AppFactory;









$app = AppFactory::create();
$app->setBasePath('/aipsibuyfood/api/v1');






// ตรวจสอบ api
$app->get('/checkapi', function($request, $response, $args) use ($app) {
    $data = array();
    $data["res_code"] = "00";
    $data["res_text"] = "แสดงข้อมูลสำเร็จ";
    return echoRespnse($response, 200, $data);
});



// สมัครสมาชิก
$app->post('/register', function($request, $response, $args) use ($app) {
   
    
    $username = $request->getParsedBody()['username'];
    $password = $request->getParsedBody()['password']; 
    $email = $request->getParsedBody()['email'];
    $phone = $request->getParsedBody()['phone'];
    $uploadedFiles = $request->getUploadedFiles();
    $image = $uploadedFiles['image'];

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

   
    if ($result != NULL && $result == true) {
        $data["res_code"] = "00";
        $data["res_text"] = "สมัครสำเร็จ";
    } else {
        $data["res_code"] = "01";
        $data["res_text"] = "สมัครไม่สำเร็จ";
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


// login 


$app->post('/login', function($request, $response, $args) {
    $username = $request->getParsedBody()['username'];
    $password = $request->getParsedBody()['password'];

    $db = new DbHandler();
    $result = $db->login($username, $password);

    if ($result) {
        $data["res_code"] = "00";
        $data["res_text"] = "เข้าสู่ระบบสำเร็จ";
        $data["user"] = $result; 
    } else {
        $data["res_code"] = "01";
        $data["res_text"] = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
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