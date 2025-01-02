<?php

class DbHandler { 
 
    private $conn,$func;
    function __construct() {
        require_once '../include/DbConnect.php';
        $db = new DbConnect();
        $this->conn = $db->connect();
    } 

      // public function login($email,$password) {
      //     $stmt = $this->conn->prepare("SELECT * FROM `member` where member_email = '$email' and member_password = '$password' and member_status = 1 ");
      //     $stmt->execute();
      //     $result = $stmt->get_result();
      //     $output = array();
      //     $member_id = 0;
      //     if($result->num_rows > 0){
      //       while($res = $result->fetch_assoc())
      //       {
      //         $member_id = $res['member_id'];
      //         $response = array(
      //           "member_fname" => $res['member_fname'],
      //           "member_lname" => $res['member_lname'],
      //           "member_email" => $res['member_email'],
      //         );
      //         $output[]=$response;
      //       }
      //       $stmt->close();
      //       $this->log($member_id,'%@#%#@WEFEWF');
      //       return $output;
      //     }else{
      //       $stmt->close();
      //       return NULL;
      //     }
      // }

      // public function log($member_id,$log_token) {
      //     $stmt = $this->conn->prepare("INSERT INTO `log`(`member_id`, `log_token`, `create_date`) VALUES ('$member_id','$log_token',NOW())");
      //     if($stmt->execute()){
      //       return true;
      //     }else{
      //       return false;
      //     }
      // }


      public function banner() {
          $stmt = $this->conn->prepare("SELECT * FROM `banner`");
          $stmt->execute();
          $result = $stmt->get_result();
          $output = array();
          if($result->num_rows > 0){
            while($res = $result->fetch_assoc())
            {
              $response = array(
                "banner_title" => $res['banner_title']
              );
              $output[]=$response;
            }
            $stmt->close();
            return $output;
          }else{
            $stmt->close();
            return NULL;
          }
      }

  
      public function member() {
        $stmt = $this->conn->prepare("SELECT * FROM `member`");
        $stmt->execute();
        $result = $stmt->get_result();
        $output = array();
        if($result->num_rows > 0){
          while($res = $result->fetch_assoc())
          {
            $response = array(
              "member_fname" => $res['member_fname'],
              "member_lname" => $res['member_lname'],
              "member_email" => $res['member_email'],
              "member_password" => $res['member_password'],
              "create_date" => $res['create_date'],

            );
            $output[]=$response;
          }
          $stmt->close();
          return $output;
        }else{
          $stmt->close();
          return NULL;
        }
    }








// ทำเอง 


public function create_members($fname, $lname, $email, $password) { 

  $stmt = $this->conn->prepare("INSERT INTO `members`(`username`, `password`, `email`, `phone`) VALUES (?, ?, ?, ?)");


  $stmt->bind_param("ssss", $fname, $lname, $email, $password);
  
  
  if ($stmt->execute()) {
      return true;
  } else {
      return false;
  }
}


  public function update_members($id, $username, $dsaprs, $email, $phone){
  
    $stmt = $this->conn->prepare("UPDATE `members` SET `username` = ?, `password` = ?, `email` = ?, `phone` = ? WHERE `id` = ?");

    
    $stmt->bind_param("ssssi", $username, $dsaprs, $email, $phone, $id);

    if($stmt->execute()){
        return true;
    } else {
        return false;
    }
}

public function delete_member($id){
 
  $stmt = $this->conn->prepare("DELETE FROM `members` WHERE `id` = ?");
  

  $stmt->bind_param("i", $id);
  
  
  if($stmt->execute()){
      return true;
  } else {
      return false;
  }
}






public function addproduts($productname, $productdesc, $productprice, $memberid) {
  
  if (empty($productname) || empty($productdesc) || !is_numeric($productprice) || empty($memberid)) {
      return false; 
  }

  $stmt = $this->conn->prepare("INSERT INTO `products` (`product_name`, `product_description`, `price`, `member_id`) VALUES (?, ?, ?, ?)");
  

  $stmt->bind_param('ssdi', $productname, $productdesc, $productprice, $memberid);

  
  if ($stmt->execute()) {
      return true;
  } else {
      return false;
  }
}



public function viewproduts(){
 
  $stmt = $this->conn->prepare("SELECT * FROM `products`");
  $stmt->execute(); 
  $result = $stmt->get_result(); 
  $output = array(); 

  if($result->num_rows > 0){
    while($res = $result->fetch_assoc()) {
    
      $response = array(
        "product_id" => $res['product_id'],
        "product_name" => $res['product_name'],
        "product_description" => $res['product_description'], 
        "price" => $res['price'],
        "member_id" => $res ['member_id']
      );
      $output[] = $response; 
    }
    $stmt->close(); 
    return $output; 
  } else {
    $stmt->close(); 
    return NULL; 
  }
}

public function logintoken($username, $password) {
 
  $stmt = $this->conn->prepare("SELECT id, password FROM members WHERE username = ?");
  $stmt->bind_param("s", $username);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
      $user = $result->fetch_assoc();
      $stored_password = $user['password'];
      
     
      if (password_verify($password, $stored_password)) {
          $member_id = $user['id']; 
          $token = $this->generateToken($member_id); 
          return [
              'token' => $token,
              'member_id' => $member_id
          ]; 
      } else {
          return [
              'error' => 'Invalid password'
          ]; 
      }
  } else {
      return [
          'error' => 'User not found'
      ]; 
  }
}

