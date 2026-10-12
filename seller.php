<?php
// seller.php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'seller') {
    header("Location: signin.php");
    exit();
}
include 'dbcon.php';

// ============================================
// ORDER STATUS MANAGEMENT - AYOS NA MAY SHIP AT DELIVER BUTTONS
// ============================================

// Generate tracking number 
function generateTrackingNumber() {
    $prefix = 'ISSANG';
    $date = date('Ymd');
    $random = strtoupper(substr(uniqid(), -6));
    return $prefix . '-' . $date . '-' . $random;
}

// PROCESS ORDER 
if (isset($_GET['process'])) {
    $order_id = (int)$_GET['process'];
    $tracking_number = generateTrackingNumber();
    $conn->query("UPDATE orders SET status = 'processing', processed_date = NOW(), tracking_number = '$tracking_number' WHERE id = $order_id");
    header("Location: seller.php?tab=orders");
    exit();
}

// SHIP ORDER 
if (isset($_GET['ship'])) {
    $order_id = (int)$_GET['ship'];
    $conn->query("UPDATE orders SET status = 'shipped', shipped_date = NOW() WHERE id = $order_id");
    header("Location: seller.php?tab=orders");
    exit();
}

// DELIVER ORDER Shipped 
if (isset($_GET['deliver'])) {
    $order_id = (int)$_GET['deliver'];
    $conn->query("UPDATE orders SET status = 'delivered', delivered_date = NOW() WHERE id = $order_id");
    header("Location: seller.php?tab=orders");
    exit();
}

// CANCEL ORDER
if (isset($_GET['cancel'])) {
    $order_id = (int)$_GET['cancel'];
    $conn->query("UPDATE orders SET status = 'cancelled' WHERE id = $order_id");
    header("Location: seller.php?tab=orders");
    exit();
}

// UPDATE SHIPPING PARTNER
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_shipping'])) {
    $order_id = (int)$_POST['order_id'];
    $shipping_partner = $conn->real_escape_string($_POST['shipping_partner']);
    
    // ITO ANG IDINAGDAG - check kung may laman
    if (!empty($shipping_partner)) {
        $check = $conn->query("SELECT tracking_number FROM orders WHERE id = $order_id");
        $row = $check->fetch_assoc();
        
        if (empty($row['tracking_number'])) {
            $tracking_number = generateTrackingNumber();
            $conn->query("UPDATE orders SET shipping_partner = '$shipping_partner', tracking_number = '$tracking_number' WHERE id = $order_id");
        } else {
            $conn->query("UPDATE orders SET shipping_partner = '$shipping_partner' WHERE id = $order_id");
        }
    }
    
    header("Location: seller.php?tab=orders");
    exit();
}
// Create uploads folder if not exists
if (!file_exists('uploads')) {
    mkdir('uploads', 0777, true);
}

// ============================================
// SALES CALCULATIONS
// ============================================

$total_sales_query = $conn->query("SELECT SUM(CAST(REPLACE(total_amount, '₱', '') AS DECIMAL(10,2))) as total FROM orders WHERE status = 'delivered'");
$total_sales = $total_sales_query->fetch_assoc()['total'] ?? 0;

$today_sales_query = $conn->query("SELECT SUM(CAST(REPLACE(total_amount, '₱', '') AS DECIMAL(10,2))) as total FROM orders WHERE status = 'delivered' AND DATE(order_date) = CURDATE()");
$today_sales = $today_sales_query->fetch_assoc()['total'] ?? 0;

$this_week_sales_query = $conn->query("SELECT SUM(CAST(REPLACE(total_amount, '₱', '') AS DECIMAL(10,2))) as total FROM orders WHERE status = 'delivered' AND YEARWEEK(order_date) = YEARWEEK(CURDATE())");
$this_week_sales = $this_week_sales_query->fetch_assoc()['total'] ?? 0;

$this_month_sales_query = $conn->query("SELECT SUM(CAST(REPLACE(total_amount, '₱', '') AS DECIMAL(10,2))) as total FROM orders WHERE status = 'delivered' AND MONTH(order_date) = MONTH(CURDATE()) AND YEAR(order_date) = YEAR(CURDATE())");
$this_month_sales = $this_month_sales_query->fetch_assoc()['total'] ?? 0;

