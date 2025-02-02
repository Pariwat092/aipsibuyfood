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
  
    $stmt = $this->conn->prepare("SELECT id FROM `user_customer` WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

   
    if ($result->num_rows > 0) {
        return 'username_exists';  
    }

    
    $stmt = $this->conn->prepare("SELECT id FROM `user_customer` WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

   
    if ($result->num_rows > 0) {
        return 'email_exists';  
    }

   
    $stmt = $this->conn->prepare("INSERT INTO `user_customer`(`id`, `username`, `password`, `email`, `phone`, `profile_image`) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $uuid, $username, $dsaprs, $email, $phone, $image_path);

    if ($stmt->execute()) {
        return true;
    } else {
        return false;
    }
}

public function verifyUser( $userid ) {
    $stmt = $this->conn->prepare("UPDATE user_customer SET status = 1 WHERE id = ?");
    $stmt->bind_param("s", $userid);


    if ($stmt->execute()) {
        return true; 
    }
    return false; 

    
}




  
  // login
  public function login($username, $password) {
  
    $stmt = $this->conn->prepare("SELECT `id`, `username`, `email`, `phone`, `profile_image`, `address`, `password`, `status` FROM `user_customer` WHERE `username` = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
     
        if ($row['status'] == 0) {
            return [
                "res_code" => "01",
                "res_text" => "ยังไม่ยืนยันตัวตน"
            ];
        }

       
        if (password_verify($password, $row['password'])) {
            return [
                "res_code" => "00",
                "res_text" => "เข้าสู่ระบบสำเร็จ",
                "user" => [
                    "id" => $row['id'],
                    "username" => $row['username'],
                    "email" => $row['email'],
                    "phone" => $row['phone'],
                    "address" => $row['address'],
                    "profile_image" => $row['profile_image']
                ]
            ];
        } else {
            return [
                "res_code" => "01",
                "res_text" => "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง"
            ];
        }
    }

    return [
        "res_code" => "01",
        "res_text" => "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง"
    ];
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

public function create_store(
    $uuid, $userId, $storeName, $ownerName, $email, $password, $description, 
    $storePhone, $storeAddress, $storeAddressLink, $image_path, $bankAccountNumber, 
    $accountHolderName, $deliveryPerson, $promptpayNumber, $latitude, $longitude, $bank_Name
) {
    $stmt = $this->conn->prepare(" 
        INSERT INTO `store_db` (
            `store_id`, `user_id`, `store_name`, `owner_name`, `email`, `password`, `description`, 
            `store_phone`, `store_address`, `store_address_link`, `store_image`, 
            `bank_account_number`, `account_holder_name`, `delivery_person`, 
            `promptpay_number`, `latitude`, `longitude`, `bank_name`
        ) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "ssssssssssiddisis", 
        $uuid, $userId, $storeName, $ownerName, $email, $password, $description, 
        $storePhone, $storeAddress, $storeAddressLink, $image_path, $bankAccountNumber, 
        $accountHolderName, $deliveryPerson, $promptpayNumber, $latitude, $longitude, $bank_Name
    );

    if ($stmt->execute()) {
        return true; 
    } else {
        return false; 
    }
}


//add product


public function create_product( $uuid, $storeId, $productName, $price,$expirationDays, $productDescription, 
$image_path, $categoryId) {
   
    $stmt = $this->conn->prepare(" INSERT INTO `products` ( `product_id`, `store_id`, `product_name`, `price`, `expiration_days`, `description`, `image_url`, `category_id`
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param( "sssdsssi", $uuid, $storeId, $productName, $price,$expirationDays, $productDescription, 
    $image_path, $categoryId       
);

if ($stmt->execute()) {
    return true; 
} else {
    error_log("SQL Execute Failed: " . $stmt->error);
    return false;
}
}


public function viewProducts() {
    $stmt = $this->conn->prepare("SELECT * FROM `products`");
    $stmt->execute(); 
    $result = $stmt->get_result(); 
    $output = array(); 

    if($result->num_rows > 0){
        while($res = $result->fetch_assoc()) {
            $response = array(
                "product_id" => $res['product_id'],
                "store_id" => $res['store_id'],
                "product_name" => $res['product_name'],
                "price" => $res['price'],
                "expiration_days" => $res['expiration_days'],
                "description" => $res['description'],
                "image_url" => $res['image_url'],
                "category_id" => $res['category_id'],
                "created_at" => $res['created_at'],
                "updated_at" => $res['updated_at']
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
public function viewProducts_drinks() {
    $category_id = 1; 
    $stmt = $this->conn->prepare("SELECT * FROM `products` WHERE `category_id` = ?");
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $output = array();

if ($result->num_rows > 0) {
    while ($res = $result->fetch_assoc()) {
        $response = array(
            "product_id" => $res['product_id'],
            "store_id" => $res['store_id'],
            "product_name" => $res['product_name'],
            "price" => $res['price'],
            "expiration_days" => $res['expiration_days'],
            "description" => $res['description'],
            "image_url" => $res['image_url'],
            "category_id" => $res['category_id'],
            "created_at" => $res['created_at'],
            "updated_at" => $res['updated_at']
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
public function viewProducts_dessert() {
    $category_id = 2; 
    $stmt = $this->conn->prepare("SELECT * FROM `products` WHERE `category_id` = ?");
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $output = array();

if ($result->num_rows > 0) {
    while ($res = $result->fetch_assoc()) {
        $response = array(
            "product_id" => $res['product_id'],
            "store_id" => $res['store_id'],
            "product_name" => $res['product_name'],
            "price" => $res['price'],
            "expiration_days" => $res['expiration_days'],
            "description" => $res['description'],
            "image_url" => $res['image_url'],
            "category_id" => $res['category_id'],
            "created_at" => $res['created_at'],
            "updated_at" => $res['updated_at']
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
public function viewProducts_fresh_food() {
    $category_id = 3; 
    $stmt = $this->conn->prepare("SELECT * FROM `products` WHERE `category_id` = ?");
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $output = array();

if ($result->num_rows > 0) {
    while ($res = $result->fetch_assoc()) {
        $response = array(
            "product_id" => $res['product_id'],
            "store_id" => $res['store_id'],
            "product_name" => $res['product_name'],
            "price" => $res['price'],
            "expiration_days" => $res['expiration_days'],
            "description" => $res['description'],
            "image_url" => $res['image_url'],
            "category_id" => $res['category_id'],
            "created_at" => $res['created_at'],
            "updated_at" => $res['updated_at']
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
public function viewProducts_other() {
    $category_id = 4; 
    $stmt = $this->conn->prepare("SELECT * FROM `products` WHERE `category_id` = ?");
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $output = array();

if ($result->num_rows > 0) {
    while ($res = $result->fetch_assoc()) {
        $response = array(
            "product_id" => $res['product_id'],
            "store_id" => $res['store_id'],
            "product_name" => $res['product_name'],
            "price" => $res['price'],
            "expiration_days" => $res['expiration_days'],
            "description" => $res['description'],
            "image_url" => $res['image_url'],
            "category_id" => $res['category_id'],
            "created_at" => $res['created_at'],
            "updated_at" => $res['updated_at']
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
public function searchProducts($searchTerm) {
   
    $stmt = $this->conn->prepare("SELECT * FROM `products` WHERE `product_name` LIKE ?");
    
   
    $searchTerm = "%" . $searchTerm . "%";
    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $output = array();

    if ($result->num_rows > 0) {
        while ($res = $result->fetch_assoc()) {
            $response = array(
                "product_id" => $res['product_id'],
                "store_id" => $res['store_id'],
                "product_name" => $res['product_name'],
                "price" => $res['price'],
                "expiration_days" => $res['expiration_days'],
                "description" => $res['description'],
                "image_url" => $res['image_url'],
                "category_id" => $res['category_id'],
                "created_at" => $res['created_at'],
                "updated_at" => $res['updated_at']
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





}



































































?>