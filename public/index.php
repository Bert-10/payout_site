<?php
require_once '../vendor/autoload.php';
require_once '../framework/autoload.php';
require_once "../middlewares/LoginRequiredMiddleware.php";
require_once "../controllers/MainController.php";
require_once "../controllers/Controller404.php";
require_once "../controllers/LoginController.php";
require_once "../controllers/LogoutController.php";
require_once "../controllers/DocumentController.php";

$loader = new \Twig\Loader\FilesystemLoader('../views');
$twig = new \Twig\Environment($loader, [
    "debug" => true // добавляем тут debug режим
]);
$twig->addExtension(new \Twig\Extension\DebugExtension());

session_set_cookie_params(60*60*10);
session_start();

$pdo = new PDO("mysql:host=localhost;dbname=payout_db;charset=utf8;port=3307", "root", "");

// if (!isset($_SESSION['user'])) {
//     $_SESSION['user'] = "no";
// }

$router = new Router($twig, $pdo);
$router->add("/", MainController::class)->middleware(new LoginRequiredMiddleware());
$router->add("/login", LoginController::class);
$router->add("/logout", LogoutController::class);
$router->add("/documents", DocumentController::class)->middleware(new LoginRequiredMiddleware());
$router->get_or_default(Controller404::class);


// $router->add("/", MainController::class)->middleware(new LoginRequiredMiddleware());
// $router->add("/web_object/(?P<id>\d+)", ObjectController::class)->middleware(new LoginRequiredMiddleware()); 
// $router->add("/search", SearchController::class)->middleware(new LoginRequiredMiddleware());
// $router->add("/login", LoginController::class);
// $router->add("/logout", LogoutController::class);
// $router->add("/set-welcome", SetWelcomeController::class)->middleware(new LoginRequiredMiddleware());
// $router->add("/web-object/create", WebObjectCreateController::class)->middleware(new LoginRequiredMiddleware());
// $router->add("/web-object/create_type", CreateTypeController::class)->middleware(new LoginRequiredMiddleware());
// $router->add("/web-object/(?P<id>\d+)/delete", WebObjectDeleteController::class)->middleware(new LoginRequiredMiddleware());
// $router->add("/web-object/(?P<id>\d+)/edit", WebObjectUpdateController::class)->middleware(new LoginRequiredMiddleware());
// $router->get_or_default(Controller404::class);
?>
