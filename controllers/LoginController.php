<?php
require_once "../framework/TwigBaseController.php";
class LoginController extends TwigBaseController {
    public $template = "login.twig"; 
    public function post(array $context){

        // $_SESSION['welcome_message'] = $_GET['message'];

        // if(!isset($_SESSION['messages'])){
        //     $_SESSION['messages']=[];
        // }
        // array_push( $_SESSION['messages'], $_GET['message']);
        
        // $url = $_SERVER['HTTP_REFERER'];
        // header("Location: $url");
        // exit;
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
       // if ($context['user'][0][1] == "") {
          //  header('WWW-Authenticate: Basic realm="Web objects"');
         //   http_response_code(401); // ну и статус 401 -- Unauthorized, то есть неавторизован
         //   echo "не залогинен";
            $_SESSION['is_logged'] = false;
            $_SESSION['user']="no";
            header("Location: /login");

          //  exit; // прерываем выполнение скрипта
        }else{            
            // echo '<script language="javascript">';
            // echo 'alert("$_SESSION['is_logged']")';
            // echo '</script>';
            $_SESSION['is_logged'] = true;
            
            if(!isset($context['user'][0][2])){
              $_SESSION['user']="worker";
              //                           echo '<script language="javascript">';
              // echo 'alert("worker")';
              // echo '</script>';
            } else{
              $_SESSION['user']="student";
              //                           echo '<script language="javascript">';
              // echo 'alert("student")';
              // echo '</script>';
              
            }

          //  $url =  $_SESSION['href_login'];
          //  header("Location: $url");
            header("Location: /");
        }
        //$_SESSION["is_logged"] = true
    }
}