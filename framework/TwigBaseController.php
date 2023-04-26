<?php
require_once "BaseController.php"; // обязательно импортим BaseController

class TwigBaseController extends BaseController {
    public $title = ""; // название страницы
    public $template = ""; // шаблон страницы
    protected \Twig\Environment $twig; // ссылка на экземпляр twig, для рендернига

    // public function __construct($twig)
    // {
    //     $this->twig = $twig; // пробрасываем его внутрь
    // }
    public function setTwig($twig) {
        $this->twig = $twig;
    }
    
    // переопределяем функцию контекста
    public function getContext() : array
    {
        $context = parent::getContext(); // вызываем родительский метод
        $context['title'] = $this->title;// добавляем title в контекст
        $context['is_logged'] = $_SESSION['is_logged']; 
      //  $context['menu'] = $this->menu;
        return $context;
    }
    
    // функция гет, рендерит результат используя $template в качестве шаблона
    // и вызывает функцию getContext для формирования словаря контекста
    public function get(array $context) {
        echo $this->twig->render($this->template, $context);
    }
}