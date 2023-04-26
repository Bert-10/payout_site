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
            $context['title']=$this->studentInterface();
            $context['user'] = "student";
        } else{
            $context['title']=$this->workerInterface();
            $context['user'] = "worker";
        }
        //$this->title="nothinh";
        // if(isset($_GET['type']))
        // {
        //     $query = $this->pdo->prepare("SELECT * FROM web_objects where type= :type");
        //     $query->bindValue("type",$_GET['type']);
        //     $query->execute();
        // } else {
        //     $query = $this->pdo->query("SELECT * FROM web_objects");
        // }

        // $context['web_objects'] = $query->fetchAll();
        return $context;
    }
    public function studentInterface():string{
        //$this->title=;
        return "student";
    }
    public function workerInterface():string{
        //$this->title="worker";
        return "worker";
    }
}