$last_month_sales_query = $conn->query("SELECT SUM(CAST(REPLACE(total_amount, '₱', '') AS DECIMAL(10,2))) as total FROM orders WHERE status = 'delivered' AND MONTH(order_date) = MONTH(CURDATE() - INTERVAL 1 MONTH)");
$last_month_sales = $last_month_sales_query->fetch_assoc()['total'] ?? 0;

$total_orders_query = $conn->query("SELECT COUNT(*) as count FROM orders");
$total_orders = $total_orders_query->fetch_assoc()['count'] ?? 0;

$pending_orders_query = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'");
$pending_orders = $pending_orders_query->fetch_assoc()['count'] ?? 0;

$processing_orders_query = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'processing'");
$processing_orders = $processing_orders_query->fetch_assoc()['count'] ?? 0;

$shipped_orders_query = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'shipped'");
$shipped_orders = $shipped_orders_query->fetch_assoc()['count'] ?? 0;

$delivered_orders_query = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'delivered'");
$delivered_orders = $delivered_orders_query->fetch_assoc()['count'] ?? 0;

$cancelled_orders_query = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'cancelled'");
$cancelled_orders = $cancelled_orders_query->fetch_assoc()['count'] ?? 0;

// ============================================
// PRODUCT MANAGEMENT
// ============================================

// ADD PRODUCT
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $desc = $conn->real_escape_string($_POST['desc']);
    $price = $conn->real_escape_string($_POST['price']);
    $icon = $conn->real_escape_string($_POST['icon']) ?: 'fa-mug-hot';
    
    $product_image = NULL;
    
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0 && $_FILES['product_image']['size'] > 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $new_filename = time() . '_' . rand(1000, 9999) . '.' . $ext;
            $upload_path = 'uploads/' . $new_filename;
            
            if (move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_path)) {
                $product_image = $upload_path;
            }
        }
    }
    
    $stmt = $conn->prepare("INSERT INTO products (name, description, price, icon, product_image) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $name, $desc, $price, $icon, $product_image);
    $stmt->execute();
    header("Location: seller.php?tab=products&success=added");
    exit();
}

// UPDATE PRODUCT DETAILS
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_details'])) {
    $product_id = (int)$_POST['product_id'];
    $new_name = $conn->real_escape_string($_POST['new_name']);
    $new_desc = $conn->real_escape_string($_POST['new_desc']);
    $new_price = $conn->real_escape_string($_POST['new_price']);
    
    $stmt = $conn->prepare("UPDATE products SET name = ?, description = ?, price = ? WHERE id = ?");
    $stmt->bind_param("sssi", $new_name, $new_desc, $new_price, $product_id);
    $stmt->execute();
    header("Location: seller.php?tab=products&success=updated");
    exit();
}

// UPDATE PRODUCT PHOTO
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_photo'])) {
    $product_id = (int)$_POST['product_id'];
    
    if (isset($_FILES['new_image']) && $_FILES['new_image']['error'] == 0 && $_FILES['new_image']['size'] > 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['new_image']['name'], PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $result = $conn->query("SELECT product_image FROM products WHERE id = $product_id");
            if ($row = $result->fetch_assoc()) {
                if ($row['product_image'] && file_exists($row['product_image'])) {
                    unlink($row['product_image']);
                }
            }
            
            $new_filename = time() . '_' . rand(1000, 9999) . '.' . $ext;
            $upload_path = 'uploads/' . $new_filename;
            
            if (move_uploaded_file($_FILES['new_image']['tmp_name'], $upload_path)) {
                $stmt = $conn->prepare("UPDATE products SET product_image = ? WHERE id = ?");
                $stmt->bind_param("si", $upload_path, $product_id);
                $stmt->execute();
            }
        }
    }
    header("Location: seller.php?tab=products&success=updated");
    exit();
}

// DELETE PRODUCT
if (isset($_GET['delete_product'])) {
    $id = (int)$_GET['delete_product'];
    $result = $conn->query("SELECT product_image FROM products WHERE id = $id");
    if ($row = $result->fetch_assoc()) {
        if ($row['product_image'] && file_exists($row['product_image'])) {
            unlink($row['product_image']);
        }
    }
    $conn->query("DELETE FROM products WHERE id = $id");
    header("Location: seller.php?tab=products&success=deleted");
    exit();
}

$products = $conn->query("SELECT * FROM products ORDER BY id DESC");
$orders = $conn->query("SELECT * FROM orders ORDER BY order_date DESC");
$username = $_SESSION['username'];
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ISSANG MACCHIATOS - Seller Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: #0a0806;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            padding: 20px;
            min-height: 100vh;
        }
        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('issang lala.jpg');
            background-repeat: no-repeat;
            background-position: center;
            background-size: cover;
            opacity: 0.45;
            pointer-events: none;
            z-index: 0;
        }
        body::after {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(0,0,0,0.65) 0%, rgba(0,0,0,0.5) 100%);
            pointer-events: none;
            z-index: 0;
        }
        .main-container { position: relative; z-index: 10; max-width: 1400px; margin: 0 auto; }
        .dashboard-card {
            background: rgba(30, 20, 15, 0.92);
            backdrop-filter: blur(12px);
            border-radius: 32px;
            border: 1px solid rgba(212, 175, 55, 0.5);
            overflow: hidden;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.6);
        }
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 25px 30px;
            background: rgba(0, 0, 0, 0.4);
            border-bottom: 1px solid rgba(212, 175, 55, 0.3);
            flex-wrap: wrap;
            gap: 15px;
        }
        .welcome-text h2 { color: #f0dfbb; font-size: 1.5rem; font-weight: 600; }
        .welcome-text h2 i { color: #d4af37; margin-right: 10px; }
        .welcome-text p { color: #b89a6e; margin-top: 5px; font-size: 0.85rem; }
        .signout-btn {
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid #c7a155;
            padding: 10px 24px;
            border-radius: 40px;
            color: #f5e5ca;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
        }
        .signout-btn:hover { background: #c7a155; color: #1c1610; }
        .alert-success {
            background: rgba(60, 110, 60, 0.95);
            padding: 12px 20px;
            border-radius: 12px;
            margin: 15px 30px 0 30px;
            color: #c8ffb5;
            text-align: center;
            border-left: 4px solid #d4af37;
        }
        .tab-nav {
            display: flex;
            gap: 5px;
            padding: 0 30px;
            background: rgba(0, 0, 0, 0.3);
            border-bottom: 1px solid rgba(212, 175, 55, 0.2);
        }
        .tab-btn {
            padding: 15px 25px;
            background: transparent;
            border: none;
            color: #b89a6e;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }
        .tab-btn.active { color: #d4af37; border-bottom: 2px solid #d4af37; }
        .tab-btn:hover { color: #d4af37; }
        .tab-content { display: none; padding: 25px 30px; }
        .tab-content.active { display: block; }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: rgba(34, 30, 25, 0.7);
            border-radius: 20px;
            padding: 20px;
            text-align: center;
            border: 1px solid rgba(212, 175, 55, 0.3);
            transition: 0.3s;
        }
        .stat-card:hover { background: rgba(55, 48, 39, 0.9); transform: translateY(-3px); }
        .stat-card i { font-size: 2rem; color: #d4af37; margin-bottom: 10px; display: block; }
        .stat-card .value { font-size: 1.8rem; font-weight: bold; color: #fad48b; }
        .stat-card .label { color: #b89a6e; font-size: 0.8rem; margin-top: 5px; }
        
        .sales-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        .sales-card {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 16px;
            padding: 15px;
            text-align: center;
        }
        .sales-card .amount { font-size: 1.4rem; font-weight: bold; color: #d4af37; }
        .sales-card .period { color: #b89a6e; font-size: 0.7rem; margin-top: 5px; }
        
        /* ORDER TABLE STYLES - MAY BOX ITO */
        .orders-table { 
            width: 100%; 
            border-collapse: collapse; 
            background: rgba(0, 0, 0, 0.3); 
            border-radius: 16px; 
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }
        .orders-table th, .orders-table td { 
            padding: 12px; 
            text-align: left; 
            border-bottom: 1px solid rgba(212, 175, 55, 0.2); 
            color: #d4c5a9; 
        }
        .orders-table th { 
            background: rgba(212, 175, 55, 0.15); 
            color: #d4af37; 
            font-weight: 600; 
        }
        .status-badge { 
            padding: 5px 12px; 
            border-radius: 20px; 
            font-size: 0.7rem; 
            font-weight: bold; 
            display: inline-block; 
        }
        .status-pending { background: #ff9800; color: #1c1610; }
        .status-processing { background: #2196f3; color: white; }
        .status-shipped { background: #9c27b0; color: white; }
        .status-delivered { background: #4caf50; color: white; }
        .status-cancelled { background: #f44336; color: white; }
        
        /* BUTTON STYLES - SHIP AT DELIVER BUTTONS */
        .action-btn { 
            padding: 8px 16px; 
            border-radius: 25px; 
            border: none; 
            cursor: pointer; 
            font-size: 0.75rem; 
            margin: 3px; 
            text-decoration: none; 
            display: inline-block;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .process-btn { background: #ff9800; color: #1c1610; }
        .process-btn:hover { background: #e68a00; transform: scale(1.02); }
        
        .ship-btn { background: #2196f3; color: white; }
        .ship-btn:hover { background: #0b7dda; transform: scale(1.02); }
        
        .deliver-btn { background: #4caf50; color: white; }
        .deliver-btn:hover { background: #3e8e41; transform: scale(1.02); }
        
        .cancel-btn { background: #f44336; color: white; }
        .cancel-btn:hover { background: #da190b; transform: scale(1.02); }
        
        .partner-select { padding: 6px 10px; border-radius: 20px; background: rgba(20,12,8,0.9); border: 1px solid #6b4c3a; color: #f0e2c5; }
        .shipping-form { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
        
        .product-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(380px, 1fr)); gap: 20px; margin-top: 15px; }
        .product-card { background: rgba(34, 30, 25, 0.8); border-radius: 20px; padding: 18px; border: 1px solid rgba(212, 175, 55, 0.25); transition: 0.3s; }
        .product-card:hover { background: rgba(55, 48, 39, 0.9); border-color: #d4af37; }
        .product-image-area { display: flex; justify-content: center; margin-bottom: 15px; }
        .product-image-area img { width: 130px; height: 130px; object-fit: cover; border-radius: 65px; border: 3px solid #d4af37; background: #1e1b17; }
        .product-image-area i { font-size: 5rem; color: #d4af37; }
        .product-info { text-align: center; margin-bottom: 15px; }
        .product-info strong { color: #fbecca; font-size: 1.2rem; display: block; }
        .product-info .price { color: #fad48b; font-weight: bold; }
        .product-info small { color: #b89a6e; font-size: 0.75rem; }
        .edit-forms { border-top: 1px solid rgba(212, 175, 55, 0.2); padding-top: 12px; }
        .edit-forms form { margin-bottom: 10px; }
        .edit-input-group { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 8px; }
        .edit-input-group input { flex: 1; padding: 8px 12px; border-radius: 30px; border: 1px solid #6b4c3a; background: rgba(20,12,8,0.9); color: #f0e2c5; font-size: 0.8rem; }
        .edit-input-group input[type="file"] { padding: 6px 10px; }
        .edit-btn { background: #6a5a3e; border: none; padding: 8px 18px; border-radius: 30px; color: white; cursor: pointer; transition: 0.3s; }
        .edit-btn:hover { background: #8a7a5e; }
        .photo-btn { background: #4a6a3e; }
        .delete-btn { background: #8b472e; border: none; padding: 8px 18px; border-radius: 30px; color: white; cursor: pointer; width: 100%; margin-top: 5px; text-align: center; display: block; text-decoration: none; }
        .form-row { display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 20px; }
        .form-row input { flex: 1; min-width: 150px; background: rgba(20,12,8,0.9); border: 1px solid #6b4c3a; border-radius: 40px; padding: 12px 18px; color: #f0e2c5; outline: none; }
        .form-row button { background: linear-gradient(135deg, #d4af37, #b8922a); border: none; padding: 12px 28px; border-radius: 40px; font-weight: bold; cursor: pointer; }
        hr { border-color: rgba(212, 175, 55, 0.3); margin: 20px 0; }
        .footer { text-align: center; padding: 20px 30px; border-top: 1px solid rgba(212, 175, 55, 0.2); color: #9e885c; font-size: 0.8rem; }
        .footer i { color: #d4af37; }
        
        /* Action buttons container */
        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }
        
        @media (max-width: 900px) {
            .orders-table { font-size: 0.7rem; }
            .orders-table th, .orders-table td { padding: 8px; }
            .product-list { grid-template-columns: 1fr; }
            .tab-btn { padding: 10px 15px; font-size: 0.8rem; }
            .dashboard-header { flex-direction: column; text-align: center; }
            .form-row { flex-direction: column; }
            .edit-input-group { flex-direction: column; }
            .shipping-form { flex-direction: column; align-items: stretch; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
    <script>
        function showTab(tabName) {
            document.getElementById('dashboardContent').classList.remove('active');
            document.getElementById('ordersContent').classList.remove('active');
            document.getElementById('productsContent').classList.remove('active');
            
            let btns = document.querySelectorAll('.tab-btn');
            btns.forEach(btn => btn.classList.remove('active'));
            
            if (tabName === 'dashboard') {
                document.getElementById('dashboardContent').classList.add('active');
                btns[0].classList.add('active');
            } else if (tabName === 'orders') {
                document.getElementById('ordersContent').classList.add('active');
                btns[1].classList.add('active');
            } else if (tabName === 'products') {
                document.getElementById('productsContent').classList.add('active');
                btns[2].classList.add('active');
            }
        }
    </script>
</head>
<body>
<div class="main-container">
    <div class="dashboard-card">
        <div class="dashboard-header">
            <div class="welcome-text">
                <h2><i class="fas fa-store"></i> Seller: <?php echo htmlspecialchars($username); ?></h2>
                <p><i class="fas fa-mug-hot"></i> Manage your ISSANG MACCHIATOS - Orders, Products & Sales</p>
            </div>
            <a href="signout.php" class="signout-btn"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert-success">✓ <?php echo ucfirst($_GET['success']); ?> successfully!</div>
        <?php endif; ?>

        <div class="tab-nav">
            <button class="tab-btn <?php echo $active_tab == 'dashboard' ? 'active' : ''; ?>" onclick="showTab('dashboard')">
                <i class="fas fa-chart-line"></i> Dashboard
            </button>
            <button class="tab-btn <?php echo $active_tab == 'orders' ? 'active' : ''; ?>" onclick="showTab('orders')">
                <i class="fas fa-shopping-cart"></i> Orders (<?php echo $total_orders; ?>)
            </button>
            <button class="tab-btn <?php echo $active_tab == 'products' ? 'active' : ''; ?>" onclick="showTab('products')">
                <i class="fas fa-mug-hot"></i> Products (<?php echo $products->num_rows; ?>)
            </button>
        </div>

        <!-- DASHBOARD TAB -->
        <div id="dashboardContent" class="tab-content <?php echo $active_tab == 'dashboard' ? 'active' : ''; ?>">
            <div class="stats-grid">
                <div class="stat-card">
                    <i class="fas fa-chart-simple"></i>
                    <div class="value">₱<?php echo number_format($total_sales, 2); ?></div>
                    <div class="label">Total Sales</div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-truck"></i>
                    <div class="value"><?php echo $delivered_orders; ?></div>
                    <div class="label">Completed Orders</div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-clock"></i>
                    <div class="value"><?php echo $pending_orders + $processing_orders + $shipped_orders; ?></div>
                    <div class="label">Pending Orders</div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-users"></i>
                    <div class="value"><?php echo $total_orders; ?></div>
                    <div class="label">Total Orders</div>
                </div>
            </div>

            <h4 style="color:#d4af37; margin-bottom:15px;"><i class="fas fa-chart-line"></i> Sales Summary</h4>
            <div class="sales-summary">
                <div class="sales-card"><div class="amount">₱<?php echo number_format($today_sales, 2); ?></div><div class="period">Today's Sales</div></div>
                <div class="sales-card"><div class="amount">₱<?php echo number_format($this_week_sales, 2); ?></div><div class="period">This Week</div></div>
                <div class="sales-card"><div class="amount">₱<?php echo number_format($this_month_sales, 2); ?></div><div class="period">This Month</div></div>
                <div class="sales-card"><div class="amount">₱<?php echo number_format($last_month_sales, 2); ?></div><div class="period">Last Month</div></div>
            </div>

            <h4 style="color:#d4af37; margin-bottom:15px;"><i class="fas fa-clock"></i> Recent Orders</h4>
            <table class="orders-table">
                <thead><tr><th>Order ID</th><th>Customer</th><th>Total</th><th>Date</th><th>Status</th></tr></thead>
                <tbody>
                    <?php $recent_orders = $conn->query("SELECT * FROM orders ORDER BY order_date DESC LIMIT 5");
                    while ($order = $recent_orders->fetch_assoc()): ?>
                    <tr>
                        <td><small><?php echo $order['order_number']; ?></small></td>
                        <td><?php echo $order['username']; ?></td>
                        <td><?php echo $order['total_amount']; ?></td>
                        <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                        <td><span class="status-badge status-<?php echo $order['status']; ?>"><?php echo ucfirst($order['status']); ?></span></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- ORDERS TAB - DITO MAY SHIP AT MARK AS DELIVERED BUTTONS -->
        <div id="ordersContent" class="tab-content <?php echo $active_tab == 'orders' ? 'active' : ''; ?>">
            <h4 style="color:#d4af37; margin-bottom:15px;"><i class="fas fa-truck"></i> All Orders</h4>
            <div style="overflow-x: auto;">
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Shipping Partner</th>
                            <th>Tracking #</th>
                            <th>Order Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($orders && $orders->num_rows > 0): ?>
                            <?php while ($order = $orders->fetch_assoc()): 
                                $items_query = $conn->query("SELECT * FROM order_items WHERE order_id = " . $order['id']);
                                $items_list = [];
                                while ($item = $items_query->fetch_assoc()) {
                                    $items_list[] = $item['product_name'] . " (x" . $item['quantity'] . ")";
                                }
                                $items_str = implode(", ", $items_list);
                            ?>
                            <tr>
                                <td><strong>#<?php echo $order['id']; ?></strong><br><small><?php echo $order['order_number']; ?></small></td>
                                <td><?php echo htmlspecialchars($order['username']); ?></td>
                                <td><small><?php echo htmlspecialchars($items_str); ?></small></td>
                                <td><?php echo htmlspecialchars($order['total_amount']); ?></td>
                                <td>
                                    <form method="POST" class="shipping-form">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <select name="shipping_partner" class="partner-select">
                                            <option value="">Select</option>
                                            <option value="Foodpanda" <?php echo $order['shipping_partner'] == 'Foodpanda' ? 'selected' : ''; ?>>Foodpanda</option>
                                            <option value="Grab" <?php echo $order['shipping_partner'] == 'Grab' ? 'selected' : ''; ?>>Grab</option>
                                            <option value="Lalamove" <?php echo $order['shipping_partner'] == 'Lalamove' ? 'selected' : ''; ?>>Lalamove</option>
                                            <option value="Own Delivery" <?php echo $order['shipping_partner'] == 'Own Delivery' ? 'selected' : ''; ?>>Own Delivery</option>
                                        </select>
                                        <button type="submit" name="update_shipping" class="action-btn" style="background:#6a5a3e;">Set</button>
                                    </form>
                                </td>
                                <td><?php echo $order['tracking_number'] ? '<code>' . $order['tracking_number'] . '</code>' : '-'; ?></td>
                                <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                <td><span class="status-badge status-<?php echo $order['status']; ?>"><?php echo ucfirst($order['status']); ?></span></td>
                                <td class="action-buttons">
                                    <?php if ($order['status'] == 'pending'): ?>
                                        <a href="?process=<?php echo $order['id']; ?>&tab=orders" class="action-btn process-btn" onclick="return confirm('Process this order?')">
                                            <i class="fas fa-cog"></i> Process
                                        </a>
                                        <a href="?cancel=<?php echo $order['id']; ?>&tab=orders" class="action-btn cancel-btn" onclick="return confirm('Cancel this order?')">
                                            <i class="fas fa-times"></i> Cancel
                                        </a>
                                        
                                    <?php elseif ($order['status'] == 'processing'): ?>
                                        <!-- SHIP BUTTON - LALABAS ITO KAPAG PROCESSING -->
                                        <a href="?ship=<?php echo $order['id']; ?>&tab=orders" class="action-btn ship-btn" onclick="return confirm('Ship this order?')">
                                            <i class="fas fa-shipping-fast"></i> Ship
                                        </a>
                                        
                                    <?php elseif ($order['status'] == 'shipped'): ?>
                                        <!-- MARK AS DELIVERED BUTTON - LALABAS ITO KAPAG SHIPPED -->
                                        <a href="?deliver=<?php echo $order['id']; ?>&tab=orders" class="action-btn deliver-btn" onclick="return confirm('Mark this order as delivered?')">
                                            <i class="fas fa-check-circle"></i> Mark as Delivered
                                        </a>
                                        
                                    <?php elseif ($order['status'] == 'delivered'): ?>
                                        <span style="color:#4caf50;"><i class="fas fa-check-circle"></i> Completed</span>
                                        
                                    <?php elseif ($order['status'] == 'cancelled'): ?>
                                        <span style="color:#f44336;"><i class="fas fa-times-circle"></i> Cancelled</span>
                                        
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="9" style="text-align:center; padding:40px;">No orders yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- PRODUCTS TAB -->
        <div id="productsContent" class="tab-content <?php echo $active_tab == 'products' ? 'active' : ''; ?>">
            <h4 style="color:#d4af37; margin-bottom:15px;"><i class="fas fa-plus-circle"></i> Add New Drink</h4>
            <form method="POST" enctype="multipart/form-data" class="form-row">
                <input type="text" name="name" placeholder="Drink name" required>
                <input type="text" name="desc" placeholder="Description">
                <input type="text" name="price" placeholder="Price" value="₱49" required>
                <input type="text" name="icon" placeholder="Icon" value="fa-mug-hot">
                <input type="file" name="product_image" accept="image/*">
                <button type="submit" name="add"><i class="fas fa-save"></i> Add Product</button>
            </form>
            <hr>
            <h4 style="color:#d4af37; margin-bottom:15px;"><i class="fas fa-list"></i> Current Menu (<?php echo $products->num_rows; ?> items)</h4>
            <div class="product-list">
                <?php while ($row = $products->fetch_assoc()): ?>
                <div class="product-card">
                    <div class="product-image-area">
                        <?php if ($row['product_image'] && file_exists($row['product_image'])): ?>
                            <img src="<?php echo $row['product_image']; ?>?v=<?php echo time(); ?>" alt="<?php echo $row['name']; ?>">
                        <?php else: ?>
                            <i class="fas <?php echo $row['icon']; ?>"></i>
                        <?php endif; ?>
                    </div>
                    <div class="product-info">
                        <strong><?php echo htmlspecialchars($row['name']); ?></strong>
                        <span class="price"><?php echo htmlspecialchars($row['price']); ?></span>
                        <small><?php echo htmlspecialchars($row['description']); ?></small>
                    </div>
                    <div class="edit-forms">
                        <form method="POST">
                            <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
                            <div class="edit-input-group">
                                <input type="text" name="new_name" value="<?php echo htmlspecialchars($row['name']); ?>" placeholder="Name" required>
                                <input type="text" name="new_desc" value="<?php echo htmlspecialchars($row['description']); ?>" placeholder="Description">
                                <input type="text" name="new_price" value="<?php echo htmlspecialchars($row['price']); ?>" placeholder="Price">
                                <button type="submit" name="update_details" class="edit-btn"><i class="fas fa-edit"></i> Update</button>
                            </div>
                        </form>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
                            <div class="edit-input-group">
                                <input type="file" name="new_image" accept="image/*">
                                <button type="submit" name="update_photo" class="edit-btn photo-btn"><i class="fas fa-image"></i> Change Photo</button>
                            </div>
                        </form>
                        <a href="?delete_product=<?php echo $row['id']; ?>&tab=products" class="delete-btn" onclick="return confirm('Delete this item?')">
                            <i class="fas fa-trash"></i> Remove Product
                        </a>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>

        <div class="footer">
            <i class="fas fa-heart"></i> ISSANG MACCHIATOS — Experience the Finest Drinks in Town 
            <i class="fas fa-mug-hot"></i>
        </div>
    </div>
</div>
</body>
</html>