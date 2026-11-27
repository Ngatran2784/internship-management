<?php
require_once __DIR__ . '/../config/database.php';

function renderSidebarForRoles(array $roles, string $requestUri, string $activePage = '') {
    $_SESSION['roles'] = $roles;
    $_SESSION['user_id'] = 1;
    $_SERVER['REQUEST_URI'] = $requestUri;
    $baseUrl = '/CNPM/internship-management/public';
    ob_start();
    include __DIR__ . '/../app/Views/layouts/sidebar.php';
    return ob_get_clean();
}

$testAccounts = [
    'admin'     => ['roles' => ['admin'], 'label' => 'Quản trị viên thuần'],
    'pdt001'    => ['roles' => ['training_dept'], 'label' => 'Phòng Đào tạo thuần'],
    'tbm001'    => ['roles' => ['department_head'], 'label' => 'Trưởng bộ môn thuần'],
    'gv001'     => ['roles' => ['lecturer'], 'label' => 'Giảng viên thuần'],
    'sv001'     => ['roles' => ['student'], 'label' => 'Sinh viên thuần'],
    'dn001'     => ['roles' => ['enterprise'], 'label' => 'Doanh nghiệp thuần'],
    'tbm_gv'    => ['roles' => ['department_head', 'lecturer'], 'label' => 'TBM kiêm Giảng viên'],
    'admin_sv'  => ['roles' => ['admin', 'student'], 'label' => 'Admin kiêm Sinh viên'],
];

echo "=== RÀ SOÁT MENU SIDEBAR VÀ ĐIỀU HƯỚNG CỦA 8 TÀI KHOẢN (KÈM MỤC DEMO) ===\n\n";

foreach ($testAccounts as $acc => $meta) {
    $roles = $meta['roles'];
    $label = $meta['label'];
    $sb = renderSidebarForRoles($roles, '/CNPM/internship-management/public/dashboard', 'dashboard');

    // Đếm số lượng active
    preg_match_all('/class="[^"]*nav-link[^"]*active[^"]*"/', $sb, $matches);
    $activeCount = count($matches[0]);

    // Trích xuất tất cả các mục menu
    // 1. Nhóm cha
    preg_match_all('/class="nav-link-title sidebar-group-title">(.*?)<\/span>/', $sb, $groups);
    // 2. Các mục demo
    preg_match_all('/data-feature-title="([^"]+)"/', $sb, $demos);
    // 3. Các mục link thật
    preg_match_all('/<a class="nav-link[^"]*"[^>]*href="([^"#]+)"[^>]*>\s*<span class="nav-link-icon">.*?<\/span>\s*<span class="nav-link-title[^"]*">(.*?)<\/span>/s', $sb, $realLinks);

    $realTitles = array_map(function($t) {
        return trim(strip_tags(html_entity_decode($t, ENT_QUOTES, 'UTF-8')));
    }, $realLinks[2]);

    echo "[$acc] $label:\n";
    echo "  - Số mục active tại /dashboard: $activeCount (Kỳ vọng: 1)\n";
    echo "  - Các nhóm menu: " . implode(' | ', array_map('trim', $groups[1])) . "\n";
    echo "  - Các mục có Route thật: " . implode(' | ', $realTitles) . "\n";
    echo "  - Các mục Demo: " . implode(' | ', $demos[1]) . "\n\n";
}
