<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // 1. Luôn để Cors lên đầu (Đã đúng)
        $middleware->prepend(\Illuminate\Http\Middleware\HandleCors::class);

        // 2. QUAN TRỌNG NHẤT: Tin tưởng Proxy của Render
        // Dòng này sẽ dập tắt lỗi "Redirected too many times"
        $middleware->trustProxies(at: '*');

        // 3. XÓA BỎ ForceHttps::class (Vì nó gây vòng lặp trên Render)
        $middleware->api(append: [
            // Đừng cho ForceHttps vào đây nữa
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();