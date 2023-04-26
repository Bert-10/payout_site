<?php
abstract class BaseController {
    public PDO $pdo;
    public array $params;
    public function setParams(array $params) {
        $this->params = $params;
    }

    public function setPDO(PDO $pdo) { // и сеттер для него
        $this->pdo = $pdo;
    }
    public function getContext(): array {
        return []; // по умолчанию пустой контекст
    }
    public function process_response() {

        $method = $_SERVER['REQUEST_METHOD'];
        $context = $this->getContext(); 
        if ($method == 'GET') { // если GET запрос то вызываем get
            $this->get($context);
        } else if ($method == 'POST') { // если POST запрос то вызываем get
            $this->post($context);
        }
    }

    public function get(array $context) {}
    public function post(array $context) {}
}