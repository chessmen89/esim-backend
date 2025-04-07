<?php

namespace App\Http\Controllers;

// Import necessary base classes and traits
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

// Ensure your Controller extends BaseController and uses the traits
abstract class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}
