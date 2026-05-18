<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::query()->orderByDesc('id');

        if ($entity = $request->string('entity')->toString()) {
            $query->where('entity', $entity);
        }
        if ($userId = $request->integer('user_id')) {
            $query->where('user_id', $userId);
        }
        if ($action = $request->string('action')->toString()) {
            $query->where('action', 'like', "%{$action}%");
        }

        $logs = $query->paginate(50)->withQueryString();
        $entities = AuditLog::query()->select('entity')->distinct()->orderBy('entity')->pluck('entity')->filter()->values();
        $users = User::orderBy('name')->get(['id', 'name', 'email']);

        return view('audit.index', compact('logs', 'entities', 'users'));
    }
}
