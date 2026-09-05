<?php
declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

$route = $_GET['r'] ?? 'dashboard';

$publicRoutes = ['login'];

if (!in_array($route, $publicRoutes, true)) {
    requireLogin();
}

switch ($route) {
    case 'login':
        require APP_PATH . '/controllers/auth_controller.php';
        break;
    case 'logout':
        require APP_PATH . '/controllers/auth_controller.php';
        break;
    case 'dashboard':
        require APP_PATH . '/controllers/dashboard_controller.php';
        break;
    case 'types':
    case 'types/create':
    case 'types/edit':
    case 'types/delete':
        require APP_PATH . '/controllers/types_controller.php';
        break;
    case 'groupes/show':
    case 'groupes/create':
    case 'groupes/edit':
    case 'groupes/delete':
        require APP_PATH . '/controllers/groupes_controller.php';
        break;
    case 'variantes/create':
    case 'variantes/edit':
    case 'variantes/delete':
        require APP_PATH . '/controllers/variantes_controller.php';
        break;
    case 'mouvements/create':
    case 'mouvements/history':
        require APP_PATH . '/controllers/mouvements_controller.php';
        break;
    case 'images/upload':
    case 'images/delete':
        require APP_PATH . '/controllers/images_controller.php';
        break;
    case 'users':
    case 'users/create':
    case 'users/edit':
    case 'users/delete':
    case 'users/unlock':
        require APP_PATH . '/controllers/users_controller.php';
        break;
    case 'settings':
    case 'settings/update':
        require APP_PATH . '/controllers/settings_controller.php';
        break;
    case 'account':
    case 'account/update':
        require APP_PATH . '/controllers/account_controller.php';
        break;
    case 'audit':
        require APP_PATH . '/controllers/audit_controller.php';
        break;
    case 'export/inventory':
        require APP_PATH . '/controllers/export_controller.php';
        break;
    default:
        http_response_code(404);
        render('errors/404', []);
}
