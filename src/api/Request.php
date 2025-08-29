<?php

class Request
{
    public string $method;
    public string $path;
    public array $query;
    public array $body;
    public array $params = [];

    public ?array $auth = null;

    public function __construct()
    {
        $this->method = strtoupper($_SERVER['REQUEST_METHOD']);
        $this->path   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
        $this->query  = $_GET ?? [];

        $raw = file_get_contents("php://input");
        $json = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
            $this->body = $json;
        } elseif (!empty($_POST)) {
            $this->body = $_POST;
        } else {
            parse_str($raw, $parsed);
            $this->body = $parsed ?? [];
        }
    }
}
