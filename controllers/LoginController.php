<?php
require_once "../framework/TwigBaseController.php";
class LoginController extends TwigBaseController {
    public $template = "login.twig"; 
    public function post(array $context){

        $user = isset($_POST['login']) ? $_POST['login'] : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        
        $sql = <<<EOL
SELECT * FROM users
WHERE username = :username AND password = :password
EOL;
        $query=$this->pdo->prepare($sql);
        $query->bindValue("username", $user);
        $query->bindValue("password", $password);
        $query->execute();

        $context['user'] = $query->fetchAll();

        if (!isset($_SESSION['is_logged'])) {
            $_SESSION['is_logged'] = false;
        }
        if (!isset($context['user'][0])) {
         //   http_response_code(401); // ну и статус 401 -- Unauthorized, то есть неавторизован
            $_SESSION['is_logged'] = false;
            $_SESSION['user']="no";
            header("Location: /login");
        }else{            
            $_SESSION['is_logged'] = true;
            
            if(!isset($context['user'][0][3])){
              $_SESSION['user']="worker";
            } else{
              $_SESSION['user']="student";
              $_SESSION['id']=$context['user'][0][3];
            }
            header("Location: /");
        }

    }
}