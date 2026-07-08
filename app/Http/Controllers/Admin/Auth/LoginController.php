<?php

namespace App\Http\Controllers\Admin\Auth;

use Backpack\CRUD\app\Http\Controllers\Auth\LoginController as BackpackLoginController;

class LoginController extends BackpackLoginController
{
    public function __construct()
    {
        parent::__construct();

        $this->redirectTo = backpack_url('destination');
    }
}
