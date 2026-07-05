<?php
namespace App\Core;

class Router {
    private $routes = [];
    private $currentRoute = null;
    private $notFoundHandler = null;
    
    public function add($method, $uri, $controller, $action, $middleware = []) {
        $this->routes[] = [
            'method' => $method,
            'uri' => $uri,
            'controller' => $controller,
            'action' => $action,
            'middleware' => $middleware
        ];
    }
    
    public function get($uri, $controller, $action, $middleware = []) {
        $this->add('GET', $uri, $controller, $action, $middleware);
    }
    
    public function post($uri, $controller, $action, $middleware = []) {
        $this->add('POST', $uri, $controller, $action, $middleware);
    }
    
    public function put($uri, $controller, $action, $middleware = []) {
        $this->add('PUT', $uri, $controller, $action, $middleware);
    }
    
    public function delete($uri, $controller, $action, $middleware = []) {
        $this->add('DELETE', $uri, $controller, $action, $middleware);
    }

    public function setNotFound($callback) {
        $this->notFoundHandler = $callback;
    }
    
    public function dispatch() {
        $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
        $uri = isset($_SERVER['REQUEST_URI']) ? parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) : '/';
        
        // Check for API routes
        if (strpos($uri, '/api/') === 0) {
            $this->dispatchApi($method, $uri);
            return;
        }
        
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) continue;
            
            $pattern = preg_replace('/\{[a-zA-Z_]+\}/', '([a-zA-Z0-9_]+)', $route['uri']);
            $pattern = '#^' . $pattern . '$#';
            
            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches);
                $this->currentRoute = $route;
                
                // Apply middleware
                if (!empty($route['middleware'])) {
                    foreach ($route['middleware'] as $middleware) {
                        $middlewareInstance = new $middleware();
                        if ($middlewareInstance->handle() === false) {
                            return;
                        }
                    }
                }
                
                $controllerClass = "App\\Controllers\\" . $route['controller'];
                $controller = new $controllerClass();
                
                return call_user_func_array([$controller, $route['action']], $matches);
            }
        }
        
        header('HTTP/1.0 404 Not Found');
        if ($this->notFoundHandler !== null) {
            call_user_func($this->notFoundHandler);
            return;
        }
        include __DIR__ . '/../../views/errors/404.php';
    }
    
    private function dispatchApi($method, $uri) {
        header('Content-Type: application/json');
        
        // Simple API routing
        $path = str_replace('/api/', '', $uri);
        $parts = explode('/', $path);
        $resource = $parts[0] ?? '';
        $id = $parts[1] ?? null;
        
        $controller = new App\Controllers\ApiController();
        
        switch ($method) {
            case 'GET':
                if ($id) {
                    $response = $controller->get($resource, $id);
                } else {
                    $response = $controller->getAll($resource);
                }
                break;
            case 'POST':
                $data = json_decode(file_get_contents('php://input'), true);
                $response = $controller->create($resource, $data);
                break;
            case 'PUT':
                $data = json_decode(file_get_contents('php://input'), true);
                $response = $controller->update($resource, $id, $data);
                break;
            case 'DELETE':
                $response = $controller->delete($resource, $id);
                break;
            default:
                http_response_code(405);
                $response = ['error' => 'Method not allowed'];
        }
        
        echo json_encode($response);
        exit;
    }
}