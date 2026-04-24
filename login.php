<?php
include('db.php');
session_start();

// 1. SET LANGUAGE (Default ni English)
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
}

$lang = $_SESSION['lang'] ?? 'en';

// 2. DICTIONARY (Tafsiri)
$texts = [
    'en' => [
        'title' => 'Login & Signup | BodaApp',
        'welcome' => 'Reliable Transport',
        'login_tab' => 'Login',
        'signup_tab' => 'Signup',
        'user_label' => 'Username',
        'pass_label' => 'Password',
        'name_label' => 'Full Name',
        'phone_label' => 'Phone Number',
        'role_label' => 'Register As',
        'btn_login' => 'Login Now',
        'btn_signup' => 'Create Account',
        'role_cust' => 'Customer',
        'role_rider' => 'Rider',
        'role_admin' => 'Administrator',
        'error' => 'Invalid Username or Password!',
        'success' => 'Registration Successful! Please Login.'
    ],
    'sw' => [
        'title' => 'Ingia & Sajili | BodaApp',
        'welcome' => 'Usafiri wa Uhakika',
        'login_tab' => 'Ingia',
        'signup_tab' => 'Sajili',
        'user_label' => 'Jina la Mtumiaji',
        'pass_label' => 'Nywila (Password)',
        'name_label' => 'Jina Kamili',
        'phone_label' => 'Namba ya Simu',
        'role_label' => 'Jisajili kama',
        'btn_login' => 'Ingia Sasa',
        'btn_signup' => 'Tengeneza Akaunti',
        'role_cust' => 'Mteja',
        'role_rider' => 'Dereva',
        'role_admin' => 'Msimamizi',
        'error' => 'Username au Password imekosea!',
        'success' => 'Usajili umekamilika! Sasa ingia.'
    ]
];

$t = $texts[$lang];

// --- LOGIC YA LOGIN ---
if(isset($_POST['login'])){
    $user = mysqli_real_escape_string($conn, $_POST['username']);
    $pass = mysqli_real_escape_string($conn, $_POST['password']);
    
    $query = "SELECT * FROM users WHERE username = '$user' AND password = '$pass'";
    $res = mysqli_query($conn, $query);

    if(mysqli_num_rows($res) > 0){
        $data = mysqli_fetch_assoc($res);
        $_SESSION['user_id'] = $data['id'];
        $_SESSION['role'] = $data['role'];
        $_SESSION['name'] = $data['name'];
        
        if($data['role'] == 'admin') header("Location: admin.php");
        else if($data['role'] == 'rider') header("Location: rider_dashboard.php");
        else header("Location: customer_request.php");
    } else {
        echo "<script>alert('{$t['error']}');</script>";
    }
}

// --- LOGIC YA SIGNUP ---
if(isset($_POST['signup'])){
    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);

    $check = mysqli_query($conn, "SELECT * FROM users WHERE username='$username'");
    if(mysqli_num_rows($check) > 0){
        echo "<script>alert('Username already taken!');</script>";
    } else {
        $sql = "INSERT INTO users (name, username, phone, password, role) VALUES ('$fullname', '$username', '$phone', '$password', '$role')";
        if(mysqli_query($conn, $sql)){
            echo "<script>alert('{$t['success']}');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['title']; ?></title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #e9ecef; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; flex-direction: column; }
        
        .lang-switcher { margin-bottom: 15px; }
        .lang-switcher a { text-decoration: none; padding: 5px 15px; background: #fff; border-radius: 20px; color: #2c3e50; font-weight: bold; font-size: 13px; border: 1px solid #2c3e50; margin: 0 5px; }
        .lang-switcher a.active { background: #2c3e50; color: #fff; }

        .auth-container { background: #fff; width: 400px; padding: 30px; border-radius: 15px; box-shadow: 0 15px 35px rgba(0,0,0,0.1); border-top: 8px solid #2c3e50; }
        .logo { text-align: center; margin-bottom: 20px; }
        .logo h1 { color: #2c3e50; margin: 0; font-size: 28px; }
        .logo span { font-size: 12px; color: #7f8c8d; text-transform: uppercase; }

        .tabs { display: flex; border-bottom: 2px solid #dee2e6; margin-bottom: 25px; }
        .tab { flex: 1; text-align: center; padding: 10px; cursor: pointer; font-weight: bold; color: #95a5a6; transition: 0.3s; }
        .tab.active { color: #2c3e50; border-bottom: 3px solid #2c3e50; }
        
        .form-group { margin-bottom: 15px; }
        label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 5px; color: #34495e; }
        input, select { width: 100%; padding: 10px; border: 1px solid #ced4da; border-radius: 8px; box-sizing: border-box; }
        
        .btn { width: 100%; padding: 12px; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; font-weight: bold; transition: 0.3s; margin-top: 10px; }
        .btn-primary { background: #2c3e50; color: white; }
        .btn-primary:hover { opacity: 0.9; }
        
        #signup-form { display: none; }
    </style>
</head>
<body>

<div class="lang-switcher">
    <a href="?lang=en" class="<?php echo ($lang == 'en') ? 'active' : ''; ?>">English</a>
    <a href="?lang=sw" class="<?php echo ($lang == 'sw') ? 'active' : ''; ?>">Kiswahili</a>
</div>

<div class="auth-container">
    <div class="logo">
        <h1>BodaApp 🏍️</h1>
        <span><?php echo $t['welcome']; ?></span>
    </div>

    <div class="tabs">
        <div class="tab active" id="login-tab" onclick="showLogin()"><?php echo $t['login_tab']; ?></div>
        <div class="tab" id="signup-tab" onclick="showSignup()"><?php echo $t['signup_tab']; ?></div>
    </div>

    <form id="login-form" method="POST">
        <div class="form-group">
            <label><?php echo $t['user_label']; ?></label>
            <input type="text" name="username" required>
        </div>
        <div class="form-group">
            <label><?php echo $t['pass_label']; ?></label>
            <input type="password" name="password" required>
        </div>
        <button type="submit" name="login" class="btn btn-primary"><?php echo $t['btn_login']; ?></button>
    </form>

    <form id="signup-form" method="POST">
        <div class="form-group">
            <label><?php echo $t['name_label']; ?></label>
            <input type="text" name="fullname" required>
        </div>
        <div class="form-group">
            <label><?php echo $t['user_label']; ?></label>
            <input type="text" name="username" required>
        </div>
        <div class="form-group">
            <label><?php echo $t['phone_label']; ?></label>
            <input type="text" name="phone" required>
        </div>
        <div class="form-group">
            <label><?php echo $t['role_label']; ?></label>
            <select name="role">
                <option value="customer"><?php echo $t['role_cust']; ?></option>
                <option value="rider"><?php echo $t['role_rider']; ?></option>
                <option value="admin"><?php echo $t['role_admin']; ?></option>
            </select>
        </div>
        <div class="form-group">
            <label><?php echo $t['pass_label']; ?></label>
            <input type="password" name="password" required>
        </div>
        <button type="submit" name="signup" class="btn btn-primary"><?php echo $t['btn_signup']; ?></button>
    </form>
</div>

<script>
    function showLogin() {
        document.getElementById('login-form').style.display = 'block';
        document.getElementById('signup-form').style.display = 'none';
        document.getElementById('login-tab').classList.add('active');
        document.getElementById('signup-tab').classList.remove('active');
    }
    function showSignup() {
        document.getElementById('login-form').style.display = 'none';
        document.getElementById('signup-form').style.display = 'block';
        document.getElementById('signup-tab').classList.add('active');
        document.getElementById('login-tab').classList.remove('active');
    }
</script>

</body>
</html>