<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AuditLogController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/AuditLogs/Index', [
            'users' => User::query()->orderBy('name')->get(['id', 'name', 'role']),
            'types' => collect(AuditLog::TYPE_LABELS)
                ->map(fn ($label, $class) => ['value' => $class, 'label' => $label])
                ->values(),
        ]);
    }

    public function list(Request $request)
    {
        $logs = AuditLog::query()
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->integer('user_id')))
            ->when($request->filled('role'), fn ($q) => $q->where('user_role', $request->string('role')->toString()))
            ->when($request->filled('event'), fn ($q) => $q->where('event', $request->string('event')->toString()))
            ->when($request->filled('type'), fn ($q) => $q->where('auditable_type', $request->string('type')->toString()))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('date_to')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = '%' . trim($request->string('search')->toString()) . '%';
                $q->where(fn ($w) => $w->where('auditable_label', 'like', $search)
                    ->orWhere('old_values', 'like', $search)
                    ->orWhere('new_values', 'like', $search));
            })
            ->latest('id')
            ->paginate(50);

        $logs->getCollection()->transform(fn (AuditLog $log) => [
            'id'              => $log->id,
            'date'            => $log->created_at?->format('d/m/Y H:i:s'),
            'user_name'       => $log->user_name ?? 'Système',
            'user_role'       => $log->user_role,
            'event'           => $log->event,
            'type_label'      => AuditLog::TYPE_LABELS[$log->auditable_type] ?? class_basename($log->auditable_type),
            'auditable_id'    => $log->auditable_id,
            'auditable_label' => $log->auditable_label,
            'old_values'      => $log->old_values ?? [],
            'new_values'      => $log->new_values ?? [],
            'ip'              => $log->ip,
            'url'             => $log->url,
        ]);

        return response()->json($logs);
    }
}
