<?php

namespace App\Http\Controllers;

use App\Models\SystemActivityLog;
use App\Models\Lead;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Customer;
use App\Models\User;
use App\Models\Organization;
use App\Models\Proposal;
use App\Models\Project;
use App\Models\WikiArticle;
use App\Models\SalesPlaybook;
use App\Models\Activity;
use App\Models\SocialPost;
use App\Models\EmailTemplate;
use App\Models\EmailCampaign;
use App\Models\ContentItem;
use App\Models\Workflow;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SystemHistoryController extends Controller
{
    /**
     * Activity History page
     */
    public function history(Request $request)
    {
        $query = SystemActivityLog::with('user')
            ->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('subject_label', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($q2) use ($search) {
                      $q2->where('first_name', 'like', "%{$search}%")
                          ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->paginate(50)->through(function ($log) {
            return [
                'id' => $log->id,
                'action' => $log->action,
                'action_label' => $log->action_label,
                'module' => $log->module,
                'module_label' => $log->module_label,
                'subject_label' => $log->subject_label,
                'subject_type' => $log->subject_type,
                'subject_id' => $log->subject_id,
                'changes' => $log->changes,
                'ip_address' => $log->ip_address,
                'created_at' => $log->created_at->format('d/m/Y H:i'),
                'created_at_human' => $log->created_at->diffForHumans(),
                'user' => $log->user ? [
                    'id' => $log->user->id,
                    'name' => $log->user->name,
                    'first_name' => $log->user->first_name,
                    'last_name' => $log->user->last_name,
                    'photo' => $log->user->photo ?? null,
                ] : null,
            ];
        });

        // Stats
        $todayCount = SystemActivityLog::whereDate('created_at', today())->count();
        $weekCount = SystemActivityLog::where('created_at', '>=', now()->startOfWeek())->count();

        // Users for filter
        $users = User::select('id', 'first_name', 'last_name')
            ->orderBy('first_name')
            ->get()
            ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name]);

        return Inertia::render('SystemHistory/Index', [
            'logs' => $logs,
            'filters' => $request->only(['search', 'module', 'action', 'user_id', 'date_from', 'date_to']),
            'stats' => [
                'today' => $todayCount,
                'week' => $weekCount,
                'total' => SystemActivityLog::count(),
            ],
            'users' => $users,
            'modules' => $this->getAvailableModules(),
            'actions' => $this->getAvailableActions(),
        ]);
    }

    /**
     * System Trash page — show all soft-deleted records
     */
    public function trash(Request $request)
    {
        $module = $request->input('module', 'all');
        $search = $request->input('search');

        $trashItems = collect();

        $models = $this->getTrashableModels();

        if ($module !== 'all') {
            $models = collect($models)->where('key', $module)->all();
        }

        foreach ($models as $config) {
            $modelClass = $config['model'];
            if (!class_exists($modelClass)) continue;

            $query = $modelClass::onlyTrashed();

            if ($search && method_exists($modelClass, 'scopeSearch')) {
                $query->search($search);
            } elseif ($search) {
                $nameField = $config['name_field'] ?? 'name';
                $query->where($nameField, 'like', "%{$search}%");
            }

            $items = $query->latest('deleted_at')->take(100)->get();

            foreach ($items as $item) {
                $nameField = $config['name_field'] ?? 'name';
                $trashItems->push([
                    'id' => $item->id,
                    'module' => $config['key'],
                    'module_label' => $config['label'],
                    'module_icon' => $config['icon'],
                    'name' => $item->{$nameField} ?? "#{$item->id}",
                    'deleted_at' => $item->deleted_at->format('d/m/Y H:i'),
                    'deleted_at_human' => $item->deleted_at->diffForHumans(),
                    'model_type' => get_class($item),
                ]);
            }
        }

        // Sort by deleted_at desc
        $trashItems = $trashItems->sortByDesc('deleted_at')->values();

        // Module counts
        $moduleCounts = [];
        foreach ($this->getTrashableModels() as $config) {
            $modelClass = $config['model'];
            if (!class_exists($modelClass)) continue;
            $count = $modelClass::onlyTrashed()->count();
            if ($count > 0) {
                $moduleCounts[] = [
                    'key' => $config['key'],
                    'label' => $config['label'],
                    'icon' => $config['icon'],
                    'count' => $count,
                ];
            }
        }

        return Inertia::render('SystemHistory/Trash', [
            'items' => $trashItems,
            'filters' => $request->only(['module', 'search']),
            'moduleCounts' => $moduleCounts,
            'totalTrashed' => $trashItems->count(),
        ]);
    }

    /**
     * Restore a soft-deleted record
     */
    public function restore(Request $request)
    {
        $request->validate([
            'model_type' => 'required|string',
            'id' => 'required|integer',
        ]);

        $modelClass = $request->model_type;
        if (!class_exists($modelClass)) {
            return back()->with('error', 'Model không tồn tại.');
        }

        $item = $modelClass::onlyTrashed()->findOrFail($request->id);
        $item->restore();

        // Log restore
        $nameField = $this->getNameFieldForModel($modelClass);
        SystemActivityLog::log('restored', $this->getModuleKeyForModel($modelClass), $item, $item->{$nameField} ?? "#{$item->id}");

        return back()->with('success', 'Đã khôi phục thành công.');
    }

    /**
     * Permanently delete a soft-deleted record
     */
    public function forceDelete(Request $request)
    {
        $request->validate([
            'model_type' => 'required|string',
            'id' => 'required|integer',
        ]);

        $modelClass = $request->model_type;
        if (!class_exists($modelClass)) {
            return back()->with('error', 'Model không tồn tại.');
        }

        $item = $modelClass::onlyTrashed()->findOrFail($request->id);

        // Log before delete
        $nameField = $this->getNameFieldForModel($modelClass);
        SystemActivityLog::log('force_deleted', $this->getModuleKeyForModel($modelClass), null, $item->{$nameField} ?? "#{$item->id}");

        $item->forceDelete();

        return back()->with('success', 'Đã xóa vĩnh viễn.');
    }

    // ── Private Helpers ──

    private function getTrashableModels(): array
    {
        return [
            ['key' => 'leads', 'label' => 'Leads', 'icon' => 'pi pi-bullseye', 'model' => Lead::class, 'name_field' => 'company'],
            ['key' => 'contacts', 'label' => 'Liên hệ', 'icon' => 'pi pi-id-card', 'model' => Contact::class, 'name_field' => 'first_name'],
            ['key' => 'deals', 'label' => 'Thương vụ', 'icon' => 'pi pi-briefcase', 'model' => Deal::class, 'name_field' => 'name'],
            ['key' => 'customers', 'label' => 'Khách hàng', 'icon' => 'pi pi-heart', 'model' => Customer::class, 'name_field' => 'company_name'],
            ['key' => 'organizations', 'label' => 'Tổ chức', 'icon' => 'pi pi-building', 'model' => Organization::class, 'name_field' => 'name'],
            ['key' => 'proposals', 'label' => 'Đề xuất', 'icon' => 'pi pi-file-edit', 'model' => Proposal::class, 'name_field' => 'title'],
            ['key' => 'projects', 'label' => 'Dự án', 'icon' => 'pi pi-folder', 'model' => Project::class, 'name_field' => 'name'],
            ['key' => 'wiki', 'label' => 'Wiki', 'icon' => 'pi pi-book', 'model' => WikiArticle::class, 'name_field' => 'title'],
            ['key' => 'playbooks', 'label' => 'Playbooks', 'icon' => 'pi pi-book', 'model' => SalesPlaybook::class, 'name_field' => 'name'],
            ['key' => 'activities', 'label' => 'Hoạt động', 'icon' => 'pi pi-calendar', 'model' => Activity::class, 'name_field' => 'description'],
            ['key' => 'social_posts', 'label' => 'Bài đăng MXH', 'icon' => 'pi pi-send', 'model' => SocialPost::class, 'name_field' => 'content'],
            ['key' => 'email_templates', 'label' => 'Mẫu email', 'icon' => 'pi pi-envelope', 'model' => EmailTemplate::class, 'name_field' => 'name'],
            ['key' => 'content_items', 'label' => 'Nội dung', 'icon' => 'pi pi-pencil', 'model' => ContentItem::class, 'name_field' => 'title'],
            ['key' => 'workflows', 'label' => 'Workflows', 'icon' => 'pi pi-sitemap', 'model' => Workflow::class, 'name_field' => 'name'],
        ];
    }

    private function getAvailableModules(): array
    {
        return [
            ['value' => 'leads', 'label' => 'Leads'],
            ['value' => 'contacts', 'label' => 'Liên hệ'],
            ['value' => 'deals', 'label' => 'Thương vụ'],
            ['value' => 'customers', 'label' => 'Khách hàng'],
            ['value' => 'users', 'label' => 'Người dùng'],
            ['value' => 'organizations', 'label' => 'Tổ chức'],
            ['value' => 'proposals', 'label' => 'Đề xuất'],
            ['value' => 'projects', 'label' => 'Dự án'],
            ['value' => 'wiki', 'label' => 'Wiki'],
            ['value' => 'settings', 'label' => 'Cài đặt'],
            ['value' => 'email', 'label' => 'Email'],
            ['value' => 'social', 'label' => 'Mạng xã hội'],
        ];
    }

    private function getAvailableActions(): array
    {
        return [
            ['value' => 'created', 'label' => 'Tạo mới'],
            ['value' => 'updated', 'label' => 'Cập nhật'],
            ['value' => 'deleted', 'label' => 'Xóa'],
            ['value' => 'restored', 'label' => 'Khôi phục'],
            ['value' => 'login', 'label' => 'Đăng nhập'],
            ['value' => 'logout', 'label' => 'Đăng xuất'],
            ['value' => 'exported', 'label' => 'Xuất dữ liệu'],
            ['value' => 'imported', 'label' => 'Nhập dữ liệu'],
        ];
    }

    private function getNameFieldForModel(string $modelClass): string
    {
        $map = collect($this->getTrashableModels())->keyBy('model');
        return $map[$modelClass]['name_field'] ?? 'name';
    }

    private function getModuleKeyForModel(string $modelClass): string
    {
        $map = collect($this->getTrashableModels())->keyBy('model');
        return $map[$modelClass]['key'] ?? 'system';
    }
}
