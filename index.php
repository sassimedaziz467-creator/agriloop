<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require_once APP_ROOT . '/controller/ParcelleController.php';
require_once APP_ROOT . '/controller/ConsommationEauController.php';

try {
    $route = $_GET['route'] ?? 'parcelles';
    if (!is_string($route)) {
        $route = '';
    }

    switch ($route) {
        case 'parcelles':
            (new ParcelleController())->index();
            break;
        case 'parcelle.create':
            (new ParcelleController())->create();
            break;
        case 'parcelle.edit':
        case 'parcelle.delete':
            $id = is_string($_GET['id'] ?? null)
                ? filter_var($_GET['id'], FILTER_VALIDATE_INT)
                : false;
            if ($id === false || $id === null || $id < 1) {
                http_response_code(400);
                exit('Identifiant de parcelle invalide.');
            }
            $controller = new ParcelleController();
            if ($route === 'parcelle.edit') {
                $controller->edit((int) $id);
            } else {
                $controller->delete((int) $id);
            }
            break;
        case 'consommations':
            (new ConsommationEauController())->index();
            break;
        case 'consommation.edit':
        case 'consommation.delete':
            $id = is_string($_GET['id'] ?? null)
                ? filter_var($_GET['id'], FILTER_VALIDATE_INT)
                : false;
            if ($id === false || $id === null || $id < 1) {
                http_response_code(400);
                exit('Identifiant de consommation invalide.');
            }
            $controller = new ConsommationEauController();
            if ($route === 'consommation.edit') {
                $controller->edit((int) $id);
            } else {
                $controller->delete((int) $id);
            }
            break;
        default:
            http_response_code(404);
            exit('Page introuvable.');
    }
} catch (Throwable $exception) {
    error_log('[Agriloop] ' . get_class($exception) . ': ' . $exception->getMessage());
    http_response_code(500);
    echo 'Erreur technique. Vérifiez la base MySQL et la configuration de l’application.';
}
