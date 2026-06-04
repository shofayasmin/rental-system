<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LoginAudit;

class AdminLoginAuditController extends Controller
{
    public function index()
    {
        $logs = LoginAudit::with('user')
            ->orderBy('logged_in_at', 'desc')
            ->paginate(10);

        return view('admin.login_audit.index', compact('logs'));
    }

    

}
