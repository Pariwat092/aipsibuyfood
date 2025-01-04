<?php

class DbHandler { 
 
    private $conn,$func;
    function __construct() {
        require_once '../include/DbConnect.php';
        $db = new DbConnect();
        $this->conn = $db->connect();
    } 

    

   


    public function create_members($uuid, $username, $dsaprs, $email, $phone, $image_path) {
      
      $stmt = $this->conn->prepare("INSERT INTO `users`(`id`, `username`, `password`, `email`, `phone`, `profile_image`) VALUES (?, ?, ?, ?, ?, ?)");
  
      
      $stmt->bind_param("ssssss", $uuid, $username, $dsaprs, $email, $phone, $image_path);
  
      
      if ($stmt->execute()) {
          return true;
      } else {
          return false;
      }
  }
  
  
  

}





























































?>