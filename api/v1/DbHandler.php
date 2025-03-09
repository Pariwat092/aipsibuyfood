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
 
public function editname($userid, $newmname) {
   
    $stmt_check = $this->conn->prepare("SELECT id FROM user_customer WHERE username = ?");
    $stmt_check->bind_param("s", $newmname);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        return "duplicate"; 
    }

   
    $stmt = $this->conn->prepare("UPDATE user_customer SET username = ? WHERE id = ?");
    $stmt->bind_param("ss", $newmname, $userid);

    if ($stmt->execute()) {
        return "success"; 
    }
    return "error"; 
}



public function verifyStore( $userstoreid ) {
    $stmt = $this->conn->prepare("UPDATE user_store SET store_status = 1 WHERE store_id = ?");
    $stmt->bind_param("s", $userstoreid);


    if ($stmt->execute()) {
        return true; 
    }
    return false; 

    
}

public function editPhone($userid, $newPhone) {
 
    $stmt_check = $this->conn->prepare("SELECT id FROM user_customer WHERE phone = ?");
    $stmt_check->bind_param("s", $newPhone); 
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        return "duplicate"; 
    }

    
    $stmt = $this->conn->prepare("UPDATE user_customer SET phone = ? WHERE id = ?");
    $stmt->bind_param("ss", $newPhone, $userid); 

    if ($stmt->execute()) {
        return "success"; 
    }
    return "error"; 
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
    $stmt = $this->conn->prepare("SELECT store_name, store_image, rating, store_address, description FROM `user_store` WHERE `store_id` = ?");
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

public function get_orders_by_store($store_id) {
    $sql = "SELECT 
                o.op_id AS order_id,
                o.total_price,
                o.payment_status,
                o.order_status,
                p.store_id,
                s.store_name,
                u.username AS customer_name,
                u.address AS delivery_address,
                u.phone AS customer_phone
            FROM orders o
            JOIN order_items oi ON o.op_id = oi.order_id
            JOIN products p ON oi.product_id = p.product_id
            JOIN user_store s ON p.store_id = s.store_id
            JOIN user_customer u ON o.user_id = u.id
            WHERE p.store_id = ?
            GROUP BY o.op_id, p.store_id";
    
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("s", $store_id);
    $stmt->execute();
    $result = $stmt->get_result(); 

    if ($result->num_rows > 0) {
        $orders = [];
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
        $stmt->close();
        return $orders;
    } else {
        $stmt->close();
        return false;
    }
}



public function get_datauseassarinformation($id) {
    $stmt = $this->conn->prepare("SELECT store_name, owner_name,  email, store_phone FROM `user_store` WHERE `store_id` = ?");
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
    
    $stmt = $this->conn->prepare("SELECT username, address, profile_image , email , phone FROM `user_customer` WHERE `id` = ?");
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

    $stmt->bind_param("S", $image_path); 
    if ($stmt->execute()) {
        return true;  
    } else {
        return false;  
    }
}




