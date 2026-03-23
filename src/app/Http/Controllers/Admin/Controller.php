<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller as BaseController;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Gate;

/**
 * Admin ガード用の基底 Controller。
 * $this->authorize() が admin guard のユーザーで Policy を評価するよう上書きする。
 */
abstract class Controller extends BaseController
{
    /**
     * @throws AuthorizationException
     */
    public function authorize($ability, $arguments = []): Response
    {
        return Gate::forUser(auth('admin')->user())->authorize($ability, $arguments);
    }
}
