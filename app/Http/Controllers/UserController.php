<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function getAdmins()
    {
        return $this->success(
            User::role(['admin', 'storeManager'])
                ->select('id', 'name')
                ->get()
        );
    }
}
