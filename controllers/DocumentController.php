<?php
require_once "../framework/TwigBaseController.php";
class DocumentController extends TwigBaseController {
    public $template = ""; 
    public function getContext(): array
    {
        $context = parent::getContext();

//         $sql = <<<EOL
// SELECT name FROM document
// WHERE id = :id
// EOL;
//         $query=$this->pdo->prepare($sql);
//         $query->bindValue("id", $this->params['document']);
//         $query->execute();
//         $queryResult = $query->fetchAll();
//         $file_name = $queryResult[0]['name'];
        // print_r( $file_name);
        $file_name= $_POST['document'];
        $file_path = "..\documents\\${file_name}";
        header('Content-Type: application/pdf');
        readfile($file_path);

        return $context;
    }

    public function post(array $context){
        // echo 'postZapros:';
        // print_r($_POST['document']);
        // print "</pre>";
        $this->get($context);
    }

}