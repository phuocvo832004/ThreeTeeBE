<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            background-color: #f5f5f5;
            padding: 20px;
            text-align: center;
        }
        .header img {
            width: 200px;
        }
        .content {
            padding: 20px;
            text-align: center;
        }
        .content h1 {
            color: #333;
            font-size: 24px;
            margin-bottom: 10px;
        }
        .content p {
            color: #555;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        .content a.button {
            display: inline-block;
            background-color: blue;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
        }
        .footer {
            padding: 20px;
            font-size: 14px;
            color: #888;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Logo -->
        <div class="header">
            <img src="https://res.cloudinary.com/drvzczhve/image/upload/v1733756655/u5dt2fodq54jiugruhnh.png" alt="App Logo">
        </div>
        <!-- Nội dung chính -->
        <div class="content">
            <h1>Đặt lại mật khẩu</h1>
            <p>
                Xin chào {{ $notifiable->name }},<br>
                Nhấn vào nút bên dưới để đặt lại mật khẩu tại ThreeTee. Nếu không phải bạn, vui lòng liên hệ chúng tôi qua:
                <a href="https://www.facebook.com/hiep.nguyenhong.3994" style="color: blue; text-decoration: none;">Tại đây</a> để bảo vệ tài khoản của bạn.
            </p>
            <a href="{{ $url }}" class="button">Đặt lại mật khẩu</a>
        </div>
        <!-- Footer -->
        <div class="footer">
            <p>Thân chào bạn<br>Bill Hiep (CEO OF ThreeTee)</p>
            <p style="color: #555;">Liên kết sẽ hết hạn sau 60 phút.</p>
        </div>
    </div>
</body>
</html>
