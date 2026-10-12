<?php
// user.php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'user') {
    header("Location: signin.php");
    exit();
}
include 'dbcon.php';

// ADD TO CART
if (isset($_GET['add_to_cart'])) {
    $product_id = (int)$_GET['add_to_cart'];
    $user_id = $_SESSION['username'];
    
    $product_query = $conn->query("SELECT * FROM products WHERE id = $product_id");
    $product = $product_query->fetch_assoc();
    
    if ($product) {
        $check_cart = $conn->query("SELECT * FROM cart WHERE user_id = '$user_id' AND product_id = $product_id");
        if ($check_cart->num_rows > 0) {
            $conn->query("UPDATE cart SET quantity = quantity + 1 WHERE user_id = '$user_id' AND product_id = $product_id");
        } else {
            $conn->query("INSERT INTO cart (user_id, product_id, product_name, product_price, product_image) 
                          VALUES ('$user_id', $product_id, '{$product['name']}', '{$product['price']}', '{$product['product_image']}')");
        }
    }
    header("Location: user.php?added=1");
    exit();
}

$products = $conn->query("SELECT * FROM products ORDER BY id DESC");
$username = $_SESSION['username'];
$email = $_SESSION['email'] ?? 'Not provided';
$municipality = $_SESSION['municipality'] ?? 'Not set';
$barangay = $_SESSION['barangay'] ?? 'Not set';
$street = $_SESSION['street'] ?? 'Not set';

$cart_count = $conn->query("SELECT SUM(quantity) as total FROM cart WHERE user_id = '$username'")->fetch_assoc()['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ISSANG MACCHIATOS - Menu</title>
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
            background: rgba(0, 0, 0, 0.6);
            pointer-events: none;
            z-index: 0;
        }

        .main-container {
            position: relative;
            z-index: 10;
            max-width: 1300px;
            margin: 0 auto;
        }

        .dashboard-card {
            background: rgba(30, 20, 15, 0.92);
            backdrop-filter: blur(12px);
            border-radius: 32px;
            border: 1px solid rgba(212, 175, 55, 0.5);
            overflow: hidden;
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

        .welcome-text h2 {
            color: #f0dfbb;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .welcome-text h2 i {
            color: #d4af37;
            margin-right: 10px;
        }

        .user-details {
            margin-top: 8px;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }

        .user-details span {
            background: rgba(0, 0, 0, 0.3);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            color: #d9c189;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .user-details span i {
            color: #d4af37;
        }

        .header-buttons {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .track-btn {
            background: #6a5a3e;
            border: none;
            padding: 10px 20px;
            border-radius: 40px;
            color: white;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.3s;
        }

        .track-btn:hover {
            background: #8a7a5e;
            transform: scale(1.02);
        }

        .cart-btn {
            background: #c8a45c;
            border: none;
            padding: 10px 20px;
            border-radius: 40px;
            color: #1c1610;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.3s;
        }

        .cart-btn:hover {
            background: #e0b66b;
            transform: scale(1.02);
        }

        .cart-badge {
            background: #1c1610;
            color: #c8a45c;
            border-radius: 30px;
            padding: 2px 8px;
            font-size: 0.75rem;
            font-weight: bold;
        }

        .signout-btn {
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid #c7a155;
            padding: 10px 20px;
            border-radius: 40px;
            color: #f5e5ca;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
        }

        .signout-btn:hover {
            background: #c7a155;
            color: #1c1610;
        }

        .section-title {
            text-align: center;
            padding: 35px 20px 20px;
        }

        .section-title h1 {
            color: #f3dfae;
            font-size: 1.8rem;
            font-weight: 500;
            display: inline-block;
            border-bottom: 3px solid #c8a45c;
            padding-bottom: 10px;
        }

        .section-title h1 i {
            color: #e2bc74;
            margin-right: 10px;
        }

        .price-badge {
            background: #c8a45c;
            color: #1c1610;
            padding: 5px 15px;
            border-radius: 30px;
            font-size: 0.9rem;
            font-weight: bold;
            display: inline-block;
            margin-top: 10px;
        }

        .products-wrapper {
            padding: 20px 30px 40px;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
        }

        .product-card {
            background: rgba(34, 30, 25, 0.85);
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid rgba(212, 178, 100, 0.3);
            text-align: center;
        }

        .product-card:hover {
            transform: translateY(-6px);
            background: rgba(55, 48, 39, 0.95);
            border-color: #c8a45c;
        }

        .product-image-area {
            height: 180px;
            background: linear-gradient(145deg, #2a241d, #1f1b16);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .product-image-area i {
            font-size: 4rem;
            color: #e2bc74;
        }

        .product-image-area img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-details {
            padding: 18px 15px 20px;
        }

        .product-name {
            color: #fbecca;
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .product-desc {
            color: #b89f6e;
            font-size: 0.8rem;
            margin-bottom: 12px;
            line-height: 1.4;
        }

        .product-price {
            color: #fad48b;
            font-size: 1.4rem;
            font-weight: 800;
            margin-bottom: 12px;
        }

        .add-to-cart-btn {
            background: #c8a45c;
            border: none;
            padding: 10px 15px;
            border-radius: 40px;
            color: #1c1610;
            font-weight: 700;
            cursor: pointer;
            width: 100%;
            transition: 0.3s;
            display: inline-block;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .add-to-cart-btn:hover {
            background: #e0b66b;
            transform: scale(1.02);
        }

        .success-msg {
            background: #3c6e3c;
            margin: 0 30px 0 30px;
            padding: 12px;
            border-radius: 12px;
            text-align: center;
            color: #c8ffb5;
        }

        .footer {
            text-align: center;
            padding: 20px 30px;
            border-top: 1px solid rgba(212, 175, 55, 0.2);
            color: #9e885c;
            font-size: 0.8rem;
        }

        .footer i {
            color: #e7bd6e;
        }

        @media (max-width: 900px) {
            .products-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 20px;
            }
        }

        @media (max-width: 600px) {
            body {
                padding: 10px;
            }
            .products-grid {
                grid-template-columns: 1fr;
            }
            .products-wrapper {
                padding: 15px 20px 30px;
            }
            .dashboard-header {
                flex-direction: column;
                text-align: center;
            }
            .section-title h1 {
                font-size: 1.4rem;
            }
            .user-details {
                justify-content: center;
            }
            .header-buttons {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
<div class="main-container">
    <div class="dashboard-card">
        <!-- Header -->
        <div class="dashboard-header">
            <div class="welcome-text">
                <h2><i class="fas fa-user-circle"></i> Welcome, <?php echo htmlspecialchars($username); ?>!</h2>
                <div class="user-details">
                    <span><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($email); ?></span>
                    <span><i class="fas fa-city"></i> <?php echo htmlspecialchars($municipality); ?></span>
                    <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($barangay); ?></span>
                    <span><i class="fas fa-road"></i> <?php echo htmlspecialchars($street); ?></span>
                </div>
                <p style="margin-top: 8px;"><i class="fas fa-mug-hot"></i> Experience the Finest Drinks in Town</p>
            </div>
            <div class="header-buttons">
                <!-- TRACK ORDERS BUTTON - BAGO -->
                <a href="orders.php" class="track-btn">
                    <i class="fas fa-truck"></i> Track Orders
                </a>
                <a href="cart.php" class="cart-btn">
                    <i class="fas fa-shopping-cart"></i> Cart 
                    <?php if($cart_count > 0): ?>
                        <span class="cart-badge"><?php echo $cart_count; ?></span>
                    <?php endif; ?>
                </a>
                <a href="signout.php" class="signout-btn">
                    <i class="fas fa-sign-out-alt"></i> Sign Out
                </a>
            </div>
        </div>

        <!-- Title -->
        <div class="section-title">
            <h1><i class="fas fa-crown"></i> Our Signature Drinks</h1>
            <div class="price-badge"><i class="fas fa-tag"></i> All drinks only ₱49!</div>
        </div>

        <?php if (isset($_GET['added'])): ?>
            <div class="success-msg">
                <i class="fas fa-check-circle"></i> Item added to cart successfully!
            </div>
        <?php endif; ?>

        <!-- Products Grid -->
        <div class="products-wrapper">
            <?php if ($products && $products->num_rows > 0): ?>
                <div class="products-grid">
                    <?php while ($row = $products->fetch_assoc()): ?>
                        <div class="product-card">
                            <div class="product-image-area">
                                <?php if (!empty($row['product_image']) && file_exists($row['product_image'])): ?>
                                    <img src="<?php echo htmlspecialchars($row['product_image']); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>">
                                <?php else: ?>
                                    <i class="fas <?php echo htmlspecialchars($row['icon']); ?>"></i>
                                <?php endif; ?>
                            </div>
                            <div class="product-details">
                                <div class="product-name"><?php echo htmlspecialchars($row['name']); ?></div>
                                <div class="product-desc"><?php echo htmlspecialchars($row['description']); ?></div>
                                <div class="product-price"><?php echo htmlspecialchars($row['price']); ?></div>
                                <a href="?add_to_cart=<?php echo $row['id']; ?>" class="add-to-cart-btn">
                                    <i class="fas fa-cart-plus"></i> Add to Cart
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 60px; color: #bda16e;">
                    <i class="fas fa-coffee" style="font-size: 3rem; margin-bottom: 15px; display: block;"></i>
                    <p>No drinks available at the moment.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <div class="footer">
            <i class="fas fa-heart"></i> ISSANG MACCHIATOS — Macchiato perfection every sip 
            <i class="fas fa-mug-hot"></i>
        </div>
    </div>
</div>
</body>
</html>