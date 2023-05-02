<?php
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once "../framework/TwigBaseController.php";
require_once "../libraries/PHPExcel/PHPExcel.php";

require '../libraries/phpmailer/Exception.php';
require '../libraries/phpmailer/PHPMailer.php';
require '../libraries/phpmailer/SMTP.php';

class MainController extends TwigBaseController {
    public $template = "main.twig";
    public $title;
    public function getContext(): array
    {
        $context = parent::getContext();
        //$context['title']
        if($_SESSION['user']=="student"){ //контекст для студента
            $context['user'] = "student";
            //получаем статусы из бд
            $sql = <<<EOL
SELECT text FROM category
EOL;
            $query=$this->pdo->prepare($sql);
            $query->execute();
            $context['categories'] = $query->fetchAll();

        } else{ //контекст для сотрудника деканата
            $context['requests'] = $this->getUnverifiedRequests();
            $context['user'] = "worker";
            //---
            // $this->sendEamail();
            //---
        }
        return $context;
    }

    public function post(array $context) {
        if($_SESSION['user']=="student") //пост запрос для студента
        { 
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

        } else{ //пост запрос для сотрудника деканата
            if(isset($_POST['form_id'])){
                if($_POST['form_id']==1){
                //echo 'win';
                    // $sum = $_POST['sum'];
                    // $message='';
                    // if(!is_numeric($sum)) { //доход
                    //     $context['message'] = 'Введенная сумма не корректна!';
                    // } else{
                    //     $this->determineRequestsForPayouts();
                    // }
                    $this->saveDataInExcelFile();
                }
            } else{
                $this->updateRequest();
                $this->sendEmailToVerifiedStudent();
                header("Location: /");
                exit;
            }
        }
        $this->get($context);

    }

    public function saveDataInExcelFile(){
        // $array
        // $objExcel = new PHPExcel();
        // $objExcel->setActiveSheetIndex[0];
        // $objWriter = PHPExcel_IOFactory::createWriter($objExcel,'Excel5');
        // $objWriter->save('payouts.xls');
        $myXls = new PHPExcel();
// Указание на активный лист
        $myXls->setActiveSheetIndex(0);
// Получение активного листа
        $mySheet = $myXls->getActiveSheet();
// Указание названия листа книги
        $mySheet->setTitle("Новый лист");

// Указываем значения для отдельных ячеек
        $mySheet->setCellValue("A1", "1-я строка");
        $mySheet->setCellValue("A2", "2-я строка");
        $mySheet->setCellValue("A3", "3-я строка");
        $mySheet->setCellValue("B1", "2-й столбец");
        $objWriter = new PHPExcel_Writer_Excel5($myXls,'Excel5');
        $objWriter->save("test.xls");
    }


    public function sendEmailToVerifiedStudent(){
        $sql = <<<EOL
SELECT s.FIO,r.status,r.email,r.status_result FROM request as r
JOIN studentrequest as sr ON(r.id = sr.request_id)
JOIN student as s ON(sr.student_id=s.number_record)
WHERE r.id =:id
EOL;
        $query=$this->pdo->prepare($sql);
        $query->bindValue("id", $_POST['id']);
        $query->execute();
        $resultSql = $query->fetchAll();
        $title="Результат проверки заявки";
        if($resultSql[0]['status']=='принятая'){
            $body = explode(' ', $resultSql[0]['FIO'])[1] . ", добрый день!" . " Ваша заявка была рассмотрена.<br>Текущий статус заявки: " . $resultSql[0]['status'];
        } else{
            $body = explode(' ', $resultSql[0]['FIO'])[1] . ", добрый день!" . " Ваша заявка была рассмотрена.<br>Текущий статус заявки: " . $resultSql[0]['status'] . ". Причина: " . $resultSql[0]['status_result'];
        }
        $this->sendEamail($resultSql[0]['email'],$body,$title);
    }

