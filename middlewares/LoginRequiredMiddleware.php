<?php

class LoginRequiredMiddleware extends BaseMiddleware {
    public function apply(BaseController $controller, array $context) {
        $is_logged = isset($_SESSION['is_logged']) ? $_SESSION['is_logged'] : false;
        if(!$is_logged){
            // echo "bad";
           // $_SESSION['href_login'] = $_SERVER['HTTP_REFERER'];
            // if (!isset($_SESSION['href_login'])) {
            //     $_SESSION['href_login'] = "/web_object/1";
            // }
            header("Location: /login");
            exit;
        }
        // заводим переменные под правильный пароль
        // $valid_user = "admin";
        // $valid_password = "123";


        // берем значения которые введет пользователь
//         $user = isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : '';
//         $password = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : '';
        
//         $sql = <<<EOL
// SELECT * FROM users
// WHERE username = :username AND password = :password
// EOL;
//         $query=$controller->pdo->prepare($sql);
//         $query->bindValue("username", $user);
//         $query->bindValue("password", $password);
//         $query->execute();

        
//         $context['user'] = $query->fetchAll();
//       //  echo $context['user'][0][1];

//         // сверяем с корректными
//         // if ($valid_user != $user || $valid_password != $password) {
//         //     header('WWW-Authenticate: Basic realm="Space objects"');
//         //     http_response_code(401); // ну и статус 401 -- Unauthorized, то есть неавторизован
//         //     exit; // прерываем выполнение скрипта
//         // }
//         if ($context['user'][0][1] == "") {
//             header('WWW-Authenticate: Basic realm="Space objects"');
//             http_response_code(401); // ну и статус 401 -- Unauthorized, то есть неавторизован
//             exit; // прерываем выполнение скрипта
//         }

    }
}