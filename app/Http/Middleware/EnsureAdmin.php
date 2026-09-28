<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
class EnsureAdmin {
    public function handle(Request $request, Closure $next) {
        if (!auth()->check()) return redirect()->guest(route('login'));
        return $next($request);
    }
}
