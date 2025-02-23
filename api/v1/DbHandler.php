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


public function verifyStore( $userstoreid ) {
    $stmt = $this->conn->prepare("UPDATE user_store SET store_status = 1 WHERE store_id = ?");
    $stmt->bind_param("s", $userstoreid);


    if ($stmt->execute()) {
        return true; 
    }
    return false; 

    
}





public function checkUserStatus($userid) {
    $stmt = $this->conn->prepare("SELECT status FROM user_customer WHERE id = ?");
    $stmt->bind_param("s", $userid);
    $stmt->execute();
    $stmt->bind_result($status);
    $stmt->fetch();
    $stmt->close();

    return $status; 
}




  
  // login
  public function login($username, $password) {
  
    $stmt = $this->conn->prepare("SELECT `id`,  `password`, `status` FROM `user_customer` WHERE `username` = ?");
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
public function get_datauseassar($id) {
    $stmt = $this->conn->prepare("SELECT store_name, owner_name, store_image, rating, store_address, description FROM `user_store` WHERE `store_id` = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        $stmt->close();
        return $data;
    } else {
        $stmt->close();
        return false;
    }
}



public function get_datauser($id) {
    
    $stmt = $this->conn->prepare("SELECT username, address, profile_image FROM `user_customer` WHERE `id` = ?");
    $stmt->bind_param("s", $id); 
    $stmt->execute();
    $result = $stmt->get_result();

   
    if($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        $stmt->close();
        return $data; 
    } else {
        $stmt->close();
        return false; 
    }
}


public function add_address($id, $address) {
    $stmt = $this->conn->prepare("UPDATE `user_customer` SET `address` = ? WHERE `id` = ?");
    $stmt->bind_param("ss", $address, $id); 
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $stmt->close();
        return true; 
    } else {
        $stmt->close();
        return false; 
    }
}








