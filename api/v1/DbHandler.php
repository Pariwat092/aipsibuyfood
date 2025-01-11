<?php

class DbHandler { 
 
    private $conn,$func;
    function __construct() {
        require_once '../include/DbConnect.php';
        $db = new DbConnect();
        $this->conn = $db->connect();
    } 

    

   
// register

    public function create_members($uuid, $username, $dsaprs, $email, $phone, $image_path) {
      
      $stmt = $this->conn->prepare("INSERT INTO `users`(`id`, `username`, `password`, `email`, `phone`, `profile_image`) VALUES (?, ?, ?, ?, ?, ?)");
  
      
      $stmt->bind_param("ssssss", $uuid, $username, $dsaprs, $email, $phone, $image_path);
  
      
      if ($stmt->execute()) {
          return true;
      } else {
          return false;
      }
  }
  
  // login
  public function login($username, $password) {
    $stmt = $this->conn->prepare("SELECT `id`, `username`, `email`, `phone`, `profile_image`, `address`, `password` FROM `users` WHERE `username` = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            return [
                "id" => $row['id'],
                "username" => $row['username'],
                "email" => $row['email'],
                "phone" => $row['phone'],
                "address" => $row['address'],
                "profile_image" => $row['profile_image']
            ]; 
        }
    }

    return false; 
}

//add address
public function add_address($id, $address) {
    
    $stmt = $this->conn->prepare("UPDATE `users` SET `address` = ? WHERE `id` = ?");
    $stmt->bind_param("ss", $address, $id); 

    if ($stmt->execute()) {
        return $address; 
    } else {
        return false; 
    }
}

public function get_banner($image_path) {
   
    $stmt = $this->conn->prepare("INSERT INTO `imagebanner_paths` (`file_path`) VALUES (?)");

    $stmt->bind_param("s", $image_path); 
    if ($stmt->execute()) {
        return true;  
    } else {
        return false;  
    }
}





}





























































?>