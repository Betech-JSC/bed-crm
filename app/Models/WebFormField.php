<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebFormField extends Model
{
    protected $fillable = [
        'web_form_id', 'field_type', 'label', 'name', 'placeholder',
        'help_text', 'is_required', 'options', 'default_value',
        'validation_rule', 'crm_mapping', 'width', 'sort_order',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'options' => 'array',
    ];

    public static function getFieldTypes(): array
    {
        return [
            'text' => ['label' => 'Text', 'icon' => 'pi pi-pencil'],
            'email' => ['label' => 'Email', 'icon' => 'pi pi-envelope'],
            'phone' => ['label' => 'Số điện thoại', 'icon' => 'pi pi-phone'],
            'textarea' => ['label' => 'Textarea', 'icon' => 'pi pi-align-left'],
            'select' => ['label' => 'Dropdown', 'icon' => 'pi pi-chevron-down'],
            'checkbox' => ['label' => 'Checkbox', 'icon' => 'pi pi-check-square'],
            'radio' => ['label' => 'Radio', 'icon' => 'pi pi-circle'],
            'number' => ['label' => 'Số', 'icon' => 'pi pi-hashtag'],
            'date' => ['label' => 'Ngày', 'icon' => 'pi pi-calendar'],
            'hidden' => ['label' => 'Hidden', 'icon' => 'pi pi-eye-slash'],
        ];
    }

    public static function getCrmMappings(): array
    {
        return [
            '' => 'Không ánh xạ',
            'lead.company' => 'Tên công ty',
            'lead.contact_name' => 'Họ tên',
            'lead.email' => 'Email',
            'lead.phone' => 'Số điện thoại',
            'lead.website' => 'Website',
            'lead.notes' => 'Ghi chú',
            'lead.source' => 'Nguồn',
        ];
    }

    public function form(): BelongsTo { return $this->belongsTo(WebForm::class, 'web_form_id'); }
}
