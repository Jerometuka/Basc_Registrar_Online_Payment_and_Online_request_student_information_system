<?php
// checkout.php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'user') {
    header("Location: signin.php");
    exit();
}
include 'dbcon.php';

$username = $_SESSION['username'];
$default_address = $_SESSION['full_address'] ?? 'Purok 3 dimla, Anyatam, San Ildefonso, Bulacan';

$shipping_fee = 50;
$voucher_applied = isset($_SESSION['free_shipping_voucher']) && $_SESSION['free_shipping_voucher'] === true;

$cart_items = $conn->query("SELECT * FROM cart WHERE user_id = '$username'");

if ($cart_items->num_rows == 0) {
    header("Location: cart.php");
    exit();
}

$subtotal = 0;
$items = [];
while ($item = $cart_items->fetch_assoc()) {
    $price_num = intval(preg_replace('/[^0-9]/', '', $item['product_price']));
    $item_total = $price_num * $item['quantity'];
    $subtotal += $item_total;
    $items[] = $item;
}

$total = $subtotal + ($voucher_applied ? 0 : $shipping_fee);
$error = '';

// ============================================
// ITO ANG IMPORTANTE - PLACE ORDER
// ============================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['place_order'])) {
    $address = $_POST['delivery_address'];
    $payment = $_POST['payment_method'];
    $order_number = 'ISSANG-' . date('Ymd') . '-' . strtoupper(uniqid());
    $total_amount = '₱' . number_format($total, 2);
    
    $stmt = $conn->prepare("INSERT INTO orders (order_number, username, total_amount, delivery_address, status) VALUES (?, ?, ?, ?, 'pending')");
    $stmt->bind_param("ssss", $order_number, $username, $total_amount, $address);
    
    if ($stmt->execute()) {
        $order_id = $conn->insert_id;
        
        foreach ($items as $item) {
            $sub = intval(preg_replace('/[^0-9]/', '', $item['product_price'])) * $item['quantity'];
            $sub_str = '₱' . number_format($sub, 2);
            $conn->query("INSERT INTO order_items (order_id, product_name, product_price, quantity, subtotal) 
                          VALUES ($order_id, '{$item['product_name']}', '{$item['product_price']}', {$item['quantity']}, '$sub_str')");
        }
        
        $conn->query("DELETE FROM cart WHERE user_id = '$username'");
        unset($_SESSION['free_shipping_voucher']);
        
        header("Location: order_success.php?order=$order_number");
        exit();
    } else {
        $error = "Order failed: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ISSANG MACCHIATOS - Checkout</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: #0a0806;
            font-family: Arial, sans-serif;
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
            background-size: cover;
            opacity: 0.45;
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
            z-index: 0;
        }
        .container { position: relative; z-index: 10; max-width: 800px; margin: 0 auto; }
        .card {
            background: rgba(30,20,15,0.92);
            backdrop-filter: blur(12px);
            border-radius: 28px;
            border: 1px solid #d4af37;
            overflow: hidden;
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
        .header h2 i { color: #d4af37; }
        .back-btn, .signout-btn {
            background: #6a5a3e;
            padding: 8px 18px;
            border-radius: 40px;
            color: white;
            text-decoration: none;
        }
        .signout-btn { background: rgba(0,0,0,0.5); border: 1px solid #c7a155; margin-left: 8px; }
        .body-content { padding: 25px; }
        .section {
            background: rgba(34,30,25,0.5);
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .section h3 { color: #fbecca; border-bottom: 1px solid #d4af37; padding-bottom: 10px; margin-bottom: 15px; }
        .order-item { display: flex; justify-content: space-between; padding: 8px 0; color: #d9c189; border-bottom: 1px solid rgba(212,175,55,0.1); }
        .summary-row { display: flex; justify-content: space-between; padding: 8px 0; color: #d4c5a9; }
        .summary-row.total { border-top: 1px solid #d4af37; margin-top: 10px; padding-top: 15px; font-weight: bold; }
        .summary-row.total .amount { color: #d4af37; font-size: 1.3rem; }
        textarea {
            width: 100%;
            padding: 10px;
            border-radius: 12px;
            border: 1px solid #6b4c3a;
            background: rgba(20,12,8,0.9);
            color: #f0e2c5;
            resize: vertical;
        }
        .payment-option {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background: rgba(0,0,0,0.3);
            border-radius: 12px;
            margin-bottom: 10px;
            cursor: pointer;
        }
        .payment-option:hover { background: rgba(212,175,55,0.15); }
        .payment-option input { accent-color: #d4af37; transform: scale(1.1); }
        .payment-option label { color: #d9c189; cursor: pointer; flex: 1; }
        .payment-option i { color: #d4af37; width: 30px; }
        .place-order-btn {
            width: 100%;
            background: linear-gradient(135deg, #d4af37, #b8922a);
            border: none;
            padding: 14px;
            border-radius: 40px;
            color: #1c1610;
            font-weight: bold;
            font-size: 1rem;
            cursor: pointer;
            margin-top: 20px;
        }
        .place-order-btn:hover { background: #e8c450; }
        .error-msg { background: #8b3c2e; padding: 12px; border-radius: 12px; color: #ffcdb5; margin-bottom: 20px; text-align: center; }
        .footer { text-align: center; padding: 15px; border-top: 1px solid #d4af37; color: #9e885c; font-size: 0.7rem; }
        @media (max-width: 600px) {
            .body-content { padding: 15px; }
            .header { flex-direction: column; text-align: center; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <div class="header">
            <h2><i class="fas fa-credit-card"></i> Checkout</h2>
            <div>
                <a href="cart.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a>
                <a href="signout.php" class="signout-btn"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
            </div>
        </div>

        <div class="body-content">
            <?php if ($error): ?>
                <div class="error-msg"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <!-- Order Summary -->
                <div class="section">
                    <h3><i class="fas fa-receipt"></i> Order Summary</h3>
                    <?php foreach ($items as $item): 
                        $sub = intval(preg_replace('/[^0-9]/', '', $item['product_price'])) * $item['quantity'];
                    ?>
                        <div class="order-item">
                            <span><?php echo $item['product_name']; ?> (x<?php echo $item['quantity']; ?>)</span>
                            <span>₱<?php echo number_format($sub, 2); ?></span>
                        </div>
                    <?php endforeach; ?>
                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span>₱<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Shipping:</span>
                        <span><?php echo $voucher_applied ? 'FREE' : '₱' . number_format($shipping_fee, 2); ?></span>
                    </div>
                    <div class="summary-row total">
                        <span>Total:</span>
                        <span class="amount">₱<?php echo number_format($total, 2); ?></span>
                    </div>
                </div>

                <!-- Delivery Address -->
                <div class="section">
                    <h3><i class="fas fa-map-marker-alt"></i> Delivery Address</h3>
                    <textarea name="delivery_address" rows="3" required><?php echo htmlspecialchars($default_address); ?></textarea>
                </div>

                <!-- Payment Method -->
                <div class="section">
                    <h3><i class="fas fa-money-bill-wave"></i> Payment Method</h3>
                    <div class="payment-option">
                        <input type="radio" name="payment_method" value="Cash on Pickup" id="cash" checked>
                        <label for="cash"><i class="fas fa-money-bill"></i> Cash on Delivery</label>
                    </div>
                    <div class="payment-option">
                        <input type="radio" name="payment_method" value="GCash" id="gcash">
                        <label for="gcash"><i class="fas fa-mobile-alt"></i> GCash</label>
                    </div>
                    <div class="payment-option">
                        <input type="radio" name="payment_method" value="Credit/Debit Card" id="card">
                        <label for="card"><i class="fas fa-credit-card"></i> Credit/Debit Card</label>
                    </div>
                </div>

                <!-- ITO ANG PLACE ORDER BUTTON - SIGURADUHING ITO ANG NAME -->
                <button type="submit" name="place_order" class="place-order-btn">
                    <i class="fas fa-check-circle"></i> Place Order (₱<?php echo number_format($total, 2); ?>)
                </button>
            </form>
        </div>

        <div class="footer">
            <i class="fas fa-heart"></i> ISSANG MACCHIATOS — Thank you!
        </div>
    </div>
</div>
</body>
</html>