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



$user_id = NULL;






$app = AppFactory::create();
$app->setBasePath('/testapi/api/v1');


$authMiddleware = function (Request $request, RequestHandler $handler) {
    $headers = $request->getHeaders();
    $response = new \Slim\Psr7\Response();
    // echo $headers['Authorization'][0];
    // exit;
    if (isset($headers['Authorization'][0])) {
        $db = new DbHandler();
        $api_key = $headers['Authorization'][0];

        if (!$db->isValidApiKey($api_key)) {
            $response = $response->withStatus(401);
            $response->getBody()->write(json_encode([
                "res_code" => "09",
                "res_text" => "Api key ไม่ถูกต้อง ไม่มีสิทธิ์การเข้าถึงข้อมูล"
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            // You can set user ID in the request attributes if needed
            $user_id = $db->getUserId($api_key);
            $request = $request->withAttribute('user_id', $user_id);
        }
    } else {
        $response = $response->withStatus(401);
        $response->getBody()->write(json_encode([
            "res_code" => "09",
            "res_text" => "ไม่พบ Api key"
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    return $handler->handle($request);
};




$app->get('/checkapi', function($request, $response, $args) use ($app) {
    $data = array();
    $data["res_code"] = "00";
    $data["res_text"] = "แสดงข้อมูลสำเร็จ";
    return echoRespnse($response, 200, $data);
});

// $app->post('/login', function($request, $response, $args) use ($app) {

//     // ตัวกำหมด
//     +
//    $email = $request->getParsedBody()['email'];
//    $password = $request->getParsedBody()['password'];

//    $db = new DbHandler();
//    $result = $db->login($email,$password);

//    $data = array();
//    if ($result != NULL) {
//        $data["res_code"] = "00";
//        $data["res_text"] = "ล็อกอินสำเร็จ";
//        $data["res_result"] = $result;
//    } else {
//        $data["res_code"] = "01";
//        $data["res_text"] = "ล็อกอินไม่สำเร็จ";
//    }
//    return echoRespnse($response, 200, $data);
// });

$app->get('/banner', function($request, $response, $args) use ($app) {

   $db = new DbHandler();
   $result = $db->banner();
   $data = array();
   if ($result != NULL) {
       $data["res_code"] = "00";
       $data["res_text"] = "ดึงข้อมูลสำเร็จ";
       $data["res_result"] = $result;
   } else {
       $data["res_code"] = "01";
       $data["res_text"] = "ดึงข้อมูลไม่สำเร็จ";
   }
   return echoRespnse($response, 200, $data);
});




// ทำเอง

$app->post('/creatmembers', function($request, $response, $args) use ($app) {



    $username = $request->getParsedBody()['username'];
    $password = $request->getParsedBody()['password'];
    $email = $request->getParsedBody()['email'];
    $phone =$request->getParsedBody()['phone'];

    $dsaprs = password_hash($password, PASSWORD_BCRYPT);




    $db = new DbHandler(); 
    $result = $db->create_members( $username,$dsaprs,$email, $phone); 
 
    if ($result != NULL || $result == true) {
        $data["res_code"] = "00";
        $data["res_text"] = "สมัครสำเร็จ";
    } else {
        $data["res_code"] = "01"; 
        $data["res_text"] = "สมัครไม่สำเร็จ"; 
    }

    return echoRespnse($response, 200, $data); 
});



$app->post('/updatemembers', function($request, $response, $args) use ($app) {
    $user_id = $request->getAttribute('user_id');
    $username = $request->getParsedBody()['username'];
    $password = $request->getParsedBody()['password'];
    $email = $request->getParsedBody()['email'];
    $phone = $request->getParsedBody()['phone'];
    
 
    $dsaprs = password_hash($password, PASSWORD_BCRYPT);
   
    
  
    $db = new DbHandler(); 
    $result = $db->update_members($user_id, $username, $dsaprs, $email, $phone); 

   
    if ($result != NULL || $result == true) {
        $data["res_code"] = "00";
        $data["res_text"] = "อัปเดตสำเร็จ";
    } else {
        $data["res_code"] = "01"; 
        $data["res_text"] = "อัปเดตไม่สำเร็จ"; 
    }

   
    return echoRespnse($response, 200, $data); 

})->add($authMiddleware);


$app->post('/deletemember', function($request, $response, $args) use ($app) {
    $user_id = $request->getAttribute('user_id');
    $db = new DbHandler(); 
    $result = $db->delete_member($user_id, ); 

    if ($result != NULL || $result == true) {
        $data["res_code"] = "00";
        $data["res_text"] = "ลบสำเร็จ";
    } else {
        $data["res_code"] = "01"; 
        $data["res_text"] = "ลบไม่สำเร็จ"; 
    }

   
    return echoRespnse($response, 200, $data); 
})->add($authMiddleware);


$app->post('/addprpducts', function($request, $response,$args) use ($app){

  
    $produstname = $request->getParsedBody()['produstname'];
    $produsdes = $request->getParsedBody()['produsdes'];
    $produspice = $request->getParsedBody()['produspice'];
    $memberid = $request->getParsedBody()['memberid'];

    $db = new DbHandler(); 
    $result = $db->addproduts(   $produstname ,  $produsdes , $produspice , $memberid); 


    if ($result != NULL || $result == true) {
        $data["res_code"] = "00";
        $data["res_text"] = "เพิ่มข้อมูลสำเร็จ";
    } else {
        $data["res_code"] = "01"; 
        $data["res_text"] = "เพิ่มไม่สำเร็จ "; 
    }

   
    return echoRespnse($response, 200, $data); 




});


$app->get('/viewproduts', function($request, $response, $args) use ($app) {

    $db = new DbHandler(); 
    
   
    $result = $db->viewproduts(); 

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


$app->post('/logintoken', function($request, $response, $args) use ($app) {
    
    $username = $request->getParsedBody()['username'];
    $password = $request->getParsedBody()['password'];

   
    $db = new DbHandler(); 
    $result = $db->logintoken($username, $password); 

    if (isset($result['token'])) {
        
        $data["res_code"] = "00";
        $data["res_text"] = "ล็อกอินด้วย Token สำเร็จ";
        $data["token"] = $result['token'];
        $data["member_id"] = $result['member_id']; 
    } else {
        
        $data["res_code"] = "01"; 
        $data["res_text"] = "ล็อกอินไม่สำเร็จ: " . $result['error']; 
    }

    
    return echoRespnse($response, 200, $data); 
});


$app->get( '/gettoken',function($request,$response,$args) use ($app) {

    $db = new DbHandler(); 
    
   
    $result = $db->gerttoken(); 

 

    if ($result != NULL) {
        
        $data["res_code"] = "00";
        $data["res_text"] = "แสดงข้อมูลสำเร็จ";
        $data["products"] = $result; 
    } else {
        
        $data["res_code"] = "01";
        $data["res_text"] = "ไม่มีข้อมูลสินค้า";
    }

  
    return echoRespnse($response, 200, $data);

    

} );
 
$app->post('/addlocation', function($request, $response, $args) use ($app) {

     $location_name = $request->getParsedBody()['locationname'];
     $location_des = $request->getParsedBody()['locationdes'];

   

    $uploadedFiles = $request->getUploadedFiles();
    $image = $uploadedFiles['image']; 

    if ($image->getError() === UPLOAD_ERR_OK) {
        $directory = __DIR__ . '/image';  
        $filename = moveUploadedFile($directory, $image); 
        $image_path = '/Testapi/api/v1/image/' . $filename; 
    } else {
        return $response->withJson(['status' => 'error', 'message' => 'File upload error']);
    }
    $data["product1s"] = $location_des; 
    $data["product2s"] = $location_name; 
    $data["3"] = $image_path; 


    $db = new DbHandler();
     $result = $db->Testin($location_name, $location_des, $image_path);

    return echoRespnse($response, 200, $result );
});


function moveUploadedFile($directory, $uploadedFile) {
    $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
    $basename = bin2hex(random_bytes(8)); 
    $filename = sprintf('%s.%0.8s', $basename, $extension);

    $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);

    return $filename;
}



$app->get('/getlocation', function($request, $response, $args) {
    
    $id = $request->getQueryParams()['id'];
    $db = new DbHandler();
    $result = $db->getLocationById($id) ;

    if ($result) {
        
        return echoRespnse($response, 200, $result );
    } else {
       
        return $response->withJson(['status' => 'error', 'message' => 'Location not found'], 404);
    }
});



$app->get('/join', function ($request, $response, $args) {
    $db = new DbHandler();
    $result = $db->jooin();

    if ($result !== false) {
        // กำหนด header 'Content-Type' เป็น 'application/json'
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    } else {
        // คืนค่า JSON สำหรับข้อผิดพลาด
        $response->getBody()->write(json_encode(['status' => 'error', 'message' => 'No products found']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }
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
