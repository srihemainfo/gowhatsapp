<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="shortcut icon" href="https://www.goride.net.in/public/goride/img/Go-Ride-fav-icon.webp">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Go Whatsapp - Login</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #f0f2f5;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
        }
        .login-box {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 350px;
            text-align: center;
        }
        .login-box img {
            width: 150px;
            margin-bottom: 20px;
        }
        .input-group {
            margin-bottom: 15px;
            text-align: left;
        }
        .input-group label {
            display: block;
            margin-bottom: 5px;
            color: #54656f;
            font-size: 14px;
        }
        .input-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #d1d7db;
            border-radius: 5px;
            font-size: 15px;
            box-sizing: border-box;
            outline: none;
        }
        .input-group input:focus {
            border-color: #008069;
        }
        .btn-submit {
            width: 100%;
            background-color: #008069;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
        }
        .btn-submit:hover {
            background-color: #005c4b;
        }
        .error-message {
            color: #d32f2f;
            background: #fdeaea;
            padding: 10px;
            border-radius: 5px;
            font-size: 14px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>

    <div class="login-box">
        <img src="https://www.goride.run/goride/img/logo-light.png" class="brand-logo" alt="GoRide" referrerpolicy="no-referrer">
        
        @if(session('error'))
            <div class="error-message">
                {{ session('error') }}
            </div>
        @endif

        <form action="{{ route('chat.login.submit') }}" method="POST">
            @csrf
            <div class="input-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn-submit">Log In</button>
        </form>
    </div>

</body>
</html>