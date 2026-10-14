<?php
require_once __DIR__ . '/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        check_csrf();
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $user = one('SELECT user_id,email,password,role,is_active FROM userdata WHERE email=? LIMIT 1', [$email]);
        if (!$user || !$user['is_active'] || !password_verify($password, $user['password'])) {
            throw new RuntimeException('อีเมลหรือรหัสผ่านไม่ถูกต้อง หรือบัญชีถูกระงับ');
        }
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        unset($_SESSION['csrf']);
        $target = ['admin' => '/admin/dashboard.php', 'employee' => '/employee/dashboard.php', 'user' => '/index.php'][$user['role']];
        redirect($target, 'เข้าสู่ระบบสำเร็จ');
    } catch (RuntimeException $exception) {
        $_SESSION['login_error'] = $exception->getMessage();
    }
}
require __DIR__ . '/../login_form.php';
