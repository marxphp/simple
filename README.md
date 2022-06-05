一款支持swoole, workerman, FPM环境的框架

```php
composer create-project max/simple:dev-master
```

## 启动服务

> swoole服务

```php
php bin/swoole.php
```

> workerman服务

```php
php bin/workerman.php start
```

> 内置服务

```php
php bin/fpm.php
```

> FPM模式，将请求指向public/index.php即可

## 区别

使用swoole/workerman支持注解，AOP等特性， FPM模式可以直接卸载AOP包。

## 简单入门

### 路由定义

> swoole/workerman下可以使用注解定义

```php
<?php

namespace App\Controllers;

use Max\HttpServer\Context;
use Max\Routing\Annotations\Controller;
use Max\Routing\Annotations\GetMapping;
use Psr\Http\Message\ResponseInterface;

#[Controller(prefix: '/')]
class IndexController
{
    #[GetMapping(path: '/<id>')]
    public function index(Context $ctx, $id): ResponseInterface
    {
        return $ctx->HTML('Hello, ' . $ctx->input()->get('name', 'MaxPHP!'));
    }
}

```

如上请求`0.0.0.0:8080/1` 会指向`index`方法，控制器方法接收`Context`参数和路由参数，如上路由中的`<id>`的值会被传递给`$id`，`$ctx` 是该请求的上下文，可以使用`$ctx->request`
拿到当前请求实例，控制器方法必须返回`ResponseInterface`实例

> FPM或内置服务下不能使用注解

路由定义在`App\Kernel`类的`map`方法中定义

```php
$router->middleware(TestMiddleware::class)->group(function(Router $router) {
            $router->get('/', [IndexController::class, 'index']);
            $router->get('/test', function(Context $ctx) {
                return $ctx->HTML('new');
            });
        });
```
