<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Bangkok');
require_once __DIR__ . '/../dbconnect.php';
$conn->query("SET time_zone = '+07:00'");
function sql($query, $params = [])
{
    global $conn;
    $stmt = $conn->prepare($query);
    if ($params) {
        $stmt->bind_param(str_repeat('s', count($params)), ...$params);
    }
    $stmt->execute();
    return $stmt;
}
function rows($query, $params = [])
{
    return sql($query, $params)->get_result()->fetch_all(MYSQLI_ASSOC);
}
function one($query, $params = [])
{
    return sql($query, $params)->get_result()->fetch_assoc();
}
function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
function money($value)
{
    return number_format((float) $value, 2);
}
function cents($value)
{
    if (!preg_match('/^\d{1,7}(\.\d{1,2})?$/', (string) $value)) {
        throw new RuntimeException('จำนวนเงินไม่ถูกต้อง');
    }
    $parts = explode('.', (string) $value);
    return (int) $parts[0] * 100 + (int) str_pad($parts[1] ?? '', 2, '0');
}
function decimal($value)
{
    return sprintf('%d.%02d', intdiv($value, 100), $value % 100);
}
function integer($value, $min = 0)
{
    if (filter_var($value, FILTER_VALIDATE_INT) === false || (int) $value < $min || (int) $value > 1000000) {
        throw new RuntimeException('จำนวนต้องเป็นเลขจำนวนเต็มที่ถูกต้อง');
    }
    return (int) $value;
}
function current_user()
{
    static $loaded = false, $user = null;
    if (!$loaded) {
        $loaded = true;
        if (!empty($_SESSION['user_id'])) {
            $user = one('SELECT user_id,email,first_name,last_name,role,is_active,address,phone_number,title_name FROM userdata WHERE user_id=?', [$_SESSION['user_id']]);
            if (!$user || !$user['is_active']) {
                unset($_SESSION['user_id'], $_SESSION['email'], $_SESSION['role']);
                $user = null;
            }
            if ($user) {
                $_SESSION['role'] = $user['role'];
                $_SESSION['email'] = $user['email'];
            }
        }
    }
    return $user;
}
function require_roles($roles)
{
    $user = current_user();
    if (!$user) {
        header('Location: /login.php');
        exit;
    }
    if (!in_array($user['role'], $roles, true)) {
        http_response_code(403);
        exit('ไม่มีสิทธิ์เข้าถึงหน้านี้');
    }
    return $user;
}
function csrf()
{
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}
function csrf_field()
{
    echo '<input type="hidden" name="csrf" value="' . e(csrf()) . '">';
}
function check_csrf()
{
    if (!is_string($_POST['csrf'] ?? null) || !hash_equals(csrf(), $_POST['csrf'])) {
        throw new RuntimeException('แบบฟอร์มหมดอายุ กรุณาลองใหม่');
    }
}
function audit($actor, $action, $details)
{
    sql('INSERT INTO audit_log(actor_id,action,details) VALUES (?,?,?)', [$actor, $action, $details]);
}
function redirect($url, $message = '')
{
    $_SESSION['flash'] = $message;
    header('Location: ' . $url);
    exit;
}
function image_upload($field, $folder)
{
    $file = $_FILES[$field] ?? null;
    if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException('รูปภาพต้องมีขนาดไม่เกิน 5 MB');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
    if (!$ext || !getimagesize($file['tmp_name'])) {
        throw new RuntimeException('รองรับภาพ JPG, PNG และ WebP เท่านั้น');
    }
    $path = $folder . '/' . bin2hex(random_bytes(16)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], __DIR__ . '/../' . $path)) {
        throw new RuntimeException('บันทึกรูปภาพไม่สำเร็จ');
    }
    return $path;
}
const ORDER_STATUSES = ['Order Placed' => 'รอดำเนินการ', 'Order Processing' => 'กำลังจัดเตรียม', 'Shipped' => 'จัดส่งแล้ว', 'Completed' => 'สำเร็จ', 'Cancelled' => 'ยกเลิก'];
const ORDER_TRANSITIONS = ['Order Placed' => ['Order Processing', 'Cancelled'], 'Order Processing' => ['Shipped', 'Cancelled'], 'Shipped' => ['Completed'], 'Completed' => [], 'Cancelled' => []];
const PAYMENT_METHODS = ['cash' => 'เงินสด', 'transfer' => 'โอน', 'qr' => 'QR Payment'];
function settings()
{
    return array_column(rows('SELECT * FROM shop_settings'), 'setting_value', 'setting_key');
}
