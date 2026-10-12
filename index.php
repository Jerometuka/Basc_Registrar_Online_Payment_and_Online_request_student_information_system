<?php
// signin.php
session_start();
include 'dbcon.php';

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'user') {
        header("Location: user.php");
        exit();
    } elseif ($_SESSION['role'] == 'seller') {
        header("Location: seller.php");
        exit();
    }
}

$error = '';
$success = '';
$active_form = 'login';

$barangays_san_ildefonso = [
    'Akle', 'Alagao', 'Anyatam', 'Bagong Barrio', 'Basuit', 'Bubulong Munti',
    'Bubulong Malaki', 'Buhol na Mangga', 'Bulusukan', 'Calasag', 'Calawitan',
    'Casalat', 'Gabihan', 'Garlang', 'Lapnit', 'Maasim', 'Makapilapil',
    'Malipampang', 'Mataas na Parang', 'Matimbubong', 'Nabaong Garlang',
    'Palapala', 'Pasong Bangkal', 'Pinaod', 'Poblacion', 'Pulong Tamo',
    'San Juan', 'Santa Catalina Bata', 'Santa Catalina Matanda',
    'Sapang Dayap', 'Sapang Putik', 'Sapang Putol', 'Sumandig', 'Telapatio',
    'Umpucan', 'Upig'
];

$barangays_san_miguel = [
    'Betinan', 'Bulawan', 'Calube', 'Concepcion', 'Dao-an', 'Dumalian',
    'Fatima', 'Langilan', 'Lantawan', 'Laperian', 'Libuganan', 'Limonan',
    'Mati', 'Ocapan', 'Poblacion', 'San Isidro', 'Sayog', 'Tapian'
];

$barangays_san_rafael = [
    'Aripdip', 'Bagacay', 'Balagtas', 'Banca-banca', 'BMA-Balagtas',
    'Caingin', 'Calaigang', 'Capihan', 'Coral na Bato', 'Cruz na Daan',
    'Dagat-dagatan', 'Diliman I', 'Diliman II', 'Ilongbukid', 'Libis',
    'Lico', 'Maasim', 'Mabalas-balas', 'Maguinao', 'Maronquillo',
    'Paco', 'Pansumaloc', 'Pantubig', 'Pasong Bangkal', 'Pasong Callos',
    'Pasong Intsik', 'Pinacpinacan', 'Poblacion', 'Poscolon',
    'Pulo', 'Pulong Bayabas', 'Salapungan', 'Sampaloc', 'San Agustin',
    'San Andres', 'San Dionisio', 'San Florentino', 'San Roque',
    'Talacsan', 'Tambubong', 'Tukod', 'Ulingao'
];

$streets = [
    'Purok 1', 'Purok 2', 'Purok 3 dimla', 'Purok 4', 'Purok 5', 'Purok 6',
    'Purok 7', 'Purok 8', 'Purok 9', 'Purok 10', 'Purok 11', 'Purok 12',
];

