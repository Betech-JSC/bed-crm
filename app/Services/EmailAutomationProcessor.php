<?php

namespace App\Services;

use App\Models\EmailAutomation;
use App\Models\EmailAutomationEnrollment;
use App\Models\EmailAutomationStep;
use App\Models\EmailSend;
use App\Models\EmailTemplate;
use Illuminate\Support\Facades\Log;

class EmailAutomationProcessor
{
    public function __construct(
        private EmailService $emailService,
        private EmailAttributionService $attributionService,
    ) {}

    /**
     * Enroll a contact into an automation workflow.
     */
    public function enroll(
        EmailAutomation $automation,
        string $contactType,
        int $contactId,
        string $email
    ): ?EmailAutomationEnrollment {
        // Check re-entry rules
        $existing = EmailAutomationEnrollment::where('email_automation_id', $automation->id)
            ->where('contact_type', $contactType)
            ->where('contact_id', $contactId)
            ->latest()
            ->first();

        $entryConditions = $automation->entry_conditions ?? [];
        $frequency = $entryConditions['frequency'] ?? 'once';

        if ($existing && $frequency === 'once' && $existing->status !== 'exited') {
            return null; // Already enrolled
        }

        if ($existing && $frequency === 'multiple') {
            $reEntryDelay = $entryConditions['re_entry_delay_days'] ?? 30;
            if ($existing->entered_at->diffInDays(now()) < $reEntryDelay) {
                return null; // Too soon to re-enter
            }
        }

        $firstStep = $automation->steps()->orderBy('step_order')->first();

        $enrollment = EmailAutomationEnrollment::create([
            'email_automation_id' => $automation->id,
            'contact_type' => $contactType,
            'contact_id' => $contactId,
            'email' => $email,
            'current_step_id' => $firstStep?->id,
            'status' => EmailAutomationEnrollment::STATUS_ACTIVE,
            'entered_at' => now(),
            'next_action_at' => $firstStep ? $this->calculateNextActionTime($firstStep) : null,
            'step_history' => [],
        ]);

        $automation->increment('active_contacts');

        return $enrollment;
    }

