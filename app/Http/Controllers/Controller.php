<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function applySearch($query, $search, $columns)
    {
        return $query->where(function ($query) use ($columns, $search) {
            foreach ($columns as $value) {
                $query->orWhere($value, 'LIKE', '%' . $search . '%');
            }
        });
    }
}