public function generateToken($member_id) {
  $token = bin2hex(random_bytes(16)); 
  $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour')); 

  $stmt = $this->conn->prepare("INSERT INTO tokens (member_id, token, expires_at) VALUES (?, ?, ?)");
  $stmt->bind_param("iss", $member_id, $token, $expires_at);

  if ($stmt->execute()) {
      return $token; 
  } else {
      return null; 
  }
}



public function gerttoken() {

  $stmt = $this->conn->prepare("SELECT * FROM `tokens`");
  $stmt->execute(); 
  $result = $stmt->get_result(); 
  $output = array(); 

  if($result->num_rows > 0){
    while($res = $result->fetch_assoc()) {
    
      $response = array(
        "token_id" => $res['token_id'],
        "member_id" => $res ['member_id'],
        "token" => $res ['token'],
        "expires_at" => $res ['expires_at'],
        "created_at" => $res ['created_at'],


      
      );
      $output[] = $response; 
    }
    $stmt->close(); 
    return $output; 
  } else {
    $stmt->close(); 
    return NULL; 
  }
}





public function Testin($location_name, $location_des, $image_path) {
  
  $stmt = $this->conn->prepare("INSERT INTO `locations`( `location_name`, `location_description`, `image_path`) VALUES (?,?,?)");
  $stmt->bind_param( 'sss' ,$location_name, $location_des, $image_path);



  // $stmt = $this->conn->prepare("INSERT INTO `locations`( `location_name`, `location_description`, `image_path`) VALUES ('$location_name',' $location_des','$image_path')");

  if ($stmt->execute()) {
      return 'เพิ่มสำเร็จ'; 
  } else {
      return 'เพิ่มไม่สำเร็จ'; 
  }






}



public function getLocationById($id) {
  // เชื่อมต่อกับฐานข้อมูล
  $stmt = $this->conn->prepare("SELECT `id`, `location_name`, `location_description`, `image_path` FROM `locations` WHERE `id` = ?");

  if ($stmt) {
      // ผูกตัวแปร $id เข้ากับ query
      $stmt->bind_param("i", $id);
      $stmt->execute();
      
      // ดึงข้อมูลออกมา
      $result = $stmt->get_result()->fetch_assoc();
      
      // ถ้าพบข้อมูล ให้ return ข้อมูล
      if ($result) {
          return $result;
      } else {
          return false; // ถ้าไม่พบข้อมูล
      }
  } else {
      return false; // ถ้าเกิดข้อผิดพลาดใน query
  }
}

public function jooin() {
  // เตรียมคำสั่ง SQL
  $stmt = $this->conn->prepare("SELECT 
      p.product_id, 
      p.product_name, 
      p.product_description, 
      p.price, 
      m.username, 
      m.email
  FROM 
      products p
  INNER JOIN 
      members m ON p.member_id = m.id");

  
  if ($stmt) {
      $stmt->execute();
      
      
      $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); 
      
      
      if ($result) {
          return $result; 
      } else {
          return []; 
      }
  } else {
      return false; 
  }
}




public function isValidApiKey($api_key) {
  $stmt = $this->conn->prepare("SELECT member_id from tokens WHERE token = '$api_key' ");
  $stmt->execute();
  $result = $stmt->get_result();
  $num_rows = $result->num_rows;
  $stmt->close();
  return $num_rows > 0;

}


public function getUserId(  $api_key) {
  $stmt = $this->conn->prepare("SELECT member_id from tokens WHERE token = '$api_key' ");
  if ($stmt->execute()) {
      $stmt->bind_result( $api_key);
      $stmt->fetch();
      $stmt->close();
      return  $api_key;
  } else {
      return NULL;
  }
}
}















?>

