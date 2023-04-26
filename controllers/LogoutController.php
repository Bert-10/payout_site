<?php
require_once "../framework/TwigBaseController.php";
class LogoutController extends TwigBaseController {
    public $template = "login.twig"; 
    public function getContext() : array{
        $context=parent::getContext();
        $_SESSION['is_logged'] = false;
        $_SESSION['user']="no";
        header("Location: /login");
        return $context;
    }
}