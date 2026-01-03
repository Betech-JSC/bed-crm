<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Workflow extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Trigger event types
     */
    public const TRIGGER_LEAD_CREATED = 'lead.created';
    public const TRIGGER_LEAD_SCORED = 'lead.scored';
    public const TRIGGER_LEAD_STATUS_CHANGED = 'lead.status_changed';
    public const TRIGGER_DEAL_CREATED = 'deal.created';
    public const TRIGGER_DEAL_STAGE_CHANGED = 'deal.stage_changed';
    public const TRIGGER_ACTIVITY_CREATED = 'activity.created';

    /**
     * Action types
     */
    public const ACTION_ASSIGN_USER = 'assign_user';
    public const ACTION_SEND_EMAIL = 'send_email';
    public const ACTION_CREATE_TASK = 'create_task';
    public const ACTION_UPDATE_FIELD = 'update_field';
    public const ACTION_ADD_TAG = 'add_tag';
    public const ACTION_CREATE_DEAL = 'create_deal';

    protected $fillable = [
        'account_id',
        'name',
        'description',
        'trigger',
        'actions',
        'is_active',
        'execution_count',
    ];

    protected $casts = [
        'trigger' => 'array',
        'actions' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Workflow belongs to Account
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Check if workflow should be triggered
     */
    public function shouldTrigger(string $event, $subject, array $data = []): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $trigger = $this->trigger;
        if (!$trigger || ($trigger['event'] ?? null) !== $event) {
            return false;
        }

        // Check conditions
        if (isset($trigger['conditions'])) {
            return $this->checkConditions($trigger['conditions'], $subject, $data);
        }

        return true;
    }

    /**
     * Check trigger conditions
     */
    private function checkConditions(array $conditions, $subject, array $data): bool
    {
        foreach ($conditions as $field => $condition) {
            if (is_array($condition)) {
                $operator = $condition['operator'] ?? '=';
                $value = $condition['value'] ?? null;
            } else {
                $operator = '=';
                $value = $condition;
            }

            $subjectValue = $this->getSubjectValue($subject, $field, $data);

            if (!$this->compareValues($subjectValue, $operator, $value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get value from subject
     */
    private function getSubjectValue($subject, string $field, array $data)
    {
        // Check data first
        if (isset($data[$field])) {
            return $data[$field];
        }

        // Check subject attributes
        if (is_object($subject) && isset($subject->$field)) {
            return $subject->$field;
        }

        // Check subject array
        if (is_array($subject) && isset($subject[$field])) {
            return $subject[$field];
        }

        return null;
    }

    /**
     * Compare values with operator
     */
    private function compareValues($subjectValue, string $operator, $targetValue): bool
    {
        return match($operator) {
            '=' => $subjectValue == $targetValue,
            '!=' => $subjectValue != $targetValue,
            '>' => $subjectValue > $targetValue,
            '>=' => $subjectValue >= $targetValue,
            '<' => $subjectValue < $targetValue,
            '<=' => $subjectValue <= $targetValue,
            'in' => in_array($subjectValue, (array) $targetValue),
            'not_in' => !in_array($subjectValue, (array) $targetValue),
            'contains' => str_contains((string) $subjectValue, (string) $targetValue),
            default => false,
        };
    }

    /**
     * Execute workflow actions
     */
    public function execute($subject, array $data = []): void
    {
        if (!$this->is_active) {
            return;
        }

        foreach ($this->actions as $action) {
            $this->executeAction($action, $subject, $data);
        }

        $this->increment('execution_count');
    }

    /**
     * Execute a single action
     */
    private function executeAction(array $action, $subject, array $data): void
    {
        $type = $action['type'] ?? null;
        if (!$type) {
            return;
        }

        match($type) {
            self::ACTION_ASSIGN_USER => $this->actionAssignUser($action, $subject),
            self::ACTION_SEND_EMAIL => $this->actionSendEmail($action, $subject),
            self::ACTION_CREATE_TASK => $this->actionCreateTask($action, $subject),
            self::ACTION_UPDATE_FIELD => $this->actionUpdateField($action, $subject),
            self::ACTION_ADD_TAG => $this->actionAddTag($action, $subject),
            self::ACTION_CREATE_DEAL => $this->actionCreateDeal($action, $subject),
            default => null,
        };
    }

    /**
     * Action: Assign user
     */
    private function actionAssignUser(array $action, $subject): void
    {
        if (!isset($action['user_id']) || !method_exists($subject, 'update')) {
            return;
        }

        $subject->update(['assigned_to' => $action['user_id']]);
    }

    /**
     * Action: Send email
     */
    private function actionSendEmail(array $action, $subject): void
    {
        // TODO: Integrate with email service
        // For now, just log it
        \Log::info('Workflow email action', [
            'workflow_id' => $this->id,
            'template' => $action['template'] ?? null,
            'subject_id' => $subject->id ?? null,
        ]);
    }

    /**
     * Action: Create task (activity)
     */
    private function actionCreateTask(array $action, $subject): void
    {
        if (!method_exists($subject, 'activities')) {
            return;
        }

        $subject->activities()->create([
            'account_id' => $this->account_id,
            'type' => $action['activity_type'] ?? 'note',
            'title' => $action['title'] ?? 'Task',
            'description' => $action['description'] ?? null,
            'date' => $action['date'] ?? now(),
            'user_id' => $action['user_id'] ?? $this->account->users()->first()?->id,
        ]);
    }

    /**
     * Action: Update field
     */
    private function actionUpdateField(array $action, $subject): void
    {
        if (!isset($action['field']) || !isset($action['value']) || !method_exists($subject, 'update')) {
            return;
        }

        $subject->update([$action['field'] => $action['value']]);
    }

    /**
     * Action: Add tag
     */
    private function actionAddTag(array $action, $subject): void
    {
        if (!isset($action['tag']) || !isset($subject->tags)) {
            return;
        }

        $tags = is_array($subject->tags) ? $subject->tags : [];
        if (!in_array($action['tag'], $tags)) {
            $tags[] = $action['tag'];
            $subject->update(['tags' => $tags]);
        }
    }

    /**
     * Action: Create deal
     */
    private function actionCreateDeal(array $action, $subject): void
    {
        if (!($subject instanceof Lead) || $subject->deal) {
            return;
        }

        $subject->account->deals()->create([
            'lead_id' => $subject->id,
            'title' => $action['title'] ?? "Deal with {$subject->name}",
            'stage' => $action['stage'] ?? 'prospecting',
            'status' => 'open',
            'assigned_to' => $action['user_id'] ?? $subject->assigned_to,
        ]);
    }
}
