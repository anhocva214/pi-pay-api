<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
class CheckTokenKey
{
   
    protected $mainResponsitory;
    public function __construct()
    {
   
    }
    public function handle(Request $request, Closure $next)
    {
        $params = $request->all();
        $tokenKey = $params['token_key'] ?? "";
        if(!$tokenKey) {
            return redirect()->route('api/permmissions/index',['msg' => 'Bạn chưa nhập Token']);
        }
        return $next($request);
    }
}