$barangays_json = json_encode([
    'San Ildefonso' => $barangays_san_ildefonso,
    'San Miguel' => $barangays_san_miguel,
    'San Rafael' => $barangays_san_rafael
]);
$streets_json = json_encode($streets);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $role = $_POST['role'];

    if ($role == 'user') {
        $check = $conn->prepare("SELECT * FROM users WHERE username = ? AND role = 'user'");
        $check->bind_param("s", $username);
        $check->execute();
        $result = $check->get_result();

        if ($row = $result->fetch_assoc()) {
            if (md5($password) == $row['password']) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $username;
                $_SESSION['email'] = $row['email'] ?? '';
                $_SESSION['municipality'] = $row['municipality'] ?? '';
                $_SESSION['barangay'] = $row['barangay'] ?? '';
                $_SESSION['street'] = $row['street'] ?? '';
                $_SESSION['full_address'] = $row['full_address'] ?? '';
                $_SESSION['role'] = 'user';
                header("Location: user.php");
                exit();
            } else {
                $error = "Invalid password";
                $active_form = 'login';
            }
        } else {
            $error = "Username not found. Please register first.";
            $active_form = 'register';
        }
    } else if ($role == 'seller') {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND role = 'seller'");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            if (md5($password) == $row['password']) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $username;
                $_SESSION['role'] = 'seller';
                header("Location: seller.php");
                exit();
            } else {
                $error = "Invalid password";
                $active_form = 'login';
            }
        } else {
            $error = "Seller account not found";
            $active_form = 'login';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $username = trim($_POST['reg_username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['reg_password']);
    $confirm_password = trim($_POST['confirm_password']);
    $municipality = $_POST['municipality'];
    $barangay = $_POST['barangay'];
    $street = $_POST['street'];
    
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required";
        $active_form = 'register';
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
        $active_form = 'register';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
        $active_form = 'register';
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters";
        $active_form = 'register';
    } elseif (empty($municipality) || empty($barangay) || empty($street)) {
        $error = "Please select municipality, barangay, and street";
        $active_form = 'register';
    } else {
        $check = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        $result = $check->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Username already exists. Please choose another.";
            $active_form = 'register';
        } else {
            $check_email = $conn->prepare("SELECT * FROM users WHERE email = ?");
            $check_email->bind_param("s", $email);
            $check_email->execute();
            $email_result = $check_email->get_result();
            
            if ($email_result->num_rows > 0) {
                $error = "Email already registered. Please login.";
                $active_form = 'login';
            } else {
                $hashed_password = md5($password);
                $full_address = "$street, $barangay, $municipality, Bulacan";
                
                $stmt = $conn->prepare("INSERT INTO users (username, email, password, municipality, barangay, street, full_address, role) VALUES (?, ?, ?, ?, ?, ?, ?, 'user')");
                $stmt->bind_param("sssssss", $username, $email, $hashed_password, $municipality, $barangay, $street, $full_address);
                
                if ($stmt->execute()) {
                    $success = "Registration successful! Redirecting to dashboard...";
                    $_SESSION['user_id'] = $conn->insert_id;
                    $_SESSION['username'] = $username;
                    $_SESSION['email'] = $email;
                    $_SESSION['municipality'] = $municipality;
                    $_SESSION['barangay'] = $barangay;
                    $_SESSION['street'] = $street;
                    $_SESSION['full_address'] = $full_address;
                    $_SESSION['role'] = 'user';
                    header("refresh:2; url=user.php");
                    exit();
                } else {
                    $error = "Registration failed: " . $conn->error;
                    $active_form = 'register';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ISSANG MACCHIATOS - Sign In / Register</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background-image: url('issang lala.jpg');
            background-position: center;
            background-size: cover;
            font-family: 'Segoe UI', 'Poppins', 'Georgia', serif;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
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
            opacity: 0.55;
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
            background: linear-gradient(135deg, rgba(0,0,0,0.6) 0%, rgba(0,0,0,0.4) 100%);
            pointer-events: none;
            z-index: 0;
        }
        .container { position: relative; z-index: 10; width: 100%; max-width: 550px; margin: 0 auto; }
        .glass { background: rgba(30,20,15,0.92); backdrop-filter: blur(12px); border-radius: 32px; border: 1px solid rgba(212,175,55,0.5); box-shadow: 0 25px 50px rgba(0,0,0,0.6); }
        .login-box { padding: 2rem; }
        .brand { text-align: center; margin-bottom: 1.8rem; }
        .brand i { font-size: 3.5rem; color: #d4af37; text-shadow: 0 0 10px rgba(212,175,55,0.8); }
        .brand h1 { font-size: 2rem; background: linear-gradient(135deg, #f5e6b8, #d4af37, #b8922a); -webkit-background-clip: text; background-clip: text; color: transparent; font-weight: 800; letter-spacing: 2px; margin-top: 5px; font-family: 'Georgia', serif; }
        .brand .tagline { color: #d4c5a9; font-size: 0.8rem; letter-spacing: 2px; margin-top: 5px; }
        .social-links { display: flex; justify-content: center; gap: 20px; margin: 15px 0 10px; }
        .social-links a { color: #d4af37; font-size: 1.2rem; transition: 0.3s; text-decoration: none; }
        .social-links a:hover { color: #f5e6b8; transform: scale(1.1); }
        .luxury-line { width: 80px; height: 2px; background: linear-gradient(90deg, transparent, #d4af37, transparent); margin: 12px auto 8px; }
        .form-toggle { display: flex; gap: 1rem; margin-bottom: 1.8rem; border-bottom: 2px solid rgba(212,175,55,0.3); }
        .toggle-btn { flex: 1; background: none; border: none; padding: 0.8rem; font-size: 0.9rem; font-weight: 600; cursor: pointer; color: #b89a6e; transition: 0.3s; font-family: 'Georgia', serif; letter-spacing: 1px; }
        .toggle-btn.active { color: #d4af37; border-bottom: 2px solid #d4af37; }
        .form-panel { display: none; }
        .form-panel.active { display: block; }
        .role-tabs { display: flex; gap: 1rem; background: rgba(0,0,0,0.5); border-radius: 3rem; padding: 0.35rem; margin-bottom: 1.8rem; }
        .role-tab { flex: 1; text-align: center; padding: 0.7rem; border-radius: 2.5rem; font-weight: 600; cursor: pointer; background: transparent; color: #b89a6e; border: none; font-family: 'Segoe UI', sans-serif; font-size: 0.9rem; transition: 0.3s; }
        .role-tab.active { background: #d4af37; color: #2c1a12; box-shadow: 0 2px 10px rgba(212,175,55,0.3); }
        .input-group { margin-bottom: 1.3rem; position: relative; }
        .input-group label { display: block; margin-bottom: 0.5rem; color: #d4c5a9; font-size: 0.75rem; font-weight: 500; letter-spacing: 1px; text-transform: uppercase; }
        .input-group input, .location-select, .barangay-select, .street-select { width: 100%; padding: 0.85rem 1rem; background: rgba(20,12,8,0.9); border: 1px solid #6b4c3a; border-radius: 1.2rem; color: #f0e2c5; font-size: 0.95rem; outline: none; transition: 0.3s; }
        .password-wrapper { position: relative; width: 100%; }
        .password-wrapper input { width: 100%; padding: 0.85rem 1rem; padding-right: 3rem; background: rgba(20,12,8,0.9); border: 1px solid #6b4c3a; border-radius: 1.2rem; color: #f0e2c5; font-size: 0.95rem; outline: none; transition: 0.3s; }
        .toggle-password { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #b89a6e; font-size: 1.1rem; transition: 0.3s; z-index: 11; }
        .toggle-password:hover { color: #d4af37; }
        .btn { width: 100%; background: linear-gradient(135deg, #d4af37, #b8922a); border: none; padding: 0.9rem; border-radius: 2rem; font-weight: 700; font-size: 0.95rem; color: #1a0f0a; cursor: pointer; transition: 0.3s; font-family: 'Segoe UI', sans-serif; letter-spacing: 1px; text-transform: uppercase; }
        .btn:hover { background: linear-gradient(135deg, #e8c450, #c9a53b); transform: scale(1.01); box-shadow: 0 5px 15px rgba(212,175,55,0.4); }
        .alert-error { background: rgba(139,44,46,0.95); padding: 0.8rem; border-radius: 1rem; margin-bottom: 1rem; color: #ffcdb5; text-align: center; border-left: 4px solid #d4af37; font-size: 0.85rem; }
        .success-msg { background: rgba(60,110,60,0.95); padding: 0.8rem; border-radius: 1rem; margin-bottom: 1rem; color: #c8ffb5; text-align: center; border-left: 4px solid #d4af37; font-size: 0.85rem; }
        .seller-note { background: rgba(212,175,55,0.12); padding: 0.8rem; border-radius: 1rem; margin-top: 1.5rem; text-align: center; font-size: 0.75rem; color: #d4af37; border: 1px solid rgba(212,175,55,0.3); letter-spacing: 0.5px; }
        .note { text-align: center; margin-top: 1.2rem; font-size: 0.65rem; color: #b89a6e; }
        .form-row { display: flex; gap: 1rem; margin-bottom: 1.3rem; flex-wrap: wrap; }
        .form-row .input-group { flex: 1; margin-bottom: 0; }
        @media (max-width: 768px) { .form-row { flex-direction: column; gap: 1.3rem; } .login-box { padding: 1.5rem; } .brand h1 { font-size: 1.6rem; } }
    </style>
</head>
<body>
<div class="container">
    <div class="glass login-box">
        <div class="brand">
            <i class="fas fa-mug-hot"></i>
            <h1>ISSANG MACCHIATOS</h1>
            <div class="luxury-line"></div>
            <p class="tagline">☕ Experience the Finest Drinks in Town ☕</p>
            <div class="social-links">
                <a href="https://instagram.com/issangmacchiatos" target="_blank"><i class="fab fa-instagram"></i></a>
                <a href="https://tiktok.com/@issangmacchiatos" target="_blank"><i class="fab fa-tiktok"></i></a>
                <a href="#"><i class="fab fa-facebook"></i></a>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert-error"><i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-msg"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="form-toggle">
            <button type="button" id="loginToggle" class="toggle-btn <?php echo $active_form == 'login' ? 'active' : ''; ?>">🔐 LOGIN</button>
            <button type="button" id="registerToggle" class="toggle-btn <?php echo $active_form == 'register' ? 'active' : ''; ?>">📝 REGISTER</button>
        </div>

        <div id="loginForm" class="form-panel <?php echo $active_form == 'login' ? 'active' : ''; ?>">
            <form method="POST">
                <div class="role-tabs">
                    <button type="button" id="userTab" class="role-tab active">🍵 USER</button>
                    <button type="button" id="sellerTab" class="role-tab">🛒 SELLER</button>
                </div>
                <input type="hidden" name="role" id="roleInput" value="user">
                <input type="hidden" name="login" value="1">
                <div class="input-group">
                    <label><i class="far fa-user"></i> USERNAME</label>
                    <input type="text" name="username" placeholder="Enter your username" required>
                </div>
                <div class="input-group">
                    <label><i class="fas fa-lock"></i> PASSWORD</label>
                    <div class="password-wrapper">
                        <input type="password" name="password" id="loginPassword" placeholder="Enter your password" required>
                        <i class="fas fa-eye toggle-password" onclick="togglePassword('loginPassword', this)"></i>
                    </div>
                </div>
                <button type="submit" class="btn"><i class="fas fa-sign-in-alt"></i> SIGN IN</button>
            </form>
            <div class="seller-note"><i class="fas fa-store"></i> SELLER LOGIN: seller / seller123</div>
        </div>

        <div id="registerForm" class="form-panel <?php echo $active_form == 'register' ? 'active' : ''; ?>">
            <form method="POST">
                <input type="hidden" name="register" value="1">
                <div class="input-group">
                    <label><i class="far fa-user"></i> USERNAME</label>
                    <input type="text" name="reg_username" placeholder="Choose a username" required>
                </div>
                <div class="input-group">
                    <label><i class="far fa-envelope"></i> EMAIL ADDRESS</label>
                    <input type="email" name="email" placeholder="your@email.com" required>
                </div>
                <div class="form-row">
                    <div class="input-group">
                        <label><i class="fas fa-lock"></i> PASSWORD</label>
                        <div class="password-wrapper">
                            <input type="password" name="reg_password" id="regPassword" placeholder="Min. 6 characters" required>
                            <i class="fas fa-eye toggle-password" onclick="togglePassword('regPassword', this)"></i>
                        </div>
                    </div>
                    <div class="input-group">
                        <label><i class="fas fa-lock"></i> CONFIRM PASSWORD</label>
                        <div class="password-wrapper">
                            <input type="password" name="confirm_password" id="confirmPassword" placeholder="Re-enter password" required>
                            <i class="fas fa-eye toggle-password" onclick="togglePassword('confirmPassword', this)"></i>
                        </div>
                    </div>
                </div>
                <div class="input-group">
                    <label><i class="fas fa-city"></i> MUNICIPALITY</label>
                    <select name="municipality" id="municipality" class="location-select" required>
                        <option value="">Select Municipality</option>
                        <option value="San Ildefonso">🏘️ San Ildefonso (36 barangays)</option>
                        <option value="San Miguel">🏘️ San Miguel (18 barangays)</option>
                        <option value="San Rafael">🏘️ San Rafael (34 barangays)</option>
                    </select>
                </div>
                <div class="input-group">
                    <label><i class="fas fa-map-marker-alt"></i> BARANGAY</label>
                    <select name="barangay" id="barangay" class="barangay-select" required>
                        <option value="">Select Municipality First</option>
                    </select>
                </div>
                <div class="input-group">
                    <label><i class="fas fa-road"></i> STREET / PUROK / SUBDIVISION</label>
                    <select name="street" id="street" class="street-select" required>
                        <option value="">Select Street / Purok</option>
                    </select>
                </div>
                <button type="submit" class="btn"><i class="fas fa-user-plus"></i> REGISTER</button>
            </form>
            <div class="note"><i class="fas fa-info-circle"></i> By registering, you agree to our terms and conditions.</div>
        </div>
    </div>
</div>

<script>
    function togglePassword(fieldId, element) {
        const passwordInput = document.getElementById(fieldId);
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            element.classList.remove('fa-eye');
            element.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            element.classList.remove('fa-eye-slash');
            element.classList.add('fa-eye');
        }
    }

    const barangays = <?php echo $barangays_json; ?>;
    const streets = <?php echo $streets_json; ?>;
    
    const streetSelect = document.getElementById('street');
    if (streetSelect) {
        for (let i = 0; i < streets.length; i++) {
            const option = document.createElement('option');
            option.value = streets[i];
            option.textContent = streets[i];
            streetSelect.appendChild(option);
        }
    }
    
    const municipalitySelect = document.getElementById('municipality');
    const barangaySelect = document.getElementById('barangay');
    
    if (municipalitySelect && barangaySelect) {
        municipalitySelect.addEventListener('change', function() {
            const selected = this.value;
            barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
            if (selected && barangays[selected]) {
                for (let i = 0; i < barangays[selected].length; i++) {
                    const option = document.createElement('option');
                    option.value = barangays[selected][i];
                    option.textContent = barangays[selected][i];
                    barangaySelect.appendChild(option);
                }
            }
        });
    }
    
    const loginToggle = document.getElementById('loginToggle');
    const registerToggle = document.getElementById('registerToggle');
    const loginForm = document.getElementById('loginForm');
    const registerFormDiv = document.getElementById('registerForm');

    if (loginToggle && registerToggle && loginForm && registerFormDiv) {
        loginToggle.addEventListener('click', function() {
            loginToggle.classList.add('active');
            registerToggle.classList.remove('active');
            loginForm.classList.add('active');
            registerFormDiv.classList.remove('active');
        });
        registerToggle.addEventListener('click', function() {
            registerToggle.classList.add('active');
            loginToggle.classList.remove('active');
            registerFormDiv.classList.add('active');
            loginForm.classList.remove('active');
        });
    }

    const userTab = document.getElementById('userTab');
    const sellerTab = document.getElementById('sellerTab');
    const roleInput = document.getElementById('roleInput');

    if (userTab && sellerTab && roleInput) {
        userTab.addEventListener('click', function() {
            userTab.classList.add('active');
            sellerTab.classList.remove('active');
            roleInput.value = 'user';
        });
        sellerTab.addEventListener('click', function() {
            sellerTab.classList.add('active');
            userTab.classList.remove('active');
            roleInput.value = 'seller';
        });
    }
</script>
</body>
</html>