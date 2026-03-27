<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    protected $fillable = [
        'name', 'slogan', 'description', 'logo', 'favicon',
        'timezone', 'currency', 'locale', 'date_format', 'time_format',
        'fiscal_year_start', 'phone', 'email', 'website', 'address',
        'tax_id', 'registration_number', 'industry', 'company_size', 'founded_year',
        'social_links',
    ];

    protected $casts = [
        'company_size' => 'integer',
        'social_links' => 'array',
    ];

    // ── Config helpers ──
    public function systemConfigs(): HasMany { return $this->hasMany(SystemConfig::class); }

    public function getConfig(string $group, string $key, $default = null)
    {
        return SystemConfig::getValue($this->id, $group, $key, $default);
    }

    public function smtpSetting()
    {
        return $this->hasOne(SMTPSetting::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function organizations(): HasMany
    {
        return $this->hasMany(Organization::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }




    public function salesChannels(): HasMany
    {
        return $this->hasMany(SalesChannel::class);
    }

    public function videoProjects(): HasMany
    {
        return $this->hasMany(VideoProject::class);
    }

    public function meetings(): HasMany
    {
        return $this->hasMany(Meeting::class);
    }

    public function workflows(): HasMany
    {
        return $this->hasMany(Workflow::class);
    }

    public function slaSettings(): HasMany
    {
        return $this->hasMany(SLASetting::class);
    }

    public function proposals(): HasMany
    {
        return $this->hasMany(Proposal::class);
    }

    public function salesPlaybooks(): HasMany
    {
        return $this->hasMany(SalesPlaybook::class);
    }

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function contentTemplates(): HasMany
    {
        return $this->hasMany(ContentTemplate::class);
    }

    public function contentItems(): HasMany
    {
        return $this->hasMany(ContentItem::class);
    }

    public function socialPosts(): HasMany
    {
        return $this->hasMany(SocialPost::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(File::class);
    }

    public function chatWidgets(): HasMany
    {
        return $this->hasMany(ChatWidget::class);
    }

    public function emailTemplates(): HasMany
    {
        return $this->hasMany(EmailTemplate::class);
    }

    public function emailLists(): HasMany
    {
        return $this->hasMany(EmailList::class);
    }

    public function emailSegments(): HasMany
    {
        return $this->hasMany(EmailSegment::class);
    }

    public function emailCampaigns(): HasMany
    {
        return $this->hasMany(EmailCampaign::class);
    }

    public function emailAutomations(): HasMany
    {
        return $this->hasMany(EmailAutomation::class);
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(Permission::class);
    }

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }

    public function chatConversations(): HasMany
    {
        return $this->hasMany(ChatConversation::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function employeeProfiles(): HasMany
    {
        return $this->hasMany(EmployeeProfile::class);
    }

    public function kpiDefinitions(): HasMany
    {
        return $this->hasMany(KpiDefinition::class);
    }

    public function financialTransactions(): HasMany
    {
        return $this->hasMany(FinancialTransaction::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    public function orgObjectives(): HasMany
    {
        return $this->hasMany(OrgObjective::class);
    }

    public function approvalWorkflows(): HasMany
    {
        return $this->hasMany(ApprovalWorkflow::class);
    }

    public function salesPipelines(): HasMany
    {
        return $this->hasMany(SalesPipeline::class);
    }

    public function wikiCategories(): HasMany
    {
        return $this->hasMany(WikiCategory::class);
    }

    public function wikiArticles(): HasMany
    {
        return $this->hasMany(WikiArticle::class);
    }
}

