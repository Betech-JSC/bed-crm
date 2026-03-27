<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\WebForm;
use App\Models\WebFormField;
use App\Models\WebFormSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class WebFormController extends Controller
{
    // ═══════════════════════════════════════════
    //  ADMIN — CRUD (auth required)
    // ═══════════════════════════════════════════

    public function index(Request $request): Response
    {
        $accountId = Auth::user()->account_id;

        $forms = WebForm::where('account_id', $accountId)
            ->with('creator')
            ->filter($request->only('search', 'status', 'form_type'))
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn ($f) => [
                'id' => $f->id,
                'name' => $f->name,
                'slug' => $f->slug,
                'form_type' => $f->form_type,
                'type_info' => WebForm::getFormTypes()[$f->form_type] ?? null,
                'status' => $f->status,
                'views_count' => $f->views_count,
                'submissions_count' => $f->submissions_count,
                'conversion_rate' => $f->conversion_rate,
                'auto_create_lead' => $f->auto_create_lead,
                'fields_count' => $f->fields()->count(),
                'creator_name' => $f->creator?->name ?? '—',
                'embed_url' => $f->embed_url,
                'created_at' => $f->created_at->format('d/m/Y'),
                'updated_at' => $f->updated_at->diffForHumans(),
            ]);

        $stats = [
            'total' => WebForm::where('account_id', $accountId)->count(),
            'active' => WebForm::where('account_id', $accountId)->where('status', 'active')->count(),
            'total_submissions' => WebFormSubmission::where('account_id', $accountId)->count(),
            'unread' => WebFormSubmission::where('account_id', $accountId)->unread()->count(),
            'today_submissions' => WebFormSubmission::where('account_id', $accountId)->whereDate('created_at', today())->count(),
        ];

        return Inertia::render('WebForms/Index', [
            'forms' => $forms,
            'stats' => $stats,
            'formTypes' => WebForm::getFormTypes(),
            'filters' => $request->only('search', 'status', 'form_type'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('WebForms/Create', [
            'formTypes' => WebForm::getFormTypes(),
            'fieldTypes' => WebFormField::getFieldTypes(),
            'crmMappings' => WebFormField::getCrmMappings(),
            'defaultFields' => $this->getDefaultFields(),
        ]);
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'form_type' => 'required|in:inline,popup,slide_in,floating_bar',
            'success_action' => 'nullable|in:message,redirect,hide',
            'success_message' => 'nullable|string|max:500',
            'redirect_url' => 'nullable|url|max:500',
            'email_notify' => 'boolean',
            'notify_emails' => 'nullable|string',
            'auto_create_lead' => 'boolean',
            'lead_source' => 'nullable|string|max:100',
            'lead_status' => 'nullable|string|max:50',
            'style_settings' => 'nullable|array',
            'trigger_settings' => 'nullable|array',
            'fields' => 'required|array|min:1',
            'fields.*.field_type' => 'required|string',
            'fields.*.label' => 'required|string|max:255',
            'fields.*.name' => 'required|string|max:100',
            'fields.*.is_required' => 'boolean',
            'fields.*.placeholder' => 'nullable|string|max:255',
            'fields.*.crm_mapping' => 'nullable|string',
            'fields.*.options' => 'nullable|array',
            'fields.*.width' => 'nullable|integer',
        ]);

        $user = Auth::user();

        $form = WebForm::create([
            'account_id' => $user->account_id,
            'created_by' => $user->id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'form_type' => $validated['form_type'],
            'success_action' => $validated['success_action'] ?? 'message',
            'success_message' => $validated['success_message'] ?? 'Cảm ơn bạn! Chúng tôi sẽ liên hệ sớm nhất.',
            'redirect_url' => $validated['redirect_url'] ?? null,
            'email_notify' => $validated['email_notify'] ?? true,
            'notify_emails' => $validated['notify_emails'] ?? $user->email,
            'auto_create_lead' => $validated['auto_create_lead'] ?? true,
            'lead_source' => $validated['lead_source'] ?? 'web_form',
            'lead_status' => $validated['lead_status'] ?? 'new',
            'style_settings' => $validated['style_settings'] ?? $this->defaultStyles(),
            'trigger_settings' => $validated['trigger_settings'] ?? null,
        ]);

        foreach ($validated['fields'] as $i => $field) {
            $form->fields()->create([
                'field_type' => $field['field_type'],
                'label' => $field['label'],
                'name' => $field['name'],
                'placeholder' => $field['placeholder'] ?? null,
                'is_required' => $field['is_required'] ?? false,
                'crm_mapping' => $field['crm_mapping'] ?? null,
                'options' => $field['options'] ?? null,
                'width' => $field['width'] ?? 100,
                'sort_order' => $i,
            ]);
        }

        return redirect()->route('web-forms')->with('success', 'Đã tạo form thành công!');
    }

    public function edit(WebForm $webForm): Response
    {
        $webForm->load('fields');

        $submissions = $webForm->submissions()
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'data' => $s->data,
                'status' => $s->status,
                'utm_summary' => $s->utm_summary,
                'page_url' => $s->page_url,
                'ip_address' => $s->ip_address,
                'lead_id' => $s->lead_id,
                'read_at' => $s->read_at?->format('d/m/Y H:i'),
                'created_at' => $s->created_at->format('d/m/Y H:i'),
            ]);

        return Inertia::render('WebForms/Edit', [
            'webForm' => [
                'id' => $webForm->id,
                'name' => $webForm->name,
                'slug' => $webForm->slug,
                'description' => $webForm->description,
                'form_type' => $webForm->form_type,
                'status' => $webForm->status,
                'style_settings' => $webForm->style_settings ?? $this->defaultStyles(),
                'trigger_settings' => $webForm->trigger_settings,
                'success_action' => $webForm->success_action,
                'success_message' => $webForm->success_message,
                'redirect_url' => $webForm->redirect_url,
                'email_notify' => $webForm->email_notify,
                'notify_emails' => $webForm->notify_emails,
                'auto_create_lead' => $webForm->auto_create_lead,
                'lead_source' => $webForm->lead_source,
                'lead_status' => $webForm->lead_status,
                'views_count' => $webForm->views_count,
                'submissions_count' => $webForm->submissions_count,
                'conversion_rate' => $webForm->conversion_rate,
                'embed_url' => $webForm->embed_url,
                'embed_code' => $webForm->embed_code,
                'fields' => $webForm->fields->map(fn ($f) => [
                    'id' => $f->id,
                    'field_type' => $f->field_type,
                    'label' => $f->label,
                    'name' => $f->name,
                    'placeholder' => $f->placeholder,
                    'help_text' => $f->help_text,
                    'is_required' => $f->is_required,
                    'options' => $f->options ?? [],
                    'default_value' => $f->default_value,
                    'crm_mapping' => $f->crm_mapping,
                    'width' => $f->width,
                    'sort_order' => $f->sort_order,
                ]),
            ],
            'submissions' => $submissions,
            'formTypes' => WebForm::getFormTypes(),
            'fieldTypes' => WebFormField::getFieldTypes(),
            'crmMappings' => WebFormField::getCrmMappings(),
        ]);
    }

    public function update(Request $request, WebForm $webForm): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'form_type' => 'required|in:inline,popup,slide_in,floating_bar',
            'status' => 'nullable|in:active,paused,archived',
            'success_action' => 'nullable|in:message,redirect,hide',
            'success_message' => 'nullable|string|max:500',
            'redirect_url' => 'nullable|url|max:500',
            'email_notify' => 'boolean',
            'notify_emails' => 'nullable|string',
            'auto_create_lead' => 'boolean',
            'lead_source' => 'nullable|string|max:100',
            'lead_status' => 'nullable|string|max:50',
            'style_settings' => 'nullable|array',
            'trigger_settings' => 'nullable|array',
            'fields' => 'required|array|min:1',
            'fields.*.field_type' => 'required|string',
            'fields.*.label' => 'required|string|max:255',
            'fields.*.name' => 'required|string|max:100',
            'fields.*.is_required' => 'boolean',
            'fields.*.placeholder' => 'nullable|string|max:255',
            'fields.*.crm_mapping' => 'nullable|string',
            'fields.*.options' => 'nullable|array',
            'fields.*.width' => 'nullable|integer',
        ]);

        $webForm->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'form_type' => $validated['form_type'],
            'status' => $validated['status'] ?? $webForm->status,
            'success_action' => $validated['success_action'] ?? 'message',
            'success_message' => $validated['success_message'] ?? null,
            'redirect_url' => $validated['redirect_url'] ?? null,
            'email_notify' => $validated['email_notify'] ?? true,
            'notify_emails' => $validated['notify_emails'] ?? null,
            'auto_create_lead' => $validated['auto_create_lead'] ?? true,
            'lead_source' => $validated['lead_source'] ?? 'web_form',
            'lead_status' => $validated['lead_status'] ?? 'new',
            'style_settings' => $validated['style_settings'] ?? null,
            'trigger_settings' => $validated['trigger_settings'] ?? null,
        ]);

        // Sync fields
        $webForm->fields()->delete();
        foreach ($validated['fields'] as $i => $field) {
            $webForm->fields()->create([
                'field_type' => $field['field_type'],
                'label' => $field['label'],
                'name' => $field['name'],
                'placeholder' => $field['placeholder'] ?? null,
                'is_required' => $field['is_required'] ?? false,
                'crm_mapping' => $field['crm_mapping'] ?? null,
                'options' => $field['options'] ?? null,
                'width' => $field['width'] ?? 100,
                'sort_order' => $i,
            ]);
        }

        return redirect()->route('web-forms')->with('success', 'Đã cập nhật form!');
    }

    public function destroy(WebForm $webForm): \Illuminate\Http\RedirectResponse
    {
        $webForm->delete();
        return redirect()->route('web-forms')->with('success', 'Đã xóa form.');
    }

    // ═══════════════════════════════════════════
    //  PUBLIC — Embed & Submit (no auth!)
    // ═══════════════════════════════════════════

    /**
     * Render embed form (public, no auth)
     */
    public function embed(string $slug)
    {
        $form = WebForm::where('slug', $slug)
            ->where('status', 'active')
            ->with('fields')
            ->firstOrFail();

        // Track view
        $form->increment('views_count');

        return Inertia::render('WebForms/Embed', [
            'form' => [
                'id' => $form->id,
                'name' => $form->name,
                'description' => $form->description,
                'form_type' => $form->form_type,
                'style_settings' => $form->style_settings ?? $this->defaultStyles(),
                'success_action' => $form->success_action,
                'success_message' => $form->success_message,
                'redirect_url' => $form->redirect_url,
                'fields' => $form->fields->map(fn ($f) => [
                    'name' => $f->name,
                    'field_type' => $f->field_type,
                    'label' => $f->label,
                    'placeholder' => $f->placeholder,
                    'help_text' => $f->help_text,
                    'is_required' => $f->is_required,
                    'options' => $f->options ?? [],
                    'default_value' => $f->default_value,
                    'width' => $f->width,
                ]),
            ],
        ]);
    }

    /**
     * Handle form submission (public, no auth)
     */
    public function submit(Request $request, string $slug): JsonResponse
    {
        $form = WebForm::where('slug', $slug)
            ->where('status', 'active')
            ->with('fields')
            ->firstOrFail();

        // Validate required fields
        $rules = [];
        foreach ($form->fields as $field) {
            $fieldRules = [];
            if ($field->is_required) $fieldRules[] = 'required';
            else $fieldRules[] = 'nullable';

            if ($field->field_type === 'email') $fieldRules[] = 'email';
            if (in_array($field->field_type, ['text', 'textarea'])) $fieldRules[] = 'string|max:2000';

            $rules["fields.{$field->name}"] = implode('|', $fieldRules);
        }

        $request->validate($rules);
        $fieldData = $request->input('fields', []);

        // Create submission
        $submission = WebFormSubmission::create([
            'web_form_id' => $form->id,
            'account_id' => $form->account_id,
            'data' => $fieldData,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'referrer_url' => $request->input('referrer_url'),
            'page_url' => $request->input('page_url'),
            'utm_source' => $request->input('utm_source'),
            'utm_medium' => $request->input('utm_medium'),
            'utm_campaign' => $request->input('utm_campaign'),
            'utm_term' => $request->input('utm_term'),
            'utm_content' => $request->input('utm_content'),
        ]);

        // Auto-create Lead
        if ($form->auto_create_lead) {
            $leadData = [
                'account_id' => $form->account_id,
                'source' => $form->lead_source ?? 'web_form',
                'status' => $form->lead_status ?? 'new',
            ];

            foreach ($form->fields as $field) {
                if (!$field->crm_mapping || empty($fieldData[$field->name])) continue;
                $mapped = str_replace('lead.', '', $field->crm_mapping);
                $leadData[$mapped] = $fieldData[$field->name];
            }

            // Ensure we have at least a name
            if (empty($leadData['company']) && empty($leadData['name'])) {
                $leadData['company'] = $fieldData['company'] ?? $fieldData['name'] ?? 'Web Form Lead';
            }

            $lead = Lead::create($leadData);
            $submission->update(['lead_id' => $lead->id]);
        }

        // Update form stats
        $form->increment('submissions_count');

        return response()->json([
            'success' => true,
            'message' => $form->success_message ?? 'Cảm ơn bạn!',
            'action' => $form->success_action,
            'redirect_url' => $form->redirect_url,
        ]);
    }

    /**
     * Mark submission as read
     */
    public function markRead(WebFormSubmission $submission): JsonResponse
    {
        $submission->update(['read_at' => now(), 'status' => 'contacted']);
        return response()->json(['success' => true]);
    }

    // ── Helpers ──
    private function getDefaultFields(): array
    {
        return [
            ['field_type' => 'text', 'label' => 'Họ tên', 'name' => 'name', 'placeholder' => 'Nhập họ tên...', 'is_required' => true, 'crm_mapping' => 'lead.contact_name', 'width' => 100],
            ['field_type' => 'email', 'label' => 'Email', 'name' => 'email', 'placeholder' => 'email@company.com', 'is_required' => true, 'crm_mapping' => 'lead.email', 'width' => 50],
            ['field_type' => 'phone', 'label' => 'Số điện thoại', 'name' => 'phone', 'placeholder' => '0901234567', 'is_required' => false, 'crm_mapping' => 'lead.phone', 'width' => 50],
            ['field_type' => 'text', 'label' => 'Công ty', 'name' => 'company', 'placeholder' => 'Tên công ty', 'is_required' => false, 'crm_mapping' => 'lead.company', 'width' => 100],
            ['field_type' => 'textarea', 'label' => 'Nội dung', 'name' => 'message', 'placeholder' => 'Bạn cần tư vấn gì?', 'is_required' => false, 'crm_mapping' => 'lead.notes', 'width' => 100],
        ];
    }

    private function defaultStyles(): array
    {
        return [
            'primary_color' => '#8b5cf6',
            'bg_color' => '#ffffff',
            'text_color' => '#1e293b',
            'border_radius' => 12,
            'button_text' => 'Gửi thông tin',
            'heading' => 'Liên hệ với chúng tôi',
            'sub_heading' => 'Để lại thông tin, chúng tôi sẽ tư vấn miễn phí cho bạn',
        ];
    }
}
