<?php

class Router {
    private $routes = [];
    private $middleware = [];
    private $groups = [];
    
    public function addMiddleware($middleware) {
        $this->middleware[] = $middleware;
    }
    
    public function get($path, $handler, $options = []) {
        $this->addRoute('GET', $path, $handler, $options);
    }
    
    public function post($path, $handler, $options = []) {
        $this->addRoute('POST', $path, $handler, $options);
    }
    
    public function put($path, $handler, $options = []) {
        $this->addRoute('PUT', $path, $handler, $options);
    }
    
    public function delete($path, $handler, $options = []) {
        $this->addRoute('DELETE', $path, $handler, $options);
    }
    
    public function group($prefix, $callback, $options = []) {
        $previousGroup = $this->groups;
        $this->groups[] = ['prefix' => $prefix, 'options' => $options];
        
        call_user_func($callback, $this);
        
        $this->groups = $previousGroup;
    }
    
    private function addRoute($method, $path, $handler, $options = []) {
        $fullPath = $this->buildFullPath($path);
        $mergedOptions = $this->mergeGroupOptions($options);
        
        $this->routes[] = [
            'method' => $method,
            'path' => $fullPath,
            'handler' => $handler,
            'options' => $mergedOptions,
            'pattern' => $this->buildPattern($fullPath)
        ];
    }
    
    private function buildFullPath($path) {
        $fullPath = $path;
        
        foreach ($this->groups as $group) {
            $fullPath = $group['prefix'] . $fullPath;
        }
        
        return $fullPath;
    }
    
    private function mergeGroupOptions($options) {
        $merged = $options;
        
        foreach ($this->groups as $group) {
            $merged = array_merge($merged, $group['options']);
        }
        
        return $merged;
    }
    
    private function buildPattern($path) {
        $pattern = preg_replace('/\{([^}]+)\}/', '([^/]+)', $path);
        return '#^' . $pattern . '$#';
    }
    
    public function dispatch() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = $this->getRequestPath();
        
        // Log para debug
        error_log("API Request: $method $path");
        
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && preg_match($route['pattern'], $path, $matches)) {
                
                // Executar middleware global apenas se não for rota pública
                if (!isset($route['options']['skip_auth']) || !$route['options']['skip_auth']) {
                    foreach ($this->middleware as $middleware) {
                        $middleware->handle();
                    }
                }
                
                // Extrair parâmetros da URL
                array_shift($matches);
                $params = $matches;
                
                // Executar handler
                if (is_callable($route['handler'])) {
                    return call_user_func_array($route['handler'], $params);
                } else if (is_string($route['handler'])) {
                    list($controller, $method) = explode('@', $route['handler']);
                    require_once "controllers/{$controller}.php";
                    $instance = new $controller();
                    return call_user_func_array([$instance, $method], $params);
                }
            }
        }
        
        // Log da rota não encontrada para debug
        error_log("Route not found: $method $path");
        Response::error('Route not found', 404);
    }
    
    private function getRequestPath() {
        // Para LiteSpeed, verificar diferentes fontes de path
        $path = '';
        
        // Tentar PATH_INFO primeiro (LiteSpeed)
        if (isset($_SERVER['PATH_INFO']) && !empty($_SERVER['PATH_INFO'])) {
            $path = $_SERVER['PATH_INFO'];
        }
        // Senão usar REQUEST_URI
        else {
            $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
            $basePath = API_BASE_URL;
            
            if (strpos($path, $basePath) === 0) {
                $path = substr($path, strlen($basePath));
            }
            
            // Remover index.php se presente
            $path = preg_replace('#^/?index\.php/?#', '/', $path);
        }
        
        return $path ?: '/';
    }
}
?>