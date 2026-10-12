<?php
// order_tracking.php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'user') {
    header("Location: signin.php");
    exit();
}
include 'dbcon.php';

$order_number = isset($_GET['order']) ? $_GET['order'] : '';
$username = $_SESSION['username'];

$order = $conn->query("SELECT * FROM orders WHERE order_number = '$order_number' AND username = '$username'")->fetch_assoc();

if (!$order) {
    header("Location: user_orders.php");
    exit();
}

$order_items = $conn->query("SELECT * FROM order_items WHERE order_id = " . $order['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Order - ISSANG MACCHIATOS</title>
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
        .container { position: relative; z-index: 10; max-width: 800px; margin: 0 auto; }
        .card {
            background: rgba(30, 20, 15, 0.92);
            backdrop-filter: blur(12px);
            border-radius: 32px;
            border: 1px solid rgba(212, 175, 55, 0.5);
            overflow: hidden;
            padding: 30px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(212, 175, 55, 0.3);
        }
        h2 { color: #f0dfbb; }
        .status-timeline {
            display: flex;
            justify-content: space-between;
            margin: 30px 0;
            position: relative;
        }
        .status-timeline::before {
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
        .order-details { background: rgba(0,0,0,0.3); border-radius: 20px; padding: 20px; margin: 20px 0; }
        .btn-back {
            background: #6a5a3e;
            padding: 10px 20px;
            border-radius: 40px;
            color: white;
            text-decoration: none;
        }
        @media (max-width: 600px) {
            .step-icon { width: 40px; height: 40px; }
            .step-icon i { font-size: 1rem; }
            .step-label { font-size: 0.6rem; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <div class="header">
            <h2><i class="fas fa-truck"></i> Track Order #<?php echo $order['order_number']; ?></h2>
            <a href="user_orders.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Orders</a>
        </div>

        <div class="status-timeline">
            <div class="timeline-step <?php echo in_array($order['status'], ['pending', 'shipped', 'delivered']) ? 'active' : ''; ?>">
                <div class="step-icon"><i class="fas fa-receipt"></i></div>
                <div class="step-label">Order Placed</div>
            </div>
            <div class="timeline-step <?php echo in_array($order['status'], ['shipped', 'delivered']) ? 'active' : ''; ?>">
                <div class="step-icon"><i class="fas fa-cogs"></i></div>
                <div class="step-label">Preparing</div>
            </div>
            <div class="timeline-step <?php echo in_array($order['status'], ['shipped']) ? 'active' : ''; ?>">
                <div class="step-icon"><i class="fas fa-shipping-fast"></i></div>
                <div class="step-label">On Delivery</div>
            </div>
            <div class="timeline-step <?php echo $order['status'] == 'delivered' ? 'active' : ''; ?>">
                <div class="step-icon"><i class="fas fa-check-circle"></i></div>
                <div class="step-label">Delivered</div>
            </div>
        </div>

        <div class="order-details">
            <h3 style="color:#fbecca;">Order Details</h3>
            <?php while ($item = $order_items->fetch_assoc()): ?>
                <div style="display:flex; justify-content:space-between; padding:8px 0; color:#d9c189;">
                    <span><?php echo $item['product_name']; ?> (x<?php echo $item['quantity']; ?>)</span>
                    <span><?php echo $item['subtotal']; ?></span>
                </div>
            <?php endwhile; ?>
            <div style="display:flex; justify-content:space-between; padding:10px 0; margin-top:10px; border-top:1px solid #7f683e;">
                <span>Total:</span>
                <span style="color:#fad48b; font-weight:bold;"><?php echo $order['total_amount']; ?></span>
            </div>
        </div>
    </div>
</div>
</body>
</html>