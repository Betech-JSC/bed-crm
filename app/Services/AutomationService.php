<?php

namespace App\Services;

use App\Models\EmailAutomation;
use App\Models\EmailAutomationStep;
use App\Models\EmailListContact;
use App\Models\Lead;
use App\Models\Contact;
use App\Models\Deal;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AutomationService
{
    public function __construct(
        private EmailService $emailService
    ) {
    }

    /**
     * Process automation for a contact/lead
     */
    public function processAutomation(EmailAutomation $automation, string $contactType, int $contactId): void
    {
        if ($automation->status !== EmailAutomation::STATUS_ACTIVE) {
            return;
        }

        // Check if trigger matches
        if (!$this->matchesTrigger($automation, $contactType, $contactId)) {
            return;
        }

        // Get contact info
        $contact = $this->getContact($contactType, $contactId);
        if (!$contact || !$contact->email) {
            return;
        }

        // Process steps
        $steps = $automation->steps()->where('is_active', true)->orderBy('step_order')->get();
        
        foreach ($steps as $step) {
            try {
                $this->processStep($step, $contact, $contactType, $contactId);
            } catch (\Exception $e) {
                Log::error('Failed to process automation step', [
                    'automation_id' => $automation->id,
                    'step_id' => $step->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $automation->contacts_processed++;
        $automation->save();
    }

    /**
     * Check if trigger matches
     */
    private function matchesTrigger(EmailAutomation $automation, string $contactType, int $contactId): bool
    {
        $triggerType = $automation->trigger_type;
        $triggerConfig = $automation->trigger_config ?? [];

        switch ($triggerType) {
            case EmailAutomation::TRIGGER_LEAD_CREATED:
                return $contactType === 'lead';

            case EmailAutomation::TRIGGER_CONTACT_CREATED:
                return $contactType === 'contact';

            case EmailAutomation::TRIGGER_DEAL_WON:
                if ($contactType === 'lead') {
                    $lead = Lead::find($contactId);
                    return $lead && $lead->status === Lead::STATUS_WON;
                }
                return false;

            case EmailAutomation::TRIGGER_TAG_ADDED:
                // Check if contact has specific tag
                $requiredTag = $triggerConfig['tag'] ?? null;
                if (!$requiredTag) {
                    return false;
                }
                $contact = $this->getContact($contactType, $contactId);
                if ($contact && method_exists($contact, 'tags')) {
                    return in_array($requiredTag, $contact->tags ?? []);
                }
                return false;

            case EmailAutomation::TRIGGER_DATE_BASED:
                // Date-based triggers (e.g., birthday, anniversary)
                // Implementation depends on requirements
                return false;

            default:
                return false;
        }
    }

    /**
     * Get contact object
     */
    private function getContact(string $contactType, int $contactId)
    {
        return match ($contactType) {
            'lead' => Lead::find($contactId),
            'contact' => Contact::find($contactId),
            default => null,
        };
    }

    /**
     * Process automation step
     */
    private function processStep(EmailAutomationStep $step, $contact, string $contactType, int $contactId): void
    {
        switch ($step->step_type) {
            case EmailAutomationStep::TYPE_SEND_EMAIL:
                $this->processSendEmailStep($step, $contact, $contactType, $contactId);
                break;

            case EmailAutomationStep::TYPE_WAIT:
                // Wait steps are handled by scheduling
                // For now, we'll skip them in immediate processing
                break;

            case EmailAutomationStep::TYPE_CONDITION:
                // Condition steps check if conditions are met
                // If not, skip remaining steps
                if (!$this->checkConditions($step, $contact)) {
                    return;
                }
                break;

            case EmailAutomationStep::TYPE_TAG:
                // Add tag to contact
                $this->addTag($step, $contact, $contactType, $contactId);
                break;
        }
    }

    /**
     * Process send email step
     */
    private function processSendEmailStep(EmailAutomationStep $step, $contact, string $contactType, int $contactId): void
    {
        $template = $step->emailTemplate;
        if (!$template) {
            return;
        }

        $variables = [
            'name' => $contact->name ?? ($contact->first_name . ' ' . $contact->last_name),
            'email' => $contact->email,
            'company' => $contact->company ?? null,
        ];

        try {
            $this->emailService->sendEmail(
                automation: $step->automation,
                email: $contact->email,
                name: $contact->name ?? ($contact->first_name . ' ' . $contact->last_name ?? ''),
                contactType: $contactType,
                contactId: $contactId,
                template: $template,
                variables: $variables
            );

            $step->automation->emails_sent++;
            $step->automation->save();
        } catch (\Exception $e) {
            Log::error('Failed to send automation email', [
                'step_id' => $step->id,
                'email' => $contact->email,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check conditions
     */
    private function checkConditions(EmailAutomationStep $step, $contact): bool
    {
        $conditions = $step->conditions ?? [];
        
        if (empty($conditions)) {
            return true;
        }

        foreach ($conditions as $condition) {
            $field = $condition['field'] ?? null;
            $operator = $condition['operator'] ?? 'equals';
            $value = $condition['value'] ?? null;

            if (!$field) {
                continue;
            }

            $contactValue = $contact->{$field} ?? null;

            $matches = match ($operator) {
                'equals' => $contactValue == $value,
                'not_equals' => $contactValue != $value,
                'contains' => stripos($contactValue ?? '', $value ?? '') !== false,
                'greater_than' => $contactValue > $value,
                'less_than' => $contactValue < $value,
                default => false,
            };

            if (!$matches) {
                return false;
            }
        }

        return true;
    }

    /**
     * Add tag to contact
     */
    private function addTag(EmailAutomationStep $step, $contact, string $contactType, int $contactId): void
    {
        $tag = $step->step_config['tag'] ?? null;
        if (!$tag) {
            return;
        }

        if (method_exists($contact, 'tags')) {
            $tags = $contact->tags ?? [];
            if (!in_array($tag, $tags)) {
                $tags[] = $tag;
                $contact->tags = $tags;
                $contact->save();
            }
        }
    }

    /**
     * Run scheduled automations (for wait steps)
     */
    public function runScheduledAutomations(): void
    {
        // This would be called by a scheduled job
        // Implementation depends on how wait steps are stored
        // For now, this is a placeholder
    }
}