public function delete_Products($id) {
    $stmt = $this->conn->prepare("DELETE FROM products WHERE product_id = ?");
    $stmt->bind_param("s", $id); // "i" หมายถึง integer

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


public function add_order($order_id, $user_id, $total_price, $order_items) {
    $this->conn->begin_transaction(); // เริ่มต้น Transaction

    try {
        // เพิ่มคำสั่งซื้อในตาราง orders
        $stmt = $this->conn->prepare("
            INSERT INTO `orders` (`op_id`, `user_id`, `total_price`, `payment_status`, `order_status`, `created_at`)
            VALUES (?, ?, ?, 'pending', 'pending', NOW())
        ");

        if (!$stmt) {
            throw new Exception("Prepare statement failed for orders: " . $this->conn->error);
        }

        $stmt->bind_param("ssd", $order_id, $user_id, $total_price);

        if (!$stmt->execute()) {
            throw new Exception("Failed to insert order: " . $stmt->error);
        }

        // เพิ่มรายการสินค้าในตาราง order_items
        $stmt_items = $this->conn->prepare("
            INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`)
            VALUES (?, ?, ?, ?, ?)
        ");

        if (!$stmt_items) {
            throw new Exception("Prepare statement failed for order_items: " . $this->conn->error);
        }

        foreach ($order_items as $item) {
            $order_item_id = $this->generateOrderItemId(); // สร้าง ID สำหรับ order_item
            $product_id = $item['product_id'];
            $quantity = $item['quantity'];
            $price = $item['price'];

            error_log("Order Item ID: " . $order_item_id);
            error_log("Order ID: " . $order_id);
            error_log("Product ID: " . $product_id);
            error_log("Quantity: " . $quantity);
            error_log("Price: " . $price);

            $stmt_items->bind_param("sssii", $order_item_id, $order_id, $product_id, $quantity, $price);

            if (!$stmt_items->execute()) {
                throw new Exception("Failed to insert order item: " . $stmt_items->error);
            }
        }

        $this->conn->commit(); // บันทึกการเปลี่ยนแปลงถ้าสำเร็จทั้งหมด
        return true;

    } catch (Exception $e) {
        $this->conn->rollback(); // ยกเลิกการทำงานหากเกิดข้อผิดพลาด
        error_log("Transaction rolled back: " . $e->getMessage()); // บันทึกข้อผิดพลาดใน log
        return false;
    }
}

// ฟังก์ชันสำหรับสร้าง Order Item ID
private function generateOrderItemId() {
    return 'OI' . bin2hex(random_bytes(8)); // สร้าง ID 18 ตัวอักษร
}



public function isStoreExist($storeId) {
    $sql = "SELECT 1 FROM user_store WHERE store_id = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("s", $storeId);
    $stmt->execute();
    $stmt->store_result();
    return $stmt->num_rows > 0;
}

public function update_store($storeId, $storeName, $storeAddress, $storeDescription, $image_path) {
    $sql = "UPDATE `user_store` SET `store_name`=?, `store_address`=?, `description`=?, 
            `store_image`=IFNULL(?, `store_image`), `updated_at`=NOW() WHERE `store_id`=?";

    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("sssss", $storeName, $storeAddress, $storeDescription, 
        $image_path, $storeId);

    if ($stmt->execute()) {
        return true; 
    } else {
        error_log("SQL Execute Failed: " . $stmt->error);
        return false;
    }
}



public function isOrderOwnedByStore($orderId, $storeId) {
    $sql = "SELECT 1 
            FROM orders o
            JOIN order_items oi ON o.op_id = oi.order_id
            JOIN products p ON oi.product_id = p.product_id
            JOIN user_store s ON p.store_id = s.store_id
            WHERE o.op_id = ? AND s.store_id = ?";

    $stmt = $this->conn->prepare($sql);
    if (!$stmt) {
        die("SQL Error: " . $this->conn->error);
    }
    
    $stmt->bind_param("ss", $orderId, $storeId);
    $stmt->execute();
    $stmt->store_result();
    return $stmt->num_rows > 0;
}



public function updateOrderStatus($orderId, $orderStatus) {
    $sql = "UPDATE orders SET order_status = ?, updated_at = NOW() WHERE op_id = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("ss", $orderStatus, $orderId);
    if ($stmt->execute()) {
        return true;
    } else {
        error_log("SQL Execute Failed: " . $stmt->error);
        return false;
    }
}


public function getOrderById($orderId) {
    $sql = "SELECT payment_status, order_status FROM orders WHERE op_id = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("s", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}


public function updatePaymentStatus($orderId, $paymentStatus) {
    $sql = "UPDATE orders SET payment_status = ?, updated_at = NOW() WHERE op_id = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("ss", $paymentStatus, $orderId);
    if ($stmt->execute()) {
        return true;
    } else {
        error_log("SQL Execute Failed: " . $stmt->error);
        return false;
    }
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

public function update_product($productId, $productName, $price, $expirationDate, $productDescription, $image_path, $categoryId, $quantity, $isyellow) {

    $sql = "UPDATE `products` SET `product_name`=?, `price`=?, `expiration_date`=?, `description`=?, 
            `image_url`=IFNULL(?, `image_url`), `category_id`=?, `stock_quantity`=?, `is_yellow_sign`=? 
            WHERE `product_id`=?";

    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("ssssssiis", $productName, $price, $expirationDate, $productDescription, 
        $image_path, $categoryId, $quantity, $isyellow, $productId);

    if ($stmt->execute()) {
        return true; 
    } else {
        error_log("SQL Execute Failed: " . $stmt->error);
        return false;
    }
}


public function add_cart($user_id, $store_id, $product_id, $quantity, $cart_id, $cart_item_id) {
    $quantity = isset($quantity) && $quantity !== null ? $quantity : 1;

   
    $stmt = $this->conn->prepare("SELECT cart_id FROM cart WHERE user_id = ? AND store_id = ? LIMIT 1");
    $stmt->bind_param("ss", $user_id, $store_id);
    $stmt->execute();
    $stmt->bind_result($existing_cart_id);
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
       
        $stmt->fetch();
        $cart_id = $existing_cart_id;
    } else {
       
        $stmt = $this->conn->prepare(" 
            INSERT INTO `cart` (`cart_id`, `user_id`, `store_id`) 
            VALUES (?, ?, ?)
        ");
        if (!$stmt) {
            error_log("SQL Prepare Failed (cart): " . $this->conn->error);
            return false;
        }

        $stmt->bind_param("sss", $cart_id, $user_id, $store_id);

        if (!$stmt->execute()) {
            error_log("SQL Execute Failed (cart): " . $stmt->error);
            return false;
        }
    }

    $stmt->close();

    
    $stmt = $this->conn->prepare("
        SELECT quantity FROM cart_items WHERE cart_id = ? AND product_id = ?
    ");
    $stmt->bind_param("ss", $cart_id, $product_id);
    $stmt->execute();
    $stmt->bind_result($currentQuantity);
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
       
        $stmt->fetch();
        $newQuantity = $currentQuantity + $quantity;

        $stmt = $this->conn->prepare("
            UPDATE cart_items SET quantity = ? WHERE cart_id = ? AND product_id = ?
        ");
        $stmt->bind_param("iss", $newQuantity, $cart_id, $product_id);
    } else {
       
        $stmt = $this->conn->prepare(" 
            INSERT INTO `cart_items` (`cart_item_id`, `cart_id`, `product_id`, `quantity`) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("sssi", $cart_item_id, $cart_id, $product_id, $quantity);
    }

    if (!$stmt->execute()) {
        error_log("SQL Execute Failed (cart_items): " . $stmt->error);
        return false;
    }

    $stmt->close();

    return true;
}


public function getUserById($userId) {
    $sql = "SELECT * FROM user_customer WHERE id = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("s", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

public function deleteUser($userId) {
    $sql = "DELETE FROM user_customer WHERE id = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("s", $userId);
    return $stmt->execute();
}


public function getUserCartWithProducts($user_id) {
    $stmt = $this->conn->prepare("
        SELECT 
            c.cart_id,
            s.store_name,
            ci.product_id,
            p.product_name,
            p.image_url,
            ci.quantity,
            p.price
        FROM cart c
        JOIN user_store s ON c.store_id = s.store_id
        JOIN cart_items ci ON c.cart_id = ci.cart_id
        JOIN products p ON ci.product_id = p.product_id
        WHERE c.user_id = ?
        ORDER BY s.store_name, p.product_name;
    ");
    
    $stmt->bind_param("s", $user_id);
    
    if (!$stmt->execute()) {
        error_log("SQL Execute Failed (getUserCartWithProducts): " . $stmt->error);
        return false;
    }

    $result = $stmt->get_result();
    $cartData = [];

    while ($row = $result->fetch_assoc()) {
        $storeName = $row['store_name'];
        $cart_id = $row['cart_id'];

        if (!isset($cartData[$cart_id])) {
            $cartData[$cart_id] = [
                'store_name' => $storeName,
                'products' => []
            ];
        }

        $cartData[$cart_id]['products_cart'][] = [
            'product_id' => $row['product_id'],
            'product_name' => $row['product_name'],
            'quantity' => $row['quantity'],
            'price' => $row['price'],
            'image_url' => $row['image_url'] 
        ];
    }

    $stmt->close();
    
    return $cartData;
}

public function getUserOrdersWithItems($user_id) {
    $stmt = $this->conn->prepare("
        SELECT 
            o.op_id AS order_id,
            o.total_price,
            o.payment_status,
            o.order_status,
            o.created_at,
            oi.product_id,
            p.product_name,
            p.image_url,
            oi.quantity,
            oi.price
        FROM orders o
        JOIN order_items oi ON o.op_id = oi.order_id
        JOIN products p ON oi.product_id = p.product_id
        WHERE o.user_id = ?
        ORDER BY o.created_at DESC, p.product_name;
    ");
    
    $stmt->bind_param("s", $user_id);
    
    if (!$stmt->execute()) {
        error_log("SQL Execute Failed (getUserOrdersWithItems): " . $stmt->error);
        return false;
    }

    $result = $stmt->get_result();
    $orderData = [];

    while ($row = $result->fetch_assoc()) {
        $orderId = $row['order_id'];

        if (!isset($orderData[$orderId])) {
            $orderData[$orderId] = [
                'order_id' => $orderId,
                'total_price' => $row['total_price'],
                'payment_status' => $row['payment_status'],
                'order_status' => $row['order_status'],
                'created_at' => $row['created_at'],
                'products' => []
            ];
        }

        $orderData[$orderId]['products'][] = [
            'product_id' => $row['product_id'],
            'product_name' => $row['product_name'],
            'quantity' => $row['quantity'],
            'price' => $row['price'],
            'image_url' => $row['image_url']
        ];
    }

    $stmt->close();
    
    return array_values($orderData);
}

public function get_orders_with_products($store_id) {
    $sql = "SELECT 
                o.op_id AS order_id,
                o.total_price,
                o.payment_status,
                o.order_status,
                pr.store_id,
                s.store_name,
                u.username AS customer_name,
                u.address AS delivery_address,
                u.phone AS customer_phone,
                pr.product_name,
                pr.image_url,
                oi.quantity,
                oi.price AS item_price,
                o.updated_at
            FROM orders o
            JOIN order_items oi ON o.op_id = oi.order_id
            JOIN products pr ON oi.product_id = pr.product_id
            JOIN user_store s ON pr.store_id = s.store_id
            JOIN user_customer u ON o.user_id = u.id
            WHERE pr.store_id = ? 
            AND o.order_status != '' 
            AND (o.order_status != 'completed' 
                 OR TIMESTAMPDIFF(MINUTE, o.updated_at, NOW()) <= 3)
            ORDER BY 
                CASE WHEN o.order_status = 'completed' THEN 1 ELSE 0 END, 
                o.updated_at DESC";

    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("s", $store_id);
    $stmt->execute();
    $result = $stmt->get_result(); 

    if ($result->num_rows > 0) {
        $orders = [];
        while ($row = $result->fetch_assoc()) {
            $order_id = $row['order_id'];

            if (!isset($orders[$order_id])) {
                $orders[$order_id] = [
                    'order_id' => $row['order_id'],
                    'total_price' => $row['total_price'],
                    'payment_status' => $row['payment_status'],
                    'order_status' => $row['order_status'],
                    'store_id' => $row['store_id'],
                    'store_name' => $row['store_name'],
                    'customer_name' => $row['customer_name'],
                    'delivery_address' => $row['delivery_address'],
                    'customer_phone' => $row['customer_phone'],
                    'products' => []
                ];
            }

            $orders[$order_id]['products'][] = [
                'product_name' => $row['product_name'],
                'quantity' => $row['quantity'],
                'item_price' => $row['item_price'],
                'image_url' => $row['image_url']
            ];
        }
        $stmt->close();

        return array_values($orders);
    } else {
        $stmt->close();
        return false;
    }
}








public function deleteProductFromCart($cart_id, $product_id) {
    $stmt = $this->conn->prepare("
        DELETE FROM cart_items 
        WHERE cart_id = ? AND product_id = ?
    ");
    
    $stmt->bind_param("ss", $cart_id, $product_id);
    
    if (!$stmt->execute()) {
        error_log("SQL Execute Failed (deleteProductFromCart): " . $stmt->error);
        return false;
    }

    $affectedRows = $stmt->affected_rows;
    $stmt->close();

    // ตรวจสอบว่ามีการลบข้อมูลจริงหรือไม่
    return $affectedRows > 0;
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
public function viewProductsToday() {
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
        WHERE DATE(p.created_at) = CURDATE()  -- ดึงเฉพาะสินค้าที่ถูกเพิ่มวันนี้
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

public function suggestProducts($searchTerm) {
    $stmt = $this->conn->prepare("
        SELECT product_name 
        FROM products 
        WHERE product_name LIKE ? 
        LIMIT 5
    ");
    
    $searchTerm = "%" . $searchTerm . "%";
    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $suggestions = [];

    while ($row = $result->fetch_assoc()) {
        $suggestions[] = $row['product_name'];
    }

    $stmt->close();
    return $suggestions;
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


public function get_data_products_isstore($store_id) {
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
        WHERE p.store_id = ?
    ");
    
    $stmt->bind_param("s", $store_id);
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