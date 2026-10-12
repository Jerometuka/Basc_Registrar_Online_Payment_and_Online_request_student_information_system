<?php
// orders.php 
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'user') {
    header("Location: signin.php");
    exit();
}
include 'dbcon.php';

$username = $_SESSION['username'];

// Use prepared statement to prevent SQL injection
$stmt = $conn->prepare("SELECT * FROM orders WHERE username = ? ORDER BY order_date DESC");
$stmt->bind_param("s", $username);
$stmt->execute();
$orders = $stmt->get_result();

// Check for query error
if (!$orders) {
    $error = "Error loading orders: " . $conn->error;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ISSANG MACCHIATOS - My Orders</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #0a0806;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            padding: 20px;
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
            background: rgba(0, 0, 0, 0.6);
            pointer-events: none;
            z-index: 0;
        }

        .main-container {
            position: relative;
            z-index: 10;
            max-width: 1000px;
            margin: 0 auto;
        }

        .orders-card {
            background: rgba(30, 20, 15, 0.92);
            backdrop-filter: blur(12px);
            border-radius: 32px;
            border: 1px solid rgba(212, 175, 55, 0.5);
            overflow: hidden;
        }

        .orders-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 25px 30px;
            background: rgba(0, 0, 0, 0.4);
            border-bottom: 1px solid rgba(212, 175, 55, 0.3);
            flex-wrap: wrap;
            gap: 15px;
        }

        .orders-header h2 {
            color: #f0dfbb;
            font-size: 1.5rem;
        }

        .orders-header h2 i {
            color: #d4af37;
            margin-right: 10px;
        }

        .back-btn, .signout-btn {
            background: #6a5a3e;
            padding: 10px 20px;
            border-radius: 40px;
            color: white;
            text-decoration: none;
            transition: 0.3s;
        }

        .back-btn:hover, .signout-btn:hover {
            background: #8a7a5e;
        }

        .signout-btn {
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid #c7a155;
            margin-left: 10px;
        }

        .signout-btn:hover {
            background: #c7a155;
            color: #1c1610;
        }

        .orders-body {
            padding: 30px;
        }

        .order-card {
            background: rgba(34, 30, 25, 0.5);
            border-radius: 20px;
            margin-bottom: 20px;
            overflow: hidden;
            transition: 0.3s;
        }

        .order-card:hover {
            background: rgba(55, 48, 39, 0.7);
            transform: translateY(-2px);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background: rgba(0, 0, 0, 0.3);
            flex-wrap: wrap;
            gap: 10px;
        }

        .order-number {
            color: #fad48b;
            font-weight: bold;
        }

        .order-status {
            padding: 5px 12px;
            border-radius: 30px;
            font-size: 0.75rem;
            font-weight: bold;
        }

        .status-pending { background: #ff9800; color: #1c1610; }
        .status-processing { background: #2196f3; color: white; }
        .status-shipped { background: #9c27b0; color: white; }
        .status-delivered { background: #4caf50; color: white; }
        .status-cancelled { background: #f44336; color: white; }
        .status-completed { background: #4caf50; color: white; }

        .order-date {
            color: #b89a6e;
            font-size: 0.75rem;
        }

        .order-items {
            padding: 15px 20px;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            color: #d9c189;
            border-bottom: 1px solid rgba(212, 175, 55, 0.1);
        }

        .order-total {
            display: flex;
            justify-content: space-between;
            padding: 12px 20px;
            background: rgba(0, 0, 0, 0.2);
            font-weight: bold;
        }

        .order-total span:last-child {
            color: #d4af37;
            font-size: 1.1rem;
        }

        .order-details-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 20px;
            font-size: 0.75rem;
            color: #b89a6e;
            flex-wrap: wrap;
            gap: 10px;
            border-top: 1px solid rgba(212, 175, 55, 0.1);
        }

        .payment-method {
            color: #8bc34a;
        }

        .delivery-address {
            color: #ffb74d;
        }

        .track-btn {
            display: inline-block;
            margin-top: 10px;
            padding: 6px 15px;
            background: #6a5a3e;
            border-radius: 30px;
            color: white;
            text-decoration: none;
            font-size: 0.75rem;
            transition: 0.3s;
        }

        .track-btn:hover {
            background: #8a7a5e;
        }

        .shipping-info {
            font-size: 0.7rem;
            color: #8bc34a;
            margin-top: 8px;
        }

        .empty-orders {
            text-align: center;
            padding: 60px;
            color: #bda16e;
        }

        .error-message {
            background: #8b3c2e;
            padding: 15px;
            border-radius: 12px;
            color: #ffcdb5;
            margin-bottom: 20px;
            text-align: center;
        }

        .footer {
            text-align: center;
            padding: 20px;
            border-top: 1px solid rgba(212, 175, 55, 0.2);
            color: #9e885c;
            font-size: 0.75rem;
        }

        @media (max-width: 700px) {
            .orders-body {
                padding: 20px;
            }
            .order-header {
                flex-direction: column;
                text-align: center;
            }
            .orders-header {
                flex-direction: column;
                text-align: center;
            }
            .order-item {
                flex-direction: column;
                text-align: center;
                gap: 5px;
            }
            .order-total {
                flex-direction: column;
                text-align: center;
                gap: 8px;
            }
            .order-details-row {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>
<div class="main-container">
    <div class="orders-card">
        <div class="orders-header">
            <h2><i class="fas fa-shopping-bag"></i> My Orders</h2>
            <div>
                <a href="user.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Menu</a>
                <a href="signout.php" class="signout-btn"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
            </div>
        </div>

        <div class="orders-body">
            <?php if (isset($error)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                </div>
            <?php elseif ($orders && $orders->num_rows > 0): ?>
                <?php while ($order = $orders->fetch_assoc()): 
                    // Use prepared statement for order items
                    $stmt_items = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
                    $stmt_items->bind_param("i", $order['id']);
                    $stmt_items->execute();
                    $order_items = $stmt_items->get_result();
                    
                    // Get status class (handle lowercase)
                    $status_class = strtolower($order['status']);
                ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div>
                                <span class="order-number"><i class="fas fa-hashtag"></i> <?php echo htmlspecialchars($order['order_number']); ?></span>
                            </div>
                            <div>
                                <span class="order-status status-<?php echo $status_class; ?>">
                                    <i class="fas fa-circle" style="font-size: 0.5rem; vertical-align: middle;"></i>
                                    <?php echo ucfirst(htmlspecialchars($order['status'])); ?>
                                </span>
                            </div>
                            <div class="order-date">
                                <i class="fas fa-calendar"></i> <?php echo date('M d, Y h:i A', strtotime($order['order_date'])); ?>
                            </div>
                        </div>
                        
                        <div class="order-items">
                            <?php if ($order_items && $order_items->num_rows > 0): ?>
                                <?php while ($item = $order_items->fetch_assoc()): ?>
                                    <div class="order-item">
                                        <span><?php echo htmlspecialchars($item['product_name']); ?> (x<?php echo $item['quantity']; ?>)</span>
                                        <span><?php echo htmlspecialchars($item['subtotal']); ?></span>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="order-item" style="color: #ff9800;">
                                    <span>No items found for this order</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="order-total">
                            <span>Total:</span>
                            <span><?php echo htmlspecialchars($order['total_amount']); ?></span>
                        </div>
                        
                        <!-- Display payment method and delivery address -->
                        <div class="order-details-row">
                            <span class="payment-method">
                                <i class="fas fa-credit-card"></i> Payment: <?php echo htmlspecialchars($order['payment_method'] ?? 'Not specified'); ?>
                            </span>
                            <span class="delivery-address">
                                <i class="fas fa-map-marker-alt"></i> Delivery: <?php echo htmlspecialchars($order['delivery_address'] ?? 'Not specified'); ?>
                            </span>
                        </div>
                        
                        <?php if (!empty($order['shipping_partner'])): ?>
                            <div style="padding: 0 20px 15px;">
                                <div class="shipping-info">
                                    <i class="fas fa-truck"></i> Shipping Partner: <?php echo htmlspecialchars($order['shipping_partner']); ?>
                                    <?php if (!empty($order['tracking_number'])): ?>
                                        <br><i class="fas fa-barcode"></i> Tracking #: <?php echo htmlspecialchars($order['tracking_number']); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div style="padding: 0 20px 20px;">
                            <a href="order_tracking.php?order=<?php echo urlencode($order['order_number']); ?>" class="track-btn">
                                <i class="fas fa-map-marker-alt"></i> Track Order
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-orders">
                    <i class="fas fa-shopping-bag" style="font-size: 4rem; margin-bottom: 15px; display: block;"></i>
                    <p>You haven't placed any orders yet.</p>
                    <a href="user.php" style="display: inline-block; margin-top: 20px; background: #d4af37; padding: 10px 30px; border-radius: 40px; color: #1c1610; text-decoration: none;">
                        <i class="fas fa-coffee"></i> Start Shopping
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <div class="footer">
            <i class="fas fa-heart"></i> ISSANG MACCHIATOS — Thank you for your orders!
        </div>
    </div>
</div>
</body>
</html>