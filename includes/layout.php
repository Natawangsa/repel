<?php
function getNavItems(string $role): array {
    $base = "/{$role}";
    $nav = [
        'admin' => [
            ['dashboard','Dashboard',$base.'/dashboard'],
            ['order','Order',$base.'/order'],
            ['payment','Payment',$base.'/payment'],
            ['customer','Customer',$base.'/customer'],
            ['search','Search',$base.'/search'],
            ['reports','Reports',$base.'/reports'],
            ['settings','Settings',$base.'/settings'],
        ],
        'desainer' => [
            ['dashboard','Dashboard',$base.'/dashboard'],
            ['settings','Settings',$base.'/settings'],
        ],
        'operator' => [
            ['dashboard','Dashboard',$base.'/dashboard'],
            ['progress','Progress',$base.'/progress'],
            ['settings','Settings',$base.'/settings'],
        ],
        'finishing' => [
            ['dashboard','Dashboard',$base.'/dashboard'],
            ['settings','Settings',$base.'/settings'],
        ],
        'owner' => [
            ['dashboard','Dashboard',$base.'/dashboard'],
            ['production-monitor','Production Monitor',$base.'/production-monitor'],
            ['customer','Customer',$base.'/customer'],
            ['search','Search',$base.'/search'],
            ['settings','Settings',$base.'/settings'],
        ],
    ];
    return $nav[$role] ?? [];
}

function svgIcon(string $name): string {
    $icons = [
        'dashboard' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>',
        'order'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>',
        'payment'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>',
        'customer'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
        'search'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>',
        'reports'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>',
        'settings'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
        'progress'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>',
        'user'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
        'logout'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>',
        'production-monitor' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>',
    ];
    return $icons[$name] ?? $icons['dashboard'];
}

function layoutStart(string $title, string $activeNav): void {
    $user  = authUser();
    $role  = $user['role'] ?? '';
    $flash = getFlash();
    $navItems = getNavItems($role);
    $iconMap = ['dashboard'=>'dashboard','order'=>'order','payment'=>'payment','customer'=>'customer','search'=>'search','reports'=>'reports','settings'=>'settings','progress'=>'progress','production-monitor'=>'production-monitor'];
    ?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= h($title) ?> - HaFI85 Digital Printing</title>
<link rel="stylesheet" href="/css/app.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
<div class="layout">
<aside class="sidebar">
  <div class="sidebar-logo">
    <div class="sidebar-logo-text">HaF<span>I</span>85</div>
    <div class="sidebar-logo-sub">Digital Printing</div>
  </div>
  <nav>
    <?php foreach ($navItems as [$key, $label, $url]): ?>
    <a href="<?= $url ?>" class="nav-item <?= $activeNav === $key ? 'active' : '' ?>">
      <?= svgIcon($iconMap[$key] ?? 'dashboard') ?>
      <span><?= h($label) ?></span>
    </a>
    <?php endforeach; ?>
  </nav>
  <hr class="sidebar-divider">
  <div class="sidebar-identity">
    <div class="sidebar-avatar"><?= strtoupper(mb_substr($user['username'], 0, 1)) ?></div>
    <div class="sidebar-identity-info">
      <span class="sidebar-username"><?= h($user['username']) ?></span>
      <span class="sidebar-role"><?= ucfirst(h($role)) ?></span>
    </div>
  </div>
  <hr class="sidebar-divider">
  <form method="POST" action="/logout" style="margin-top:auto;">
    <input type="hidden" name="_token" value="<?= csrfToken() ?>">
    <button type="submit" class="nav-item" style="width:100%;background:none;border:none;cursor:pointer;text-align:left;">
      <?= svgIcon('logout') ?><span>Logout</span>
    </button>
  </form>
</aside>
<main class="main-content">
<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>">
  <?= h($flash['msg']) ?>
</div>
<?php endif; ?>
<?php
}

function layoutEnd(): void {
    echo '</main></div></body></html>';
}
