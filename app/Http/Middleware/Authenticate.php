<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Authenticate
{
    /**
     * التعامل مع الطلب الوارد والتحقق من وجود مستخدم مصادق عليه.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  mixed  ...$guards
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$guards)
    {
        // إذا لم يكن هناك مستخدم مصادق عليه، يتم إرجاع استجابة خطأ JSON مع حالة 401.
        if (!$request->user()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'غير مصرح: يرجى تسجيل الدخول.'
            ], 401);
        }

        // إذا كان المستخدم موجودًا، نمرر الطلب إلى الخطوة التالية في السلسلة.
        return $next($request);
    }
}
