<?php

class LoginRequiredMiddleware extends BaseMiddleware {
    public function apply(BaseController $controller, array $context) {
        $is_logged = isset($_SESSION['is_logged']) ? $_SESSION['is_logged'] : false;
        if(!$is_logged){
            header("Location: /login");
            exit;
        }
    }
}