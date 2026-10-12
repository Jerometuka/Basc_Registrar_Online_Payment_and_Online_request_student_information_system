<?php
// cart.php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'user') {
    header("Location: signin.php");
    exit();
}
include 'dbcon.php';

$user_id = $_SESSION['username'];
$user_municipality = $_SESSION['municipality'] ?? 'San Ildefonso';

$shipping_fee = 50;
$voucher_applied = false;
$voucher_error = '';
$voucher_success = '';

if (isset($_SESSION['free_shipping_voucher']) && $_SESSION['free_shipping_voucher'] === true) {
    $voucher_applied = true;
}

if (isset($_POST['apply_voucher'])) {
    $voucher_code = trim($_POST['voucher_code']);
    $valid_vouchers = ['FREESHIP', 'FREE50', 'SHIPFREE', 'ISSANGVIP', 'WELCOME50'];
    if (in_array(strtoupper($voucher_code), $valid_vouchers)) {
        $_SESSION['free_shipping_voucher'] = true;
        $voucher_applied = true;
        $voucher_success = "Voucher applied! FREE SHIPPING!";
    } else {
        $voucher_error = "Invalid voucher code";
    }
}

if (isset($_POST['remove_voucher'])) {
    unset($_SESSION['free_shipping_voucher']);
    header("Location: cart.php");
    exit();
}

if (isset($_GET['remove'])) {
    $cart_id = (int)$_GET['remove'];
    $conn->query("DELETE FROM cart WHERE id = $cart_id AND user_id = '$user_id'");
    header("Location: cart.php");
    exit();
}

if (isset($_GET['clear'])) {
    $conn->query("DELETE FROM cart WHERE user_id = '$user_id'");
    header("Location: cart.php");
    exit();
}

