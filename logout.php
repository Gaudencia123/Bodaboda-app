<?php
// 1. Anza session ili uweze kuifuta
session_start();

// 2. Safisha data zote za session
$_SESSION = array();

// 3. Kama kuna cookies za session, zifute pia (hii ni kwa usalama zaidi)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Haribu session rasmi
session_destroy();
?>

<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout | BodaApp</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .logout-card {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 400px;
        }
        .icon {
            font-size: 50px;
            color: #ff9800;
            margin-bottom: 20px;
        }
        h2 { color: #333; margin-bottom: 10px; }
        p { color: #666; margin-bottom: 25px; }
        .loader {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #ff9800;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
    <meta http-equiv="refresh" content="2;url=login.php">
</head>
<body>

<div class="logout-card">
    <div class="icon">🏍️</div>
    <h2>Unatoka...</h2>
    <p>Asante kwa kutumia BodaApp. Unarudishwa kwenye ukurasa wa kuingia sasa hivi.</p>
    <div class="loader"></div>
</div>

</body>
</html>