public function get_address($id) {
    $stmt = $this->conn->prepare("SELECT `address` FROM `users` WHERE `id` = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $stmt->bind_result($address);
    $stmt->fetch();
    $stmt->close();
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











//store
public function create_store($storeId, $storeName, $ownerName, $email, $hashedPassword, 
                            $description, $storePhone, $storeAddress, $storeAddressLink, 
                            $image_path, $bank_name, $bank_account_number, $account_holder_name, 
                            $promptpay_number, $delivery_person, $latitude, $longitude) {
    
    try {
        // ตรวจสอบชื่อร้านค้าซ้ำ
        $stmt = $this->conn->prepare("SELECT store_id FROM `user_store` WHERE LOWER(store_name) = LOWER(?)");
        $stmt->bind_param("s", $storeName);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) return 'store_name_exists';

        // ตรวจสอบอีเมลซ้ำ
        $stmt = $this->conn->prepare("SELECT store_id FROM `user_store` WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) return 'email_exists';

        // เพิ่มข้อมูลร้านค้า
        $stmt = $this->conn->prepare("
            INSERT INTO `user_store` 
            (`store_id`, `store_name`, `owner_name`, `email`, `password`, `description`, 
             `store_phone`, `store_address`, `store_address_link`, `store_image`, `bank_name`, 
             `bank_account_number`, `account_holder_name`, `promptpay_number`, `delivery_person`, 
             `latitude`, `longitude`) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param("ssssssssssssssidd", $storeId, $storeName, $ownerName, $email, 
                          $hashedPassword, $description, $storePhone, $storeAddress, 
                          $storeAddressLink, $image_path, $bank_name, $bank_account_number, 
                          $account_holder_name, $promptpay_number, $delivery_person, 
                          $latitude, $longitude);

        return $stmt->execute() ? true : false;
    } catch (Exception $e) {
        return false;
    }
}


public function checkStoreStatus($userstoreid) {
    $stmt = $this->conn->prepare("SELECT store_status FROM user_store WHERE store_id = ?");
    $stmt->bind_param("s", $userstoreid);
    $stmt->execute();
    $stmt->bind_result($store_status);
    $stmt->fetch();
    $stmt->close();

    return $store_status; 
}



public function login_store($email_store, $password_store) {
  
    $stmt = $this->conn->prepare("SELECT `store_id`, `password`, `store_status` FROM `user_store` WHERE `email` = ?");
    $stmt->bind_param("s", $email_store);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
 
        if ($row['store_status'] == 0) {
            return [
                "res_code" => "01",
                "res_text" => "ยังไม่ยืนยันตัวตน"
            ];
        }

        if (password_verify($password_store, $row['password'])) {
            return [
                "res_code" => "00",
                "res_text" => "เข้าสู่ระบบสำเร็จ",
                "user" => [
                    "id" => $row['store_id'],  
                ]
            ];
        } else {
            return [
                "res_code" => "01",
                "res_text" => "อีเมลหรือรหัสผ่านไม่ถูกต้อง"
            ];
        }
    }

    return [
        "res_code" => "01",
        "res_text" => "อีเมลหรือรหัสผ่านไม่ถูกต้อง"
    ];
}


















//add product


public function create_product($pbid, $storeId, $productName, $price, $expirationDate, $productDescription, $image_path, $categoryId, $quantity, $isyellow) {
    
    $stmt = $this->conn->prepare(" 
        INSERT INTO `products` (
            `product_id`, `store_id`, `product_name`, `price`, `expiration_date`, `description`, `image_url`, `category_id`, `stock_quantity`, `is_yellow_sign`
        ) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

   
    $stmt->bind_param("ssssssssii", $pbid, $storeId, $productName, $price, $expirationDate, $productDescription, $image_path, $categoryId, $quantity, $isyellow);
    
    if ($stmt->execute()) {
        return true; 
    } else {
        error_log("SQL Execute Failed: " . $stmt->error);
        return false;
    }
}




public function getpularstore () {
    $stmt = $this->conn->prepare("SELECT * FROM user_store ORDER BY rating DESC, RAND() LIMIT 5;
");
    $stmt->execute(); 
    $result = $stmt->get_result(); 
    $output = array(); 

    if($result->num_rows > 0){
        while($res = $result->fetch_assoc()) {
            $response = array(
                "store_id" => $res['store_id'],
                "store_name" => $res['store_name'],
                "latitude" => $res['latitude'],
                "longitude" => $res['longitude'],
                "rating" => $res['rating'],
                "store_image" => $res['store_image'],
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

public function getstoreall () {
    $stmt = $this->conn->prepare("SELECT * FROM user_store;
");
    $stmt->execute(); 
    $result = $stmt->get_result(); 
    $output = array(); 

    if($result->num_rows > 0){
        while($res = $result->fetch_assoc()) {
            $response = array(
                "store_id" => $res['store_id'],
                "store_name" => $res['store_name'],
                "latitude" => $res['latitude'],
                "longitude" => $res['longitude'],
                "rating" => $res['rating'],
                "store_image" => $res['store_image'],
                "description" => $res['description'],
                "store_address" => $res['store_address'],

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

















public function viewProducts() {
    $stmt = $this->conn->prepare("
        SELECT 
            p.product_id, 
            p.store_id, 
            p.product_name, 
            p.price, 
            p.expiration_date, 
            p.description, 
            p.image_url, 
            p.category_id,
            p.stock_quantity,
            p.is_yellow_sign,
            us.delivery_person, 
            us.store_address_link, 
            us.latitude, 
            us.longitude
        FROM products p
        JOIN user_store us ON p.store_id = us.store_id
    ");
    
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
                "expiration_date" => $res['expiration_date'],
                "description" => $res['description'],
                "image_url" => $res['image_url'],
                "category_id" => $res['category_id'],
                "stock_quantity" => $res['stock_quantity'],
                "is_yellow_sign" => $res['is_yellow_sign'],
                "delivery_person" => $res['delivery_person'],
                "store_address_link" => $res['store_address_link'],
                "latitude" => $res['latitude'],
                "longitude" => $res['longitude']
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
    $category_id = 1; // ตัวอย่าง category_id
    $stmt = $this->conn->prepare("
        SELECT 
            p.product_id, 
            p.store_id, 
            p.product_name, 
            p.price, 
            p.expiration_date, 
            p.description, 
            p.image_url, 
            p.category_id,
            p.stock_quantity,
            p.is_yellow_sign,
            us.delivery_person, 
            us.store_address_link, 
            us.latitude, 
            us.longitude
        FROM products p
        JOIN user_store us ON p.store_id = us.store_id
        WHERE p.category_id = ?
    ");
    
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
                "expiration_date" => $res['expiration_date'],
                "description" => $res['description'],
                "image_url" => $res['image_url'],
                "category_id" => $res['category_id'],
                "stock_quantity" => $res['stock_quantity'],
                "is_yellow_sign" => $res['is_yellow_sign'],
                "delivery_person" => $res['delivery_person'],
                "store_address_link" => $res['store_address_link'], 
                "latitude" => $res['latitude'], 
                "longitude" => $res['longitude']
            );
            $output[] = $response;
        }
    }

    $stmt->close();
    return !empty($output) ? $output : NULL;
}

public function viewProducts_dessert() {
    $category_id = 2; // ตัวอย่าง category_id
    $stmt = $this->conn->prepare("
        SELECT 
            p.product_id, 
            p.store_id, 
            p.product_name, 
            p.price, 
            p.expiration_date, 
            p.description, 
            p.image_url, 
            p.category_id, 
            p.stock_quantity,
            p.is_yellow_sign,
            us.delivery_person,
            us.store_address_link, 
            us.latitude, 
            us.longitude
        FROM products p
        JOIN user_store us ON p.store_id = us.store_id
        WHERE p.category_id = ?
    ");
    
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
                "expiration_date" => $res['expiration_date'],
                "description" => $res['description'],
                "image_url" => $res['image_url'],
                "category_id" => $res['category_id'],
                "stock_quantity" => $res['stock_quantity'],
                "is_yellow_sign" => $res['is_yellow_sign'],
                "delivery_person" => $res['delivery_person'],
                "store_address_link" => $res['store_address_link'], 
                "latitude" => $res['latitude'], 
                "longitude" => $res['longitude']
            );
            $output[] = $response;
        }
    }

    $stmt->close();
    return !empty($output) ? $output : NULL;
}


public function viewProducts_fresh_food() {
    $category_id = 3; // ตัวอย่าง category_id
    $stmt = $this->conn->prepare("
        SELECT 
            p.product_id, 
            p.store_id, 
            p.product_name, 
            p.price, 
            p.expiration_date, 
            p.description, 
            p.image_url, 
            p.category_id, 
            p.stock_quantity,
            p.is_yellow_sign,
            us.delivery_person,
            us.store_address_link, 
            us.latitude, 
            us.longitude
        FROM products p
        JOIN user_store us ON p.store_id = us.store_id
        WHERE p.category_id = ?
    ");
    
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
                "expiration_date" => $res['expiration_date'],
                "description" => $res['description'],
                "image_url" => $res['image_url'],
                "category_id" => $res['category_id'],
                "stock_quantity" => $res['stock_quantity'],
                "is_yellow_sign" => $res['is_yellow_sign'],
                "delivery_person" => $res['delivery_person'],
                "store_address_link" => $res['store_address_link'], 
                "latitude" => $res['latitude'], 
                "longitude" => $res['longitude']
            );
            $output[] = $response;
        }
    }

    $stmt->close();
    return !empty($output) ? $output : NULL;
}
public function viewProducts_other() {
    $category_id = 4; // ตัวอย่าง category_id
    $stmt = $this->conn->prepare("
        SELECT 
            p.product_id, 
            p.store_id, 
            p.product_name, 
            p.price, 
            p.expiration_date, 
            p.description, 
            p.image_url, 
            p.category_id, 
            p.stock_quantity,
            p.is_yellow_sign,
            us.delivery_person,
            us.store_address_link, 
            us.latitude, 
            us.longitude
        FROM products p
        JOIN user_store us ON p.store_id = us.store_id
        WHERE p.category_id = ?
    ");
    
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
                "expiration_date" => $res['expiration_date'],
                "description" => $res['description'],
                "image_url" => $res['image_url'],
                "category_id" => $res['category_id'],
                "stock_quantity" => $res['stock_quantity'],
                "is_yellow_sign" => $res['is_yellow_sign'],
                "delivery_person" => $res['delivery_person'],
                "store_address_link" => $res['store_address_link'], 
                "latitude" => $res['latitude'], 
                "longitude" => $res['longitude']
            );
            $output[] = $response;
        }
    }

    $stmt->close();
    return !empty($output) ? $output : NULL;
}





public function searchProducts($searchTerm) {
    $stmt = $this->conn->prepare("
        SELECT 
            p.product_id, 
            p.store_id, 
            p.product_name, 
            p.price, 
            p.expiration_date, 
            p.description, 
            p.image_url, 
            p.category_id,
            p.stock_quantity,
            p.is_yellow_sign, 
            us.delivery_person,
            us.store_address_link, 
            us.latitude, 
            us.longitude
        FROM products p
        JOIN user_store us ON p.store_id = us.store_id
        WHERE p.product_name LIKE ?
    ");
    
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
                "expiration_date" => $res['expiration_date'],
                "description" => $res['description'],
                "image_url" => $res['image_url'],
                "category_id" => $res['category_id'],
                "stock_quantity" => $res['stock_quantity'],
                "is_yellow_sign" => $res['is_yellow_sign'],
                "delivery_person" => $res['delivery_person'],
                "store_address_link" => $res['store_address_link'], 
                "latitude" => $res['latitude'], 
                "longitude" => $res['longitude']
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