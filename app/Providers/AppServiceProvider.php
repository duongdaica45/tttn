<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\App;
use Carbon\Carbon;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Để trống hoặc giữ các bind dịch vụ khác
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        Carbon::setLocale('vi');
        // Chuyển đoạn code này xuống hàm boot
        if (App::environment(['production', 'staging'])) {
            URL::forceScheme('https');
        }
    }
}
