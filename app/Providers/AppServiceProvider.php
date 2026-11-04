<?php
namespace App\Providers;
use Illuminate\Support\ServiceProvider;
class AppServiceProvider extends ServiceProvider {
 public function register(): void {}
 public function boot(): void {
  if (env('VERCEL')) {
   \Illuminate\Support\Facades\URL::forceScheme('https');
  }
 }
}