$cart_items = $conn->query("SELECT * FROM cart WHERE user_id = '$user_id' ORDER BY added_at DESC");
$subtotal = 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ISSANG MACCHIATOS - Cart</title>
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
        .container { position: relative; z-index: 10; max-width: 1000px; margin: 0 auto; }
        .cart-card {
            background: rgba(30,20,15,0.92);
            backdrop-filter: blur(12px);
            border-radius: 32px;
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
        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(34,30,25,0.7);
            margin: 10px 25px;
            padding: 15px 20px;
            border-radius: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }
        .item-info { display: flex; align-items: center; gap: 15px; flex: 2; }
        .item-info img { width: 60px; height: 60px; object-fit: cover; border-radius: 12px; }
        .item-info i { font-size: 2.5rem; color: #d4af37; width: 60px; text-align: center; }
        .item-details h4 { color: #fbecca; }
        .item-details p { color: #fad48b; font-weight: bold; }
        .item-details .qty { color: #b89a6e; font-size: 0.8rem; margin-top: 5px; }
        .remove-btn { 
            background: #8b472e; 
            border: none; 
            padding: 8px 18px; 
            border-radius: 30px; 
            color: white; 
            text-decoration: none;
            display: inline-block;
        }
        .voucher-section {
            margin: 10px 25px;
            padding: 15px 20px;
            background: rgba(0,0,0,0.3);
            border-radius: 20px;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }
        .voucher-form { display: flex; gap: 10px; flex: 2; }
        .voucher-form input { flex: 1; padding: 10px 15px; border-radius: 30px; border: 1px solid #6b4c3a; background: rgba(20,12,8,0.9); color: #f0e2c5; }
        .voucher-form button { background: #d4af37; border: none; padding: 10px 20px; border-radius: 30px; color: #1c1610; font-weight: bold; cursor: pointer; }
        .cart-summary {
            margin: 20px 25px 25px;
            padding: 20px;
            background: rgba(0,0,0,0.4);
            border-radius: 20px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            color: #d4c5a9;
            border-bottom: 1px solid rgba(212,175,55,0.2);
        }
        .summary-row.total {
            border-bottom: none;
            padding-top: 15px;
            font-weight: bold;
            font-size: 1.2rem;
            color: #fbecca;
        }
        .summary-row .amount { color: #fad48b; font-weight: bold; }
        .summary-row.total .amount { font-size: 1.5rem; color: #d4af37; }
        .action-buttons { display: flex; justify-content: flex-end; gap: 15px; margin-top: 20px; }
        .checkout-btn {
            background: linear-gradient(135deg, #d4af37, #b8922a);
            padding: 12px 28px;
            border-radius: 40px;
            color: #1c1610;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
        }
        .clear-btn {
            background: #5a3a2e;
            padding: 12px 25px;
            border-radius: 40px;
            color: white;
            text-decoration: none;
        }
        .empty-cart { text-align: center; padding: 60px; color: #bda16e; }
        .footer { text-align: center; padding: 20px; border-top: 1px solid #d4af37; color: #9e885c; }
        @media (max-width: 700px) {
            .cart-item { margin: 10px 15px; flex-direction: column; text-align: center; }
            .item-info { flex-direction: column; }
            .voucher-section { margin: 10px 15px; flex-direction: column; }
            .voucher-form { width: 100%; }
            .action-buttons { justify-content: center; }
            .header { flex-direction: column; text-align: center; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="cart-card">
        <div class="header">
            <h2><i class="fas fa-shopping-cart"></i> My Shopping Cart</h2>
            <div>
                <a href="user.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Menu</a>
                <a href="signout.php" class="signout-btn"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
            </div>
        </div>

        <?php if ($cart_items && $cart_items->num_rows > 0): ?>
            <?php while ($item = $cart_items->fetch_assoc()): 
                $price_num = intval(preg_replace('/[^0-9]/', '', $item['product_price']));
                $item_total = $price_num * $item['quantity'];
                $subtotal += $item_total;
            ?>
                <div class="cart-item">
                    <div class="item-info">
                        <?php if (!empty($item['product_image']) && file_exists($item['product_image'])): ?>
                            <img src="<?php echo $item['product_image']; ?>?v=<?php echo time(); ?>" alt="<?php echo $item['product_name']; ?>">
                        <?php else: ?>
                            <i class="fas fa-mug-hot"></i>
                        <?php endif; ?>
                        <div class="item-details">
                            <h4><?php echo htmlspecialchars($item['product_name']); ?></h4>
                            <p><?php echo htmlspecialchars($item['product_price']); ?> each</p>
                            <div class="qty">Quantity: <?php echo $item['quantity']; ?></div>
                        </div>
                    </div>
                    <a href="?remove=<?php echo $item['id']; ?>" class="remove-btn" onclick="return confirm('Remove this item?')">Remove</a>
                </div>
            <?php endwhile; ?>
            
            <?php $total = $subtotal + ($voucher_applied ? 0 : $shipping_fee); ?>
            
            <div class="voucher-section">
                <?php if (!$voucher_applied): ?>
                    <form method="POST" class="voucher-form">
                        <input type="text" name="voucher_code" placeholder="Enter voucher code (FREESHIP, FREE50, ISSANGVIP)">
                        <button type="submit" name="apply_voucher">Apply</button>
                    </form>
                    <div><i class="fas fa-ticket-alt"></i> Enter code for FREE SHIPPING</div>
                <?php else: ?>
                    <div style="color:#8bc34a;"><i class="fas fa-check-circle"></i> Voucher Applied! FREE SHIPPING</div>
                    <form method="POST">
                        <button type="submit" name="remove_voucher" style="background:#5a3a2e; border:none; padding:8px 15px; border-radius:30px; color:white;">Remove</button>
                    </form>
                <?php endif; ?>
            </div>
            
            <div class="cart-summary">
                <div class="summary-row">
                    <span>Subtotal:</span>
                    <span class="amount">₱<?php echo number_format($subtotal, 2); ?></span>
                </div>
                <div class="summary-row">
                    <span><i class="fas fa-truck"></i> Shipping Fee:</span>
                    <span class="amount"><?php echo $voucher_applied ? 'FREE' : '₱' . number_format($shipping_fee, 2); ?></span>
                </div>
                <div class="summary-row total">
                    <span>Total:</span>
                    <span class="amount">₱<?php echo number_format($total, 2); ?></span>
                </div>
                <div class="action-buttons">
                    <a href="?clear=1" class="clear-btn" onclick="return confirm('Clear cart?')">Clear Cart</a>
                    <a href="checkout.php" class="checkout-btn">Proceed to Checkout</a>
                </div>
            </div>
        <?php else: ?>
            <div class="empty-cart">
                <i class="fas fa-shopping-cart" style="font-size:4rem; margin-bottom:15px;"></i>
                <p>Your cart is empty.</p>
                <a href="user.php" style="display:inline-block; margin-top:20px; background:#d4af37; padding:10px 30px; border-radius:40px; color:#1c1610; text-decoration:none;">Browse Menu</a>
            </div>
        <?php endif; ?>
        
        <div class="footer">
            <i class="fas fa-heart"></i> ISSANG MACCHIATOS — All drinks ₱49! Shipping ₱50
        </div>
    </div>
</div>
</body>
</html>