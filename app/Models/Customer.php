<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Customer extends Model
{
    protected $table = 'customers';

    protected $fillable = [
        'user_id',
        'phone',
        'company',
        'org_no',
        'cost_place',
        'statuses',
        'reg_email',
        'parent_id',
        'dep_id',
        'interview_template',
        'send_security_report',
        'sent_email',
        'combine_bk_and_security',
                'combine_interview_service',
        'timra_report',
        'combine_status',
        'invoice_period',
        'last_invoice_sent',
        'client_wish',
        'groups',
        'interview_upload_allowed',
        'remainder_email_template',
        'bk_interviewed',
        'bk_remainder_email_template',
        'report_delete_duration',
        'last_login',
        'ellevio_report',
        'send_email_question',
    ];

    protected $casts = [
        'interview_template' => 'boolean',
        'send_security_report' => 'boolean',
        'sent_email' => 'boolean',
        'timra_report' => 'boolean',
        'interview_upload_allowed' => 'boolean',
        'bk_interviewed' => 'boolean',
        'send_email_question' => 'boolean',
        'last_invoice_sent' => 'date',
        'last_login' => 'datetime',
    ];

    /**
     * Get the user associated with the customer
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get parent customer
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'parent_id');
    }

    /**
     * Get child customers
     */
    public function children(): HasMany
    {
        return $this->hasMany(Customer::class, 'parent_id');
    }

    /**
     * Legacy relation for customer service rows.
     */
    public function services(): HasMany
    {
        return $this->hasMany(CustomerService::class, 'cus_id', 'id');
    }

    /**
     * Service types assigned to the customer's user.
     */
    public function serviceTypes(): BelongsToMany
    {
        return $this->belongsToMany(
            ServiceType::class,
            'service_type_user',
            'cus_id',
            'service_type_id',
            'id'
        )->withTimestamps();
    }

    /**
     * Get customer permissions
     * Returns array of permission titles as keys (like old system: ['Create-order' => 1, 'View-order' => 1])
     */
    public function getPermissionsAttribute(): array
    {
        return app(\App\Services\CustomerPermissionService::class)->getCustomerPermissions($this->id);
    }

    /**
     * Check if customer has a specific permission
     */
    public function hasPermission(string $permissionTitle): bool
    {
        return app(\App\Services\CustomerPermissionService::class)->hasPermission($this->id, $permissionTitle);
    }

    /**
     * Get statuses as array
     */
    public function getStatusesArrayAttribute(): array
    {
        if (empty($this->statuses)) {
            return [];
        }
        return explode(',', $this->statuses);
    }

    /**
     * Get groups as array
     */
    public function getGroupsArrayAttribute(): array
    {
        if (empty($this->groups)) {
            return [];
        }
        return explode(',', $this->groups);
    }

    /**
     * Get allowed email statuses as IDs array from the single-row JSON storage.
     */
    public function getAllowedEmailStatusIdsAttribute(): array
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('allowed_emails')) {
            return [];
        }

        $value = \Illuminate\Support\Facades\DB::table('allowed_emails')
            ->where('cus_id', $this->id)
            ->value('allowed_status_ids');

        if (empty($value)) {
            return [];
        }

        $decoded = json_decode((string) $value, true);

        return is_array($decoded)
            ? array_values(array_unique(array_map('intval', $decoded)))
            : [];
    }

    public function billing(): HasOne
    {
        return $this->hasOne(StandardBillingDetails::class, 'cus_id');
    }
}
