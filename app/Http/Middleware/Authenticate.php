<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo($request): ?string
    {
        // اگر درخواست JSON یا API باشد، اصلاً ریدایرکت نکن تا خود لاراول خطای 401 unauthenticated بدهد
        if ($request->expectsJson() || $request->is('api/*')) {
            return null;
        }

        return null; // یا اگر هیچ روت وبی نداری، کلاً همین null را برگردان
    }
}
