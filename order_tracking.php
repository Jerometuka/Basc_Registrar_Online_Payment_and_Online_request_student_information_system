<?php
// order_tracking.php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'user') {
    header("Location: signin.php");
    exit();
}
include 'dbcon.php';

$username = $_SESSION['username'];
$order_number = isset($_GET['order']) ? $_GET['order'] : '';

// order details
$order_query = $conn->query("SELECT * FROM orders WHERE order_number = '$order_number' AND username = '$username'");
$order = $order_query->fetch_assoc();

if (!$order) {
    // see all orders
    $orders = $conn->query("SELECT * FROM orders WHERE username = '$username' ORDER BY order_date DESC");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ISSANG MACCHIATOS - Track My Orders</title>
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
            background: rgba(0,0,0,0.6);
            pointer-events: none;
            z-index: 0;
        }
        .container { position: relative; z-index: 10; max-width: 1200px; margin: 0 auto; }
        .track-card {
            background: rgba(30,20,15,0.92);
            backdrop-filter: blur(12px);
            border-radius: 32px;
            border: 1px solid #d4af37;
            overflow: hidden;
            margin-bottom: 20px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 25px;
            background: rgba(0,0,0,0.4);
            border-bottom: 1px solid #d4af37;
            flex-wrap: wrap;
            gap: 10px;
        }
        .header h2 { color: #f0dfbb; }
        .header h2 i { color: #d4af37; margin-right: 10px; }
        .back-btn, .signout-btn {
            background: #6a5a3e;
            padding: 8px 18px;
            border-radius: 40px;
            color: white;
            text-decoration: none;
        }
        .signout-btn { background: rgba(0,0,0,0.5); border: 1px solid #c7a155; margin-left: 8px; }
        .body-content { padding: 25px; }
        
        /* Order List Styles */
        .orders-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }
        .order-item {
            background: rgba(34,30,25,0.7);
            border-radius: 20px;
            padding: 18px;
            border: 1px solid rgba(212,175,55,0.3);
            transition: 0.3s;
            cursor: pointer;
        }
        .order-item:hover {
            background: rgba(55,48,39,0.9);
            border-color: #d4af37;
            transform: translateY(-3px);
        }
        .order-number { color: #fad48b; font-weight: bold; font-size: 1rem; }
        .order-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: bold;
            margin-top: 8px;
        }
        .status-pending { background: #ff9800; color: #1c1610; }
        .status-processing { background: #2196f3; color: white; }
        .status-shipped { background: #9c27b0; color: white; }
        .status-delivered { background: #4caf50; color: white; }
        .status-cancelled { background: #f44336; color: white; }
        .order-date { color: #b89a6e; font-size: 0.7rem; margin-top: 8px; }
        .order-total { color: #d4af37; font-weight: bold; margin-top: 10px; }
        
        /* Single Order Details */
        .order-detail-section {
            background: rgba(34,30,25,0.5);
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .order-detail-section h3 {
            color: #fbecca;
            border-bottom: 1px solid #d4af37;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .info-row {
            display: flex;
            padding: 8px 0;
            border-bottom: 1px solid rgba(212,175,55,0.1);
        }
        .info-label { width: 150px; color: #b89a6e; font-weight: bold; }
        .info-value { flex: 1; color: #d9c189; }
        .timeline {
            display: flex;
            justify-content: space-between;
            margin: 30px 0;
            position: relative;
        }
        .timeline::before {
            content: "";
            position: absolute;
            top: 30px;
            left: 0;
            width: 100%;
            height: 3px;
            background: #3a3228;
            z-index: 1;
        }
        .timeline-step {
            text-align: center;
            flex: 1;
            position: relative;
            z-index: 2;
        }
        .step-icon {
            width: 60px;
            height: 60px;
            background: #3a3228;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            border: 2px solid #6b4c3a;
        }
        .timeline-step.active .step-icon {
            background: #d4af37;
            border-color: #d4af37;
        }
        .timeline-step.active .step-icon i { color: #1c1610; }
        .step-icon i { font-size: 1.5rem; color: #b89a6e; }
        .step-label { color: #b89a6e; font-size: 0.8rem; }
        .timeline-step.active .step-label { color: #d4af37; font-weight: bold; }
        .step-date { font-size: 0.7rem; color: #8a6e4a; margin-top: 5px; }
        .items-list { margin-top: 10px; }
        .item-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid rgba(212,175,55,0.1);
            color: #d9c189;
        }
        .btn-back {
            background: #6a5a3e;
            padding: 10px 20px;
            border-radius: 40px;
            color: white;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 20px;
        }
        @media (max-width: 700px) {
            .body-content { padding: 15px; }
            .header { flex-direction: column; text-align: center; }
            .info-row { flex-direction: column; }
            .info-label { width: 100%; margin-bottom: 5px; }
            .timeline { flex-direction: column; gap: 20px; }
            .timeline::before { display: none; }
            .orders-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="container">
    <?php if (isset($order) && $order): ?>
        <!-- Single Order View -->
        <div class="track-card">
            <div class="header">
                <h2><i class="fas fa-truck"></i> Order #<?php echo htmlspecialchars($order['order_number']); ?></h2>
                <div>
                    <a href="order_tracking.php" class="back-btn"><i class="fas fa-arrow-left"></i> All Orders</a>
                    <a href="user.php" class="back-btn" style="margin-left:8px;"><i class="fas fa-home"></i> Home</a>
                    <a href="signout.php" class="signout-btn"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
                </div>
            </div>
            <div class="body-content">
                <!-- Order Status Timeline -->
                <div class="order-detail-section">
                    <h3><i class="fas fa-chart-line"></i> Order Status Timeline</h3>
                    <div class="timeline">
                        <div class="timeline-step <?php echo in_array($order['status'], ['pending', 'processing', 'shipped', 'delivered']) ? 'active' : ''; ?>">
                            <div class="step-icon"><i class="fas fa-receipt"></i></div>
                            <div class="step-label">Order Placed</div>
                            <div class="step-date"><?php echo date('M d, Y', strtotime($order['order_date'])); ?></div>
                        </div>
                        <div class="timeline-step <?php echo in_array($order['status'], ['processing', 'shipped', 'delivered']) ? 'active' : ''; ?>">
                            <div class="step-icon"><i class="fas fa-cogs"></i></div>
                            <div class="step-label">Processing</div>
                            <div class="step-date"><?php echo $order['processed_date'] ? date('M d, Y', strtotime($order['processed_date'])) : 'Pending'; ?></div>
                        </div>
                        <div class="timeline-step <?php echo in_array($order['status'], ['shipped', 'delivered']) ? 'active' : ''; ?>">
                            <div class="step-icon"><i class="fas fa-shipping-fast"></i></div>
                            <div class="step-label">Shipped</div>
                            <div class="step-date"><?php echo $order['shipped_date'] ? date('M d, Y', strtotime($order['shipped_date'])) : 'Pending'; ?></div>
                        </div>
                        <div class="timeline-step <?php echo $order['status'] == 'delivered' ? 'active' : ''; ?>">
                            <div class="step-icon"><i class="fas fa-check-circle"></i></div>
                            <div class="step-label">Delivered</div>
                            <div class="step-date"><?php echo $order['delivered_date'] ? date('M d, Y', strtotime($order['delivered_date'])) : 'Pending'; ?></div>
                        </div>
                    </div>
                </div>

                <!-- Order Information -->
                <div class="order-detail-section">
                    <h3><i class="fas fa-info-circle"></i> Order Information</h3>
                    <div class="info-row">
                        <div class="info-label">Order Number:</div>
                        <div class="info-value"><?php echo htmlspecialchars($order['order_number']); ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Order Date:</div>
                        <div class="info-value"><?php echo date('F d, Y h:i A', strtotime($order['order_date'])); ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Order Status:</div>
                        <div class="info-value">
                            <span class="order-status status-<?php echo $order['status']; ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Total Amount:</div>
                        <div class="info-value"><?php echo htmlspecialchars($order['total_amount']); ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Delivery Address:</div>
                        <div class="info-value"><?php echo nl2br(htmlspecialchars($order['delivery_address'])); ?></div>
                    </div>
                </div>

                <!-- Seller & Shipping Information -->
                <div class="order-detail-section">
                    <h3><i class="fas fa-store"></i> Seller & Shipping Information</h3>
                    <div class="info-row">
                        <div class="info-label">Seller Name:</div>
                        <div class="info-value"><?php echo htmlspecialchars($order['seller_name'] ?? 'ISSANG MACCHIATOS'); ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Shipping Partner:</div>
                        <div class="info-value">
                            <?php if ($order['shipping_partner']): ?>
                                <i class="fas fa-truck"></i> <?php echo htmlspecialchars($order['shipping_partner']); ?>
                                <?php if ($order['tracking_number']): ?>
                                    <br><small>Tracking #: <?php echo $order['tracking_number']; ?></small>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="color:#b89a6e;">Waiting for seller to assign partner</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($order['shipped_date']): ?>
                    <div class="info-row">
                        <div class="info-label">Shipped Date:</div>
                        <div class="info-value"><?php echo date('F d, Y h:i A', strtotime($order['shipped_date'])); ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if ($order['delivered_date']): ?>
                    <div class="info-row">
                        <div class="info-label">Delivered Date:</div>
                        <div class="info-value"><?php echo date('F d, Y h:i A', strtotime($order['delivered_date'])); ?></div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Order Items -->
                <div class="order-detail-section">
                    <h3><i class="fas fa-shopping-bag"></i> Order Items</h3>
                    <div class="items-list">
                        <?php
                        $items = $conn->query("SELECT * FROM order_items WHERE order_id = " . $order['id']);
                        while ($item = $items->fetch_assoc()):
                        ?>
                        <div class="item-row">
                            <span><?php echo htmlspecialchars($item['product_name']); ?> (x<?php echo $item['quantity']; ?>)</span>
                            <span><?php echo htmlspecialchars($item['subtotal']); ?></span>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- All Orders View -->
        <div class="track-card">
            <div class="header">
                <h2><i class="fas fa-list"></i> My Orders</h2>
                <div>
                    <a href="user.php" class="back-btn"><i class="fas fa-home"></i> Home</a>
                    <a href="signout.php" class="signout-btn"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
                </div>
            </div>
            <div class="body-content">
                <?php if (isset($orders) && $orders->num_rows > 0): ?>
                    <div class="orders-grid">
                        <?php while ($ord = $orders->fetch_assoc()): ?>
                            <a href="order_tracking.php?order=<?php echo $ord['order_number']; ?>" style="text-decoration: none;">
                                <div class="order-item">
                                    <div class="order-number">#<?php echo htmlspecialchars($ord['order_number']); ?></div>
                                    <div>
                                        <span class="order-status status-<?php echo $ord['status']; ?>">
                                            <?php echo ucfirst($ord['status']); ?>
                                        </span>
                                    </div>
                                    <div class="order-date">
                                        <i class="fas fa-calendar"></i> <?php echo date('M d, Y h:i A', strtotime($ord['order_date'])); ?>
                                    </div>
                                    <div class="order-total">Total: <?php echo htmlspecialchars($ord['total_amount']); ?></div>
                                    <?php if ($ord['shipping_partner']): ?>
                                        <div style="font-size:0.7rem; color:#8bc34a; margin-top:5px;">
                                            <i class="fas fa-truck"></i> <?php echo $ord['shipping_partner']; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div style="text-align:center; padding:60px; color:#bda16e;">
                        <i class="fas fa-shopping-bag" style="font-size:4rem; margin-bottom:15px; display:block;"></i>
                        <p>You haven't placed any orders yet.</p>
                        <a href="user.php" style="display:inline-block; margin-top:20px; background:#d4af37; padding:10px 30px; border-radius:40px; color:#1c1610; text-decoration:none;">Start Shopping</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
</body>
</html>