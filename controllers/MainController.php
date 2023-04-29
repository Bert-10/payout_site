<?php
require_once "../framework/TwigBaseController.php";

class MainController extends TwigBaseController {
    public $template = "main.twig";
    public $title;
    public function getContext(): array
    {
        $context = parent::getContext();
        //$context['title']
        if($_SESSION['user']=="student"){
            // $context['title']=$this->studentInterface();
            $context['user'] = "student";
            //получаем статусы из бд
            $sql = <<<EOL
SELECT text FROM category
EOL;
            $query=$this->pdo->prepare($sql);
            $query->execute();
            $context['categories'] = $query->fetchAll();

        } else{
            // $context['title']=$this->workerInterface();
            $context['user'] = "worker";
        }
        return $context;
    }

    public function post(array $context) {
        if($_SESSION['user']=="student"){
            ['message'=>$message,'result'=>$result] =$this->isDataCorrect();
            if($result){
                if(!$this->isRequestExists()){
                    
                    $this->saveData();
                    // $context['message']=$_POST['category'];
                    $context['message'] = 'Ваша заявка принята к рассмотрению! На электронную почту, указанную вами в заявке, придёт письмо с результом определения выплат.';
                }else{
                    $context['message'] = 'Вы уже отправляли заявку в этом рассчетном периоде!';
                }
            }else{
                $context['message'] = $message;
            }

        } else{
       
        }
        $this->get($context);

    }

    public function saveData(){ //сохраняем данные, отправленные пользователем
        $sql = <<<EOL
INSERT INTO request( category, income, email)
VALUES((select id from category where text=:category), :income, :email)
EOL;
        $query = $this->pdo->prepare($sql);
        // привязываем параметры
        $query->bindValue("category", $_POST['category']);
        $query->bindValue("income", $_POST['income']);
        $query->bindValue("email", $_POST['email']);
        $query->execute();

        $request_id = $this->pdo->lastInsertId();
        $sql = <<<EOL
INSERT INTO studentrequest(student_id,request_id)
VALUES(:student_id, :request_id)
EOL;
        $query = $this->pdo->prepare($sql);
        $query->bindValue("request_id", $request_id);
        $query->bindValue("student_id", $_SESSION['id']);
        $query->execute();

        $documents = $this->uploadFile();
        foreach ($documents as $key => $document){
            $sql = <<<EOL
INSERT INTO document(name) VALUES(:name)
EOL;
                    $query = $this->pdo->prepare($sql);        
                    $query->bindValue("name", $document);
                    $query->execute();
                    $document_id = $this->pdo->lastInsertId();
                    $sql = <<<EOL
INSERT INTO requestdocuments(request_id, document_id)
VALUES(:request_id, :document_id)
EOL;
                    $query = $this->pdo->prepare($sql);           
                    $query->bindValue("request_id", $request_id);
                    $query->bindValue("document_id", $document_id);
                    $query->execute();
        }

    }

    //проверяю данные на корректность
    public function isDataCorrect():array{
        $income = $_POST['income'];
        $email = $_POST['email'];
        $category = $_POST['category'];
        $result=true;
        $message='';
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)){ //почту
            $message = 'Введенный email не корректен!';
            $result=false;
        }else if(!is_numeric($income)) { //доход
            $message = 'Введенный доход не корректен!';
            $result=false;
        }else if(count($_FILES['documents']['tmp_name'])<1){ //файлы прикрелены?
            $message = 'Вы не прикрепили документы!';
            $result=false;
        }else if(count($_FILES['documents']['tmp_name'])>3){
            $message = 'Нужно прикрепить не более трех документов!'; //количество файлов не больше 3
            $result=false;
        } else if(!$this->isFilesExtensionsCorrect()){ //расширение файлов
            $message = 'Принимаются только документы в формате pdf!';
            $result=false;
        }
        $res=['message'=>$message, 'result' => $result];
        return $res;
    }

    public function isRequestExists():bool{ //если заявка уже существует, то вернет true
        $result = true;  
        $sql = <<<EOL
SELECT * FROM request
WHERE id = (SELECT request_id FROM studentrequest
WHERE student_id=:id)  AND (status = 'непроверенная' OR (status = 'принята' AND status_result IS NULL))
EOL;
        $query=$this->pdo->prepare($sql);
        $query->bindValue("id", $_SESSION['id']);
        $query->execute();
        $resultSql = $query->fetchAll();
        if(!isset($resultSql[0])){
            $result=false; //если не было найдено ни одной строки, то возращаем false
        }
        return $result;
    }

    //проверяю корректное ли расширение у файлов
    public function isFilesExtensionsCorrect():bool{
        $result = true;
        foreach ($_FILES['documents']['tmp_name'] as $key => $error) {
            if(mb_strrchr($_FILES['documents']['name'][$key],".",false)!=".pdf"){
                $result = false;
                break;
            }
        }
        return $result;
    }
    //переименовываю  полученные файлы (если это необходимо) и перемещаю их в папку documents
    public function uploadFile():array{
        $array=[];
        foreach ($_FILES['documents']['tmp_name'] as $key => $error) {
            $tmp_name = $_FILES['documents']['tmp_name'][$key];
            $name =  basename($_FILES['documents']['name'][$key]);
            $i=1;
            $tName=$name;
            while(file_exists("../documents/$tName")){
                $tName=mb_strrchr($name,".",true); // имя файла без расширения
                $tName.= "(";
                $tName.= strval($i);
                $tName.= ")";
                $tName.=mb_strrchr($name,".",false);// расширение файла
                $i++;
            }
            move_uploaded_file($tmp_name, "../documents/$tName");
            // $path = "/documents/$tName";
            $array[]=$tName;
        }
        // echo 'Here is some more debugging info:';
        // print_r($array);
        // print "</pre>";
        return $array;
    }

    public function postWorker():array{
        //$this->title="worker";
        return "worker";
    }
}
