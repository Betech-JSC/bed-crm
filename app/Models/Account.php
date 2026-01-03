<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    protected $fillable = [
        'name',
        'logo',
    ];

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

    public function icps(): HasMany
    {
        return $this->hasMany(ICP::class);
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
}
