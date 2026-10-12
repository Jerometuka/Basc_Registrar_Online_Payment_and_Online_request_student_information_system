<?php
// user_orders.php - order history ng customer
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'user') {
    header("Location: signin.php");
    exit();
}
include 'dbcon.php';

$username = $_SESSION['username'];
$orders = $conn->query("SELECT * FROM orders WHERE username = '$username' ORDER BY order_date DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - ISSANG MACCHIATOS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
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
            background: linear-gradient(135deg, rgba(0,0,0,0.65) 0%, rgba(0,0,0,0.5) 100%);
            pointer-events: none;
            z-index: 0;
        }
        .container { position: relative; z-index: 10; max-width: 1200px; margin: 0 auto; }
        .card {
            background: rgba(30, 20, 15, 0.92);
            backdrop-filter: blur(12px);
            border-radius: 32px;
            border: 1px solid rgba(212, 175, 55, 0.5);
            overflow: hidden;
            padding: 25px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(212, 175, 55, 0.3);
        }
        h2 { color: #f0dfbb; }
        h2 i { color: #d4af37; margin-right: 10px; }
        .btn-back, .signout-btn {
            background: #6a5a3e;
            padding: 10px 20px;
            border-radius: 40px;
            color: white;
            text-decoration: none;
        }
        .signout-btn { background: rgba(0,0,0,0.5); border: 1px solid #c7a155; margin-left: 10px; }
        .orders-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px; }
        .order-card {
            background: rgba(34, 30, 25, 0.7);
            border-radius: 20px;
            padding: 18px;
            border: 1px solid rgba(212, 175, 55, 0.25);
        }
        .order-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(212, 175, 55, 0.2);
        }
        .order-number { color: #fad48b; font-weight: bold; }
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: bold;
        }
        .status-pending { background: #ff9800; color: #1c1610; }
        .status-shipped { background: #2196f3; color: white; }
        .status-delivered { background: #4caf50; color: white; }
        .status-cancelled { background: #f44336; color: white; }
        .order-date { color: #b89a6e; font-size: 0.7rem; margin-top: 8px; }
        .total-amount { color: #d4af37; font-size: 1.1rem; font-weight: bold; margin-top: 10px; }
        .footer { text-align: center; margin-top: 20px; padding-top: 15px; border-top: 1px solid rgba(212,175,55,0.2); color: #9e885c; }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <div class="header">
            <h2><i class="fas fa-shopping-bag"></i> My Orders</h2>
            <div>
                <a href="user.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Menu</a>
                <a href="signout.php" class="signout-btn"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
            </div>
        </div>

        <div class="orders-grid">
            <?php if ($orders && $orders->num_rows > 0): ?>
                <?php while ($order = $orders->fetch_assoc()): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <span class="order-number">#<?php echo $order['order_number']; ?></span>
                            <span class="status-badge status-<?php echo $order['status']; ?>"><?php echo ucfirst($order['status']); ?></span>
                        </div>
                        <div class="order-date">
                            <i class="fas fa-calendar"></i> <?php echo date('F d, Y h:i A', strtotime($order['order_date'])); ?>
                        </div>
                        <div class="total-amount">
                            Total: <?php echo $order['total_amount']; ?>
                        </div>
                        <div style="margin-top: 10px;">
                            <a href="order_tracking.php?order=<?php echo $order['order_number']; ?>" style="color:#d4af37; text-decoration:none;">
                                <i class="fas fa-eye"></i> Track Order
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="text-align:center; padding:40px; color:#b89a6e;">No orders yet.</div>
            <?php endif; ?>
        </div>

        <div class="footer">
            <i class="fas fa-heart"></i> ISSANG MACCHIATOS — Thank you for your orders!
        </div>
    </div>
</div>
</body>
</html>