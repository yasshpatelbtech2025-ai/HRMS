<?php

namespace App\Http\Controllers;

use App\Support\RecentlyDeleted;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;

abstract class Controller
{
    /**
     * Back, with a "deleted" toast that has Undo (Configs › Recently deleted).
     */
    protected function deleted(Model $model, string $message): RedirectResponse
    {
        return back()->with('status', $message)->with('undo', RecentlyDeleted::restoreUrl($model));
    }
}
