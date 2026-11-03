<?php
namespace App\Core;

class Router {
  private array $routes = [];
  public function add(string $method, string $path, string $handler): void {
    $this->routes[] = [$method, '#^' . preg_replace('#\{\w+\}#', '(\d+)', $path) . '$#', $handler];
  }
  public function dispatch(): void {
    $uri  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $path = '/' . trim(substr($uri, strlen(base_url())), '/');
    foreach ($this->routes as [$m, $re, $handler]) {
      if ($m === $_SERVER['REQUEST_METHOD'] && preg_match($re, $path, $mt)) {
        [$c, $a] = explode('@', $handler);
        $cls = 'App\\Controllers\\' . $c;
        (new $cls)->$a(...array_map('intval', array_slice($mt, 1)));
        return;
      }
    }
    http_response_code(404);
    echo 'Página não encontrada.';
  }
}