    public function determineRequestsForPayouts(){  //определяем кто из студентов получит выплаты
        $minPayout=2000;
        $sum = $_POST['sum'];
        $query = $this->pdo->query("SELECT r.id,r.email,r.status_result,r.status, s.FIO, g.сipher,c.priority FROM request as r
JOIN category as c ON(r.category=c.id)
JOIN studentrequest as sr ON(r.id = sr.request_id)
JOIN student as s ON(sr.student_id=s.number_record)
JOIN payout_db.group as g ON(g.id=s.group_id)
WHERE status = 'принятая' AND status_result IS NULL
group by c.priority, r.income");
        $resultSql = $query->fetchAll();

        if((count($resultSql)*$minPayout)<=$sum){
            $payoutSum = ((float)$sum)/count($resultSql);
            foreach($resultSql as &$request){
                // $this->saveDataInExcelFile();
                $request['status'] = "принятая";
                $request['status_result'] = $payoutSum;
            }

        } else {
            $count =  intdiv($sum,$minPayout);
            foreach($resultSql as $key => &$request){
                if($key<$count){
                    $request['status'] = "принятая";
                    $request['status_result'] = $minPayout;
                } else{
                    $request['status'] = "отклоненная";
                    $request['status_result'] = "Нехватка денежных средств";
                }
            }

        }
        $this->savePayoutData($sum, $resultSql);
        $title="Результат формирования выплат";

        foreach($resultSql as &$request){
            if($request['status'] == "принятая"){
                $body = explode(' ', $request['FIO'])[1] . ", добрый день!" . " Информируем вас о результатах формирования выплат.<br>Вам назначена выплата в размере: " . $request['status_result']. "р.";
            }else{
                $body = explode(' ', $request['FIO'])[1] . ", добрый день!" . " Информируем вас о результатах формирования выплат.<br>Вам отказано в выплате, причина: " . $request['status_result'];
            }
            $this->sendEamail($request['email'],$body,$title);
        }
    }

    public function savePayoutData($sum, $array){ //сохраняем данные о новой выплате и обновляем заявки
        $sql = <<<EOL
INSERT INTO payout(sum, date)
VALUES(:sum, :date)
EOL;
        $query = $this->pdo->prepare($sql);
        $query->bindValue("sum", $sum);
        $query->bindValue("date", date("Y-m-d H:i:s", strtotime("+8 hours")));
        $query->execute();
        $payout_id = $this->pdo->lastInsertId();

        // а вот тут сохраняй все заявки. в $array передавай все заявки, даже отклоненные
        foreach($array as $key => $request){
            $sql = <<<EOL
INSERT INTO specificpayout(payout_id, request_id)
VALUES(:payout_id, :request_id)
EOL;
            $query = $this->pdo->prepare($sql);
            $query->bindValue("payout_id", $payout_id);
            $query->bindValue("request_id", $request['id']);
            $query->execute();
            $sql = <<<EOL
UPDATE request SET status = :status, status_result = :status_result WHERE id = :id
EOL;
            $query = $this->pdo->prepare($sql);
            $query->bindValue("status", $request['status']);
            $query->bindValue("status_result", $request['status_result']);
            $query->bindValue("id", $request['id']);
            $query->execute();
        }

    }

    public function sendEamail($email,$body,$title){
        $mail = new PHPMailer(true);
        
        try {
            //Server settings
            // $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      //Enable verbose debug output
            $mail->CharSet = 'utf-8';
            $mail->isSMTP();                                            //Send using SMTP
            $mail->Host       = 'smtp.mail.ru';                     //Set the SMTP server to send through
            $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
            $mail->Username   = 'irnitu.payout@mail.ru';                     //SMTP username
            $mail->Password   = '5hdUpTuz5DrGvQ7zcAgi';                               //SMTP password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            //Enable implicit TLS encryption
            $mail->Port       = 465;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

            //Recipients
            $mail->setFrom('irnitu.payout@mail.ru');
            $mail->addAddress($email);     //Add a recipient
            //Content
            $mail->isHTML(true);                                  //Set email format to HTML
            $mail->Subject = $title;
            $mail->Body = $body;
            // $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

            $mail->send();
            // echo 'Message has been sent';
        } catch (Exception $e) {
            // echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    }

    public function updateRequest(){
        $status = $_POST['status'];
        if($status=='принятая'){
            $sql = <<<EOL
UPDATE request SET status = :status WHERE id = :id
EOL;
            $query = $this->pdo->prepare($sql);
            $query->bindValue("status", $_POST['status']);
            $query->bindValue("id", $_POST['id']);
            $query->execute();
        }else{
            $sql = <<<EOL
UPDATE request SET status = :status, status_result = :status_result WHERE id = :id
EOL;
            $query = $this->pdo->prepare($sql);
            $query->bindValue("status", $_POST['status']);
            if($_POST['status_result-extended']===""){
                $query->bindValue("status_result", $_POST['status_result']);
            } else{
                $status_result = $_POST['status_result'];
                $status_result.=". ";
                $status_result.=$_POST['status_result-extended'];
                $query->bindValue("status_result", $status_result);
            }
            $query->bindValue("id", $_POST['id']);
            $query->execute();
        }
    }

    public function getUnverifiedRequests():array{ //тягаю из бд непроверенные заявки
        //причем файлы группирую в одну строку, ибо у заявки может быть несколько файлов, но я не хочу получать из-за этого
        //несколько строк с одной и той же информацией (ьудет отличие только в именах файлов)
        $query = $this->pdo->query("SELECT r.id, GROUP_CONCAT(d.name SEPARATOR '*') as documents, r.income, r.category, s.FIO, g.сipher,c.text FROM request as r
JOIN category as c ON(r.category=c.id)
JOIN studentrequest as sr ON(r.id = sr.request_id)
JOIN student as s ON(sr.student_id=s.number_record)
JOIN payout_db.group as g ON(g.id=s.group_id)
JOIN requestdocuments as rd ON(rd.request_id=r.id)
JOIN document as d ON(d.id = rd.document_id)
WHERE status = 'непроверенная' group by r.id");
        $resultSql = $query->fetchAll();
        foreach ($resultSql as $key => &$resultString){
            if(is_string(stristr($resultString['documents'],'*'))){ //если в строке документов несколько файлов
                $array=explode("*", $resultString['documents']); //тогда конвертирую эту строку в массив, по разделителю
                $resultString['documents'] = $array;
            }else{
                //если в строке документов один файл, то конвертирую её в массив из одного элемента
                $str=$resultString['documents']; 
                $resultString['documents']=[];
                $resultString['documents'][]=$str;
            }
            // foreach ($resultString['documents'] as &$arr){
            //     // $arr = mb_strrchr($arr,".",true); //убираю расширение из имени файла
            //     // $arr="gdfgff";
            // }
            //удаляю повторяющиеся элементы массива
            for($i=0;$i<count($resultString);$i++){
                unset($resultString[$i]);
            }
        }
        // echo 'Here is some more debugging info:';
        // print_r($resultSql);
        // print "</pre>";
        return $resultSql;
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

        $documents = $this->uploadFile(); //переименовываю файлы
        foreach ($documents as $key => $document){//сохраняем данные о документах в бд
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
WHERE student_id=:id)  AND (status = 'непроверенная' OR (status = 'принятая' AND status_result IS NULL))
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
                //делим строку по ПОСЛЕДНЕЙ точке в строке
                $tName=mb_strrchr($name,".",true); // имя файла без расширения
                $tName.= "(";
                $tName.= strval($i);
                $tName.= ")";
                $tName.=mb_strrchr($name,".",false);// расширение файла вместе с точкой
                $i++;
            }
            move_uploaded_file($tmp_name, "./documents/$tName");
            // $path = "/documents/$tName";
            $array[]=$tName;
        }
        // echo 'Here is some more debugging info:';
        // print_r($array);
        // print "</pre>";
        return $array;
    }

}