    /**
     * Process all due automation steps across all active automations.
     * This should be called by a scheduled job (every minute).
     */
    public function processDueSteps(): int
    {
        $processed = 0;

        $enrollments = EmailAutomationEnrollment::where('status', 'active')
            ->where('next_action_at', '<=', now())
            ->whereNotNull('current_step_id')
            ->with(['automation', 'currentStep'])
            ->limit(100) // Process in batches
            ->get();

        foreach ($enrollments as $enrollment) {
            try {
                $this->executeStep($enrollment);
                $processed++;
            } catch (\Throwable $e) {
                Log::error("Automation step failed", [
                    'enrollment_id' => $enrollment->id,
                    'step_id' => $enrollment->current_step_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $processed;
    }

    /**
     * Execute the current step for an enrollment
     */
    public function executeStep(EmailAutomationEnrollment $enrollment): void
    {
        $step = $enrollment->currentStep;
        if (!$step || !$step->is_active) {
            $this->completeEnrollment($enrollment);
            return;
        }

        // Check automation goal
        $automation = $enrollment->automation;
        if ($this->isGoalMet($enrollment, $automation)) {
            $enrollment->update(['status' => 'goal_met', 'completed_at' => now()]);
            $automation->increment('goal_conversions');
            $automation->decrement('active_contacts');
            return;
        }

        match ($step->step_type) {
            'send_email' => $this->executeSendEmail($enrollment, $step),
            'wait' => $this->executeWait($enrollment, $step),
            'condition' => $this->executeCondition($enrollment, $step),
            'tag' => $this->executeTag($enrollment, $step),
            'update_field' => $this->executeUpdateField($enrollment, $step),
            'webhook' => $this->executeWebhook($enrollment, $step),
            default => $this->advanceToNextStep($enrollment, $step),
        };
    }

    private function executeSendEmail(EmailAutomationEnrollment $enrollment, EmailAutomationStep $step): void
    {
        $template = $step->emailTemplate;
        if (!$template) {
            $this->advanceToNextStep($enrollment, $step);
            return;
        }

        // Create the email send
        $send = EmailSend::create([
            'account_id' => $enrollment->automation->account_id,
            'sendable_type' => 'automation',
            'sendable_id' => $enrollment->email_automation_id,
            'automation_step_id' => $step->id,
            'email_template_id' => $template->id,
            'contact_type' => $enrollment->contact_type,
            'contact_id' => $enrollment->contact_id,
            'email' => $enrollment->email,
            'subject' => $this->personalize($template->subject, $enrollment),
            'body_html' => $this->personalize($template->body_html, $enrollment),
            'body_text' => $template->body_text,
            'status' => EmailSend::STATUS_PENDING,
        ]);

        // Queue the actual send
        $this->emailService->dispatchSend($send);

        $enrollment->recordStep($step->id, 'send_email', ['send_id' => $send->id]);
        $enrollment->automation->increment('emails_sent');

        // Update step performance
        $perf = $step->performance ?? ['sent' => 0, 'opened' => 0, 'clicked' => 0];
        $perf['sent']++;
        $step->update(['performance' => $perf]);

        $this->advanceToNextStep($enrollment, $step);
    }

    private function executeCondition(EmailAutomationEnrollment $enrollment, EmailAutomationStep $step): void
    {
        $conditions = $step->conditions ?? [];
        $result = $this->evaluateConditions($enrollment, $conditions);

        $enrollment->recordStep($step->id, 'condition', ['result' => $result]);

        // Branch based on condition result
        $nextStepId = $result ? $step->yes_next_step_id : $step->no_next_step_id;

        if ($nextStepId) {
            $nextStep = EmailAutomationStep::find($nextStepId);
            $enrollment->update([
                'current_step_id' => $nextStepId,
                'next_action_at' => $nextStep ? $this->calculateNextActionTime($nextStep) : now(),
            ]);
        } else {
            $this->completeEnrollment($enrollment);
        }
    }

    private function executeWait(EmailAutomationEnrollment $enrollment, EmailAutomationStep $step): void
    {
        $enrollment->recordStep($step->id, 'wait');
        $this->advanceToNextStep($enrollment, $step);
    }

    private function executeTag(EmailAutomationEnrollment $enrollment, EmailAutomationStep $step): void
    {
        $config = $step->step_config ?? [];
        $enrollment->recordStep($step->id, 'tag', $config);
        $this->advanceToNextStep($enrollment, $step);
    }

    private function executeUpdateField(EmailAutomationEnrollment $enrollment, EmailAutomationStep $step): void
    {
        $config = $step->step_config ?? [];
        $enrollment->recordStep($step->id, 'update_field', $config);
        $this->advanceToNextStep($enrollment, $step);
    }

    private function executeWebhook(EmailAutomationEnrollment $enrollment, EmailAutomationStep $step): void
    {
        $enrollment->recordStep($step->id, 'webhook');
        $this->advanceToNextStep($enrollment, $step);
    }

    private function advanceToNextStep(EmailAutomationEnrollment $enrollment, EmailAutomationStep $step): void
    {
        $nextStep = EmailAutomationStep::where('email_automation_id', $step->email_automation_id)
            ->where('step_order', '>', $step->step_order)
            ->where('is_active', true)
            ->orderBy('step_order')
            ->first();

        if ($nextStep) {
            $enrollment->update([
                'current_step_id' => $nextStep->id,
                'next_action_at' => $this->calculateNextActionTime($nextStep),
            ]);
        } else {
            $this->completeEnrollment($enrollment);
        }
    }

    private function completeEnrollment(EmailAutomationEnrollment $enrollment): void
    {
        $enrollment->update([
            'status' => EmailAutomationEnrollment::STATUS_COMPLETED,
            'completed_at' => now(),
            'current_step_id' => null,
            'next_action_at' => null,
        ]);

        $enrollment->automation->decrement('active_contacts');
        $enrollment->automation->increment('completed_contacts');
    }

    private function calculateNextActionTime(EmailAutomationStep $step): \Carbon\Carbon
    {
        $delay = now();

        if ($step->wait_days) {
            $delay = $delay->addDays($step->wait_days);
        }
        if ($step->wait_hours) {
            $delay = $delay->addHours($step->wait_hours);
        }
        if ($step->wait_until_time) {
            $delay = $delay->setTimeFromTimeString($step->wait_until_time);
            if ($delay->isPast()) {
                $delay = $delay->addDay();
            }
        }

        // If no wait is configured, execute immediately
        if (!$step->wait_days && !$step->wait_hours && !$step->wait_until_time) {
            return now();
        }

        return $delay;
    }

    private function evaluateConditions(EmailAutomationEnrollment $enrollment, array $conditions): bool
    {
        // Simple condition evaluation
        foreach ($conditions as $condition) {
            $field = $condition['field'] ?? '';
            $operator = $condition['operator'] ?? '=';
            $value = $condition['value'] ?? null;

            $result = match ($field) {
                'email_opened' => EmailSend::where('contact_type', $enrollment->contact_type)
                    ->where('contact_id', $enrollment->contact_id)
                    ->where('sendable_type', 'automation')
                    ->where('sendable_id', $enrollment->email_automation_id)
                    ->whereNotNull('opened_at')
                    ->exists(),
                'email_clicked' => EmailSend::where('contact_type', $enrollment->contact_type)
                    ->where('contact_id', $enrollment->contact_id)
                    ->where('sendable_type', 'automation')
                    ->where('sendable_id', $enrollment->email_automation_id)
                    ->whereNotNull('clicked_at')
                    ->exists(),
                default => true,
            };

            if (!$result) return false;
        }
        return true;
    }

    private function isGoalMet(EmailAutomationEnrollment $enrollment, EmailAutomation $automation): bool
    {
        $goal = $automation->goal_config;
        if (!$goal) return false;

        return match ($goal['type'] ?? '') {
            'deal_won' => \App\Models\Deal::where('account_id', $automation->account_id)
                ->whereHas('lead', fn ($q) => $q->where('email', $enrollment->email))
                ->where('status', 'won')
                ->where('updated_at', '>=', $enrollment->entered_at)
                ->exists(),
            default => false,
        };
    }

    private function personalize(string $content, EmailAutomationEnrollment $enrollment): string
    {
        $contact = null;
        if ($enrollment->contact_type === 'lead') {
            $contact = \App\Models\Lead::find($enrollment->contact_id);
        }

        if (!$contact) return $content;

        $tokens = [
            '{{first_name}}' => $contact->name ? explode(' ', $contact->name)[0] : '',
            '{{name}}' => $contact->name ?? '',
            '{{email}}' => $enrollment->email,
            '{{company}}' => $contact->company ?? '',
        ];

        return str_replace(array_keys($tokens), array_values($tokens), $content);
    }
}
