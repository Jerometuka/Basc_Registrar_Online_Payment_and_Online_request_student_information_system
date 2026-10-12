<?php
// order_success.php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit();
}
include 'dbcon.php';

$order_number = isset($_GET['order']) ? $_GET['order'] : '';
$username = $_SESSION['username'];

$order = $conn->query("SELECT * FROM orders WHERE order_number = '$order_number' AND username = '$username'")->fetch_assoc();

if (!$order) {
    header("Location: user.php");
    exit();
}

$order_items = $conn->query("SELECT * FROM order_items WHERE order_id = " . $order['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Success - ISSANG MACCHIATOS</title>
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
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
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

        .container {
            position: relative;
            z-index: 10;
            max-width: 550px;
            width: 100%;
            margin: 0 auto;
        }

        .success-card {
            background: rgba(30, 20, 15, 0.95);
            backdrop-filter: blur(12px);
            border-radius: 32px;
            border: 1px solid #d4af37;
            overflow: hidden;
            text-align: center;
        }

        .success-header {
            background: linear-gradient(135deg, #2a241d, #1f1b16);
            padding: 30px;
            border-bottom: 1px solid rgba(212, 175, 55, 0.3);
        }

        .success-icon {
            font-size: 4rem;
            color: #4caf50;
            margin-bottom: 15px;
        }

        .success-header h1 {
            color: #f0dfbb;
            font-size: 1.8rem;
            margin-bottom: 8px;
        }

        .success-header p {
            color: #b89a6e;
            font-size: 0.9rem;
        }

        .body-content {
            padding: 25px;
        }

        .order-number-box {
            background: rgba(0, 0, 0, 0.4);
            border-radius: 16px;
            padding: 15px;
            text-align: center;
            margin-bottom: 20px;
            border: 1px solid rgba(212, 175, 55, 0.2);
        }

        .order-number-box .label {
            color: #b89a6e;
            font-size: 0.75rem;
            letter-spacing: 1px;
        }

        .order-number-box .number {
            color: #d4af37;
            font-size: 1.2rem;
            font-weight: bold;
            margin-top: 5px;
            word-break: break-all;
        }

        .info-section {
            background: rgba(34, 30, 25, 0.5);
            border-radius: 20px;
            padding: 18px;
            margin-bottom: 20px;
            text-align: left;
        }

        .info-section h3 {
            color: #fbecca;
            font-size: 1rem;
            margin-bottom: 12px;
            border-bottom: 1px solid rgba(212, 175, 55, 0.3);
            padding-bottom: 8px;
            text-align: center;
        }

        .info-section h3 i {
            color: #d4af37;
            margin-right: 8px;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            color: #d9c189;
            border-bottom: 1px solid rgba(212, 175, 55, 0.1);
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            color: #d4c5a9;
        }

        .summary-row.total {
            border-top: 1px solid rgba(212, 175, 55, 0.3);
            margin-top: 8px;
            padding-top: 12px;
            font-weight: bold;
        }

        .summary-row.total .amount {
            color: #d4af37;
            font-size: 1.1rem;
        }

        .address-box {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 12px;
            padding: 12px;
            color: #d9c189;
            font-size: 0.85rem;
            line-height: 1.5;
            text-align: center;
        }

        .address-box i {
            color: #d4af37;
            margin-right: 8px;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: bold;
            background: #ff9800;
            color: #1c1610;
        }

        .button-group {
            display: flex;
            gap: 12px;
            margin-top: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-track, .btn-shop {
            flex: 1;
            min-width: 140px;
            text-align: center;
            padding: 12px 20px;
            border-radius: 40px;
            text-decoration: none;
            font-weight: bold;
            transition: 0.3s;
        }

        .btn-track {
            background: #6a5a3e;
            color: white;
        }

        .btn-track:hover {
            background: #8a7a5e;
        }

        .btn-shop {
            background: linear-gradient(135deg, #d4af37, #b8922a);
            color: #1c1610;
        }

        .btn-shop:hover {
            background: linear-gradient(135deg, #e8c450, #c9a53b);
        }

        .footer {
            text-align: center;
            padding: 15px;
            border-top: 1px solid rgba(212, 175, 55, 0.2);
            color: #9e885c;
            font-size: 0.7rem;
        }

        .footer i {
            color: #d4af37;
        }

        @media (max-width: 500px) {
            .body-content {
                padding: 18px;
            }
            .success-header {
                padding: 20px;
            }
            .success-header h1 {
                font-size: 1.4rem;
            }
            .button-group {
                flex-direction: column;
            }
            .btn-track, .btn-shop {
                width: 100%;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="success-card">
        <div class="success-header">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h1>Order Successful!</h1>
            <p>Thank you for your order, <?php echo htmlspecialchars($username); ?>!</p>
        </div>

        <div class="body-content">
            <!-- Order Number -->
            <div class="order-number-box">
                <div class="label"><i class="fas fa-hashtag"></i> ORDER NUMBER</div>
                <div class="number"><?php echo htmlspecialchars($order['order_number']); ?></div>
                <div style="margin-top: 8px;">
                    <span class="status-badge">
                        <?php echo ucfirst($order['status']); ?>
                    </span>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="info-section">
                <h3><i class="fas fa-receipt"></i> Order Summary</h3>
                <?php 
                $subtotal = 0;
                while ($item = $order_items->fetch_assoc()): 
                    $price_num = intval(preg_replace('/[^0-9]/', '', $item['product_price']));
                    $item_subtotal = $price_num * $item['quantity'];
                    $subtotal += $item_subtotal;
                ?>
                    <div class="order-item">
                        <span><?php echo htmlspecialchars($item['product_name']); ?> (x<?php echo $item['quantity']; ?>)</span>
                        <span>₱<?php echo number_format($item_subtotal, 2); ?></span>
                    </div>
                <?php endwhile; ?>
                <div class="summary-row">
                    <span>Subtotal:</span>
                    <span>₱<?php echo number_format($subtotal, 2); ?></span>
                </div>
                <div class="summary-row total">
                    <span>Total Amount:</span>
                    <span class="amount"><?php echo htmlspecialchars($order['total_amount']); ?></span>
                </div>
            </div>

            <!-- Delivery Information -->
            <div class="info-section">
                <h3><i class="fas fa-truck"></i> Delivery Information</h3>
                <div class="address-box">
                    <i class="fas fa-map-marker-alt"></i> 
                    <?php echo nl2br(htmlspecialchars($order['delivery_address'])); ?>
                </div>
                <div class="summary-row" style="margin-top: 10px;">
                    <span>Order Date:</span>
                    <span><?php echo date('F d, Y h:i A', strtotime($order['order_date'])); ?></span>
                </div>
            </div>

            <!-- Buttons -->
            <div class="button-group">
                <a href="order_tracking.php?order=<?php echo $order['order_number']; ?>" class="btn-track">
                    <i class="fas fa-map-marker-alt"></i> Track Order
                </a>
                <a href="user.php" class="btn-shop">
                    <i class="fas fa-shopping-bag"></i> Continue Shopping
                </a>
            </div>
        </div>

        <div class="footer">
            <i class="fas fa-heart"></i> ISSANG MACCHIATOS — Thank you for choosing us! 
            <i class="fas fa-mug-hot"></i>
        </div>
    </div>
</div>
</body>
</html>