<?php
/**
 * Here is your custom functions.
 */

use plugin\admin\app\model\Admin;
use plugin\admin\app\model\AdminRole;
use support\Response;

/**
 * 当前管理员id
 * @return integer|null
 */
function admin_id(): ?int
{
    return session('admin.id');
}

function admin_lang(): string
{
    $lang = session('admin_lang');
    return in_array($lang, ['en', 'de'], true) ? $lang : 'zh';
}

/**
 * 后端文案翻译（语言文件在 resource/translations/{lang}.php）
 * 统一合并 menu + ui 两段，key 为中文原文
 * @param string $key 中文原文
 */
function admin_trans(string $key, string $section = ''): string
{
    if ($key === '' || admin_lang() === 'zh') {
        return $key;
    }
    $dict = admin_lang_dict();
    return $dict[$key] ?? $key;
}

/**
 * 返回当前语言的合并字典数组（menu + ui），中文原文 => 译文
 */
function admin_lang_dict(string $section = ''): array
{
    $lang = admin_lang();
    if ($lang === 'zh') {
        return [];
    }
    static $dicts = [];
    if (!isset($dicts[$lang])) {
        $file = base_path("plugin/admin/resource/translations/$lang.php");
        $raw = is_file($file) ? (array)(include $file) : [];
        $dicts[$lang] = array_merge((array)($raw['menu'] ?? []), (array)($raw['ui'] ?? []));
    }
    return $dicts[$lang];
}

/**
 * 返回登录后页面 <head> 中需内联的语言注入脚本（引入 i18n.js 并注入字典）
 */
function admin_lang_inject(): string
{
    $lang = admin_lang();
    $dict = json_encode(admin_lang_dict(), JSON_UNESCAPED_UNICODE);
    return '<script src="/app/admin/lang/i18n.js"></script>'
        . '<script>window.__LANG_CODE__=' . json_encode($lang) . ';window.__LANG_DICT__=' . $dict . ';</script>';
}

/**
 * 当前管理员
 * @param null|array|string $fields
 * @return array|mixed|null
 * @throws Exception
 */
function admin($fields = null)
{
    refresh_admin_session();
    if (!$admin = session('admin')) {
        return null;
    }
    if ($fields === null) {
        return $admin;
    }
    if (is_array($fields)) {
        $results = [];
        foreach ($fields as $field) {
            $results[$field] = $admin[$field] ?? null;
        }
        return $results;
    }
    return $admin[$fields] ?? null;
}


/**
 * 刷新当前管理员session
 * @param bool $force
 * @return void
 * @throws Exception
 */
function refresh_admin_session(bool $force = false)
{
    $admin_session = session('admin');
    if (!$admin_session) {
        return null;
    }
    $admin_id = $admin_session['id'];
    $time_now = time();
    // session在2秒内不刷新
    $session_ttl = 2;
    $session_last_update_time = session('admin.session_last_update_time', 0);
    if (!$force && $time_now - $session_last_update_time < $session_ttl) {
        return null;
    }
    $session = request()->session();
    $admin = Admin::find($admin_id);
    if (!$admin) {
        $session->forget('admin');
        return null;
    }
    $admin = $admin->toArray();
    $admin['password'] = md5($admin['password']);
    $admin_session['password'] = $admin_session['password'] ?? '';
    if ($admin['password'] != $admin_session['password']) {
        $session->forget('admin');
        return null;
    }
    // 账户被禁用
    if ($admin['status'] != 0) {
        $session->forget('admin');
        return;
    }
    $admin['roles'] = AdminRole::where('admin_id', $admin_id)->pluck('role_id')->toArray();
    $admin['session_last_update_time'] = $time_now;
    $session->set('admin', $admin);
}

function admin_error_401_script(): Response
{
  return response(<<<EOF
<script>top.location.href = '/app/admin';</script>
EOF
  );
}




