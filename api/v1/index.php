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







$app->get('/checkapi', function($request, $response, $args) use ($app) {
    $data = array();
    $data["res_code"] = "00";
    $data["res_text"] = "แสดงข้อมูลสำเร็จ";
 

    return echoRespnse($response, 200, $data);
});

$app->post('/register', function($request, $response, $args) use ($app) {
    $data = array();
    $data["res_code"] = "00";
    $data["res_text"] = "บันทึกข้อมูลสําเร็จ";
    return echoRespnse($response, 200, $data);
    
})


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
