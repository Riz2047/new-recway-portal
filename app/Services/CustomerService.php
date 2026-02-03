<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class CustomerService
{
    /**
     * Create a new customer with all related data
     */
    public function createCustomer(array $data): Customer
    {
        return DB::transaction(function () use ($data) {
            // Determine registration email template
            $regEmail = $this->determineRegistrationEmail($data);
            
            // Prepare customer data
            $customerData = $this->prepareCustomerData($data, $regEmail);
            
            // Create customer
            $customer = Customer::create($customerData);
            
            // Handle parent customer copying
            if (!empty($data['parent_id'])) {
                $this->copyFromParentCustomer($customer, $data['parent_id']);
            } else {
                // Create default messages for selected services
                $this->createDefaultMessages($customer->id, $data['services'] ?? []);
            }
            
            // Link services
            $this->syncCustomerServices($customer->id, $data['services'] ?? []);
            
            // Create allowed emails for all statuses
            $this->createAllowedEmails($customer->id);
            
            // Handle permissions
            if (!empty($data['permissions'])) {
                $this->syncPermissions($customer->id, $data['permissions']);
            }
            
            // Handle company manager
            if (!empty($data['company_manager'])) {
                $this->createCompanyManager($customer->id, $data['company']);
            }
            
            // Handle standard billing details
            if (!empty($data['pref']) || !empty($data['ref']) || !empty($data['comment'])) {
                $this->createStandardBillingDetails($customer->id, $data);
            }
            
            // Send registration email if requested
            if (!empty($data['send_email'])) {
                $this->sendRegistrationEmail($customer, $regEmail);
            }
            
            return $customer->refresh();
        });
    }
    
    /**
     * Update customer with all related data
     */
    public function updateCustomer(Customer $customer, array $data): Customer
    {
        return DB::transaction(function () use ($customer, $data) {
            // Prepare customer data
            $customerData = $this->prepareCustomerData($data, null, false);
            
            // Update customer
            $customer->update($customerData);
            
            // Handle parent customer copying if changed
            if (isset($data['parent_id']) && $data['parent_id'] != $customer->parent_id) {
                if (!empty($data['parent_id'])) {
                    $this->copyFromParentCustomer($customer, $data['parent_id']);
                }
            }
            
            // Sync services
            if (isset($data['services'])) {
                $this->syncCustomerServices($customer->id, $data['services'], true);
            }
            
            // Sync permissions
            if (isset($data['permissions'])) {
                $this->syncPermissions($customer->id, $data['permissions'], true);
            }
            
            // Update child customers if statuses or interview_upload_allowed changed
            if (isset($data['statuses']) || isset($data['interview_upload_allowed'])) {
                $this->updateChildCustomers($customer, $data);
            }
            
            // Update email addresses in emails table
            if (isset($data['email']) && $data['email'] != $data['old_email'] ?? '') {
                $this->updateEmailAddresses($data['old_email'], $data['email']);
            }
            
            return $customer->refresh();
        });
    }
    
    /**
     * Determine registration email template based on priority
     */
    private function determineRegistrationEmail(array $data): ?string
    {
        // Priority 1: Custom registration email provided
        if (!empty($data['changed_registration_email'])) {
            return $data['changed_registration_email'];
        }
        
        // Priority 2: Parent customer's reg_email
        if (!empty($data['parent_id'])) {
            $parent = Customer::find($data['parent_id']);
            if ($parent && !empty($parent->reg_email)) {
                return $parent->reg_email;
            }
        }
        
        // Priority 3: Global default (from settings)
        // This would need to be fetched from settings table
        // For now, return null and let the calling code handle it
        return null;
    }
    
    /**
     * Prepare customer data for create/update
     */
    private function prepareCustomerData(array $data, ?string $regEmail = null, bool $isCreate = true): array
    {
        $customerData = [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'company' => $data['company'] ?? null,
            'org_no' => $data['org_no'] ?? null,
            'cost_place' => $data['org_no'] ?? null, // cost_place is same as org_no
            'parent_id' => $data['parent_id'] ?? null,
            'dep_id' => $data['cus_department'] ?? null,
            'interview_template' => !empty($data['interview_template']),
            'send_security_report' => !empty($data['send_security_report']),
            'sent_email' => !empty($data['send_email']),
            'combine_bk_and_security' => $this->prepareCombineServices($data['combine_bk_and_security'] ?? []),
            'timra_report' => !empty($data['timra_report']),
            'combine_status' => $this->prepareCombineStatuses($data['combine_status'] ?? []),
            'invoice_period' => $data['invoice_period'] ?? 'month',
            'last_invoice_sent' => $this->calculateLastInvoiceSent($data['invoice_period'] ?? 'month', $data['last_invoice_sent'] ?? null),
            'client_wish' => $data['client_wish'] ?? null,
            'interview_upload_allowed' => !empty($data['interview_upload_allowed']),
            'interviewed' => !empty($data['interviewed']),
            'remainder_email_template' => $data['remainder_email_template'] ?? null,
            'bk_interviewed' => !empty($data['bk_interviewed']),
            'bk_remainder_email_template' => $data['bk_remainder_email_template'] ?? null,
        ];
        
        if ($isCreate) {
            $customerData['password'] = $data['password'];
            $customerData['reg_email'] = $regEmail;
            $customerData['statuses'] = $this->prepareStatusesString($data['statuses'] ?? []);
        } else {
            if (isset($data['password']) && !empty($data['password'])) {
                $customerData['password'] = $data['password'];
            }
            if (isset($data['statuses'])) {
                $customerData['statuses'] = $this->prepareStatusesString($data['statuses']);
            }
            if (isset($data['reg_email'])) {
                $customerData['reg_email'] = $data['reg_email'];
            }
            if (isset($data['groups'])) {
                $customerData['groups'] = $this->prepareGroupsString($data['groups']);
            }
        }
        
        return array_filter($customerData, fn($value) => $value !== null || is_bool($value));
    }
    
    /**
     * Prepare statuses as comma-separated string
     */
    private function prepareStatusesString(array $statuses): string
    {
        return implode(',', $statuses);
    }
    
    /**
     * Prepare groups as comma-separated string
     */
    private function prepareGroupsString(array $groups): string
    {
        return implode(',', $groups);
    }
    
    /**
     * Prepare combine services as comma-separated string
     */
    private function prepareCombineServices($services): string
    {
        if (is_array($services)) {
            return implode(',', $services);
        }
        return $services ?? '0';
    }
    
    /**
     * Prepare combine statuses as comma-separated string
     */
    private function prepareCombineStatuses($statuses): string
    {
        if (is_array($statuses)) {
            return implode(',', $statuses);
        }
        return $statuses ?? '';
    }
    
    /**
     * Calculate last_invoice_sent based on invoice period
     */
    private function calculateLastInvoiceSent(string $period, ?string $providedDate): ?string
    {
        if ($providedDate) {
            return $providedDate;
        }
        
        $today = strtotime(date('Y-m-d'));
        switch ($period) {
            case 'day':
                return date('Y-m-d', strtotime('-1 day', $today));
            case 'week':
                return date('Y-m-d', strtotime('last monday', $today));
            case 'month':
                return date('Y-m-01', strtotime('first day of last month', $today));
            default:
                return date('Y-m-01', strtotime('first day of last month', $today));
        }
    }
    
    /**
     * Copy data from parent customer
     */
    private function copyFromParentCustomer(Customer $customer, int $parentId): void
    {
        $parent = Customer::findOrFail($parentId);
        
        // Copy order forms
        $this->copyOrderForms($customer->id, $parentId);
        
        // Copy messages
        $this->copyMessages($customer->id, $parentId);
        
        // Copy customer reports
        $this->copyCustomerReports($customer->id, $parentId);
    }
    
    /**
     * Copy order forms from parent
     */
    private function copyOrderForms(int $customerId, int $parentId): void
    {
        // This would need the order_forms table structure
        // Implementation depends on your schema
        DB::statement("
            INSERT INTO order_forms (cus_id, ...)
            SELECT ? as cus_id, ...
            FROM order_forms
            WHERE cus_id = ?
        ", [$customerId, $parentId]);
    }
    
    /**
     * Copy messages from parent
     */
    private function copyMessages(int $customerId, int $parentId): void
    {
        // Implementation depends on messages table structure
        DB::statement("
            INSERT INTO messages (cus_id, interview_id, ...)
            SELECT ? as cus_id, interview_id, ...
            FROM messages
            WHERE cus_id = ?
        ", [$customerId, $parentId]);
    }
    
    /**
     * Copy customer reports from parent
     */
    private function copyCustomerReports(int $customerId, int $parentId): void
    {
        $metaInfo = json_encode([
            'created_by' => auth()->id(),
            'created_on' => now()->toDateTimeString(),
            'user' => 'Admin'
        ]);
        
        DB::statement("
            INSERT INTO customer_reports_html (cus_id, report_data, interview_id, lang, meta_info)
            SELECT ? as cus_id, report_data, interview_id, lang, ? as meta_info
            FROM customer_reports_html
            WHERE cus_id = ?
        ", [$customerId, $metaInfo, $parentId]);
    }
    
    /**
     * Create default messages for services
     */
    private function createDefaultMessages(int $customerId, array $services): void
    {
        // If there are no services or the messages table doesn't exist yet, do nothing
        if (empty($services) || ! Schema::hasTable('messages')) {
            return;
        }

        // Get default messages (cus_id = 0, interview_id = 0)
        $defaultMessages = DB::table('messages')
            ->where('cus_id', 0)
            ->where('interview_id', 0)
            ->first();
        
        if ($defaultMessages) {
            foreach ($services as $serviceId) {
                $messageData = (array) $defaultMessages;
                unset($messageData['id'], $messageData['cus_id'], $messageData['interview_id']);
                $messageData['cus_id'] = $customerId;
                $messageData['interview_id'] = $serviceId;
                
                DB::table('messages')->insert($messageData);
            }
        }
    }
    
    /**
     * Sync customer services
     */
    private function syncCustomerServices(int $customerId, array $services, bool $updateChildren = false): void
    {
        // Get current services
        $currentServices = DB::table('customer_services')
            ->where('cus_id', $customerId)
            ->pluck('service_id')
            ->toArray();
        
        // Services to remove
        $toRemove = array_diff($currentServices, $services);
        if (!empty($toRemove)) {
            DB::table('customer_services')
                ->where('cus_id', $customerId)
                ->whereIn('service_id', $toRemove)
                ->delete();
            
            if ($updateChildren) {
                $this->removeServicesFromChildren($customerId, $toRemove);
            }
        }
        
        // Services to add
        $toAdd = array_diff($services, $currentServices);
        if (!empty($toAdd)) {
            foreach ($toAdd as $serviceId) {
                DB::table('customer_services')->insert([
                    'cus_id' => $customerId,
                    'service_id' => $serviceId,
                ]);
            }
            
            if ($updateChildren) {
                $this->addServicesToChildren($customerId, $toAdd);
            }
        }
    }
    
    /**
     * Remove services from child customers
     */
    private function removeServicesFromChildren(int $parentId, array $serviceIds): void
    {
        $children = Customer::where('parent_id', $parentId)->pluck('id');
        foreach ($children as $childId) {
            DB::table('customer_services')
                ->where('cus_id', $childId)
                ->whereIn('service_id', $serviceIds)
                ->delete();
        }
    }
    
    /**
     * Add services to child customers
     */
    private function addServicesToChildren(int $parentId, array $serviceIds): void
    {
        $children = Customer::where('parent_id', $parentId)->pluck('id');
        foreach ($children as $childId) {
            foreach ($serviceIds as $serviceId) {
                DB::table('customer_services')->insertOrIgnore([
                    'cus_id' => $childId,
                    'service_id' => $serviceId,
                ]);
            }
        }
    }
    
    /**
     * Create allowed emails for all statuses
     */
    private function createAllowedEmails(int $customerId): void
    {
        // If table doesn't exist yet, skip gracefully
        if (! Schema::hasTable('allowed_emails') || ! Schema::hasTable('statuses')) {
            return;
        }

        DB::statement("
            INSERT INTO allowed_emails (cus_id, status_id, allowed)
            SELECT ? as cus_id, id as status_id, 1 as allowed
            FROM statuses
        ", [$customerId]);
    }
    
    /**
     * Sync permissions
     */
    private function syncPermissions(int $customerId, array $permissions, bool $replace = false): void
    {
        if ($replace) {
            DB::table('user_allowed_permissions')
                ->where('user_id', $customerId)
                ->where('user_type', 2) // 2 = customer
                ->delete();
        }
        
        foreach ($permissions as $permissionId) {
            DB::table('user_allowed_permissions')->insertOrIgnore([
                'per_id' => $permissionId,
                'user_id' => $customerId,
                'user_type' => 2, // 2 = customer
            ]);
        }
    }
    
    /**
     * Create company manager record
     */
    private function createCompanyManager(int $customerId, string $company): void
    {
        // If table not present yet, skip safely
        if (! Schema::hasTable('company_manager')) {
            return;
        }

        DB::table('company_manager')->insert([
            'company' => $company,
            'cus_id' => $customerId,
        ]);
    }
    
    /**
     * Create standard billing details
     */
    private function createStandardBillingDetails(int $customerId, array $data): void
    {
        // If table not present (e.g. during early migrations), skip safely
        if (! Schema::hasTable('standard_billing_details')) {
            return;
        }

        DB::table('standard_billing_details')->insert([
            'cus_id' => $customerId,
            'referenceperson' => $data['pref'] ?? null,
            'reference' => $data['ref'] ?? null,
            'comment' => $data['comment'] ?? null,
        ]);
    }
    
    /**
     * Send registration email
     */
    private function sendRegistrationEmail(Customer $customer, ?string $emailTemplate): void
    {
        if (empty($emailTemplate)) {
            // Get default from settings
            $emailTemplate = $this->getDefaultRegistrationEmail();
        }
        
        if (empty($emailTemplate)) {
            return;
        }
        
        // Replace placeholders
        $body = $this->replaceEmailPlaceholders(
            $emailTemplate,
            $customer->name,
            $customer->company,
            $customer->email,
            $customer->password ?? ''
        );
        
        $subject = "Registration";
        
        // Check if within Swedish working hours (Mon-Fri, 08:00-18:00)
        $swedenTime = now('Europe/Stockholm');
        $isWorkingHours = $swedenTime->isWeekday() 
            && $swedenTime->format('H:i:s') >= '08:00:00' 
            && $swedenTime->format('H:i:s') < '18:00:00';
        
        // Save email
        $this->saveEmail(
            "Customer",
            $customer->name,
            "N/A",
            'Customer Registration Message',
            $body,
            $customer->email,
            $subject,
            $isWorkingHours ? null : '1' // Delay if outside working hours
        );
        
        // Send immediately if within working hours
        if ($isWorkingHours) {
            $this->sendMail($body, $customer->email, $customer->name, $subject);
        }
    }
    
    /**
     * Get default registration email from settings
     */
    private function getDefaultRegistrationEmail(): ?string
    {
        $setting = DB::table('settings')
            ->where('option_name', 'cus_reg_msg')
            ->first();
        
        return $setting->option_value ?? null;
    }
    
    /**
     * Replace email placeholders
     */
    private function replaceEmailPlaceholders(string $text, string $customer, string $company, string $email, string $password): string
    {
        $replacements = [
            '{customer}' => $customer,
            '{company}' => $company,
            '{email}' => $email,
            '{password}' => $password,
        ];
        
        return str_replace(array_keys($replacements), array_values($replacements), $text);
    }
    
    /**
     * Save email to database
     */
    private function saveEmail(string $userType, string $userName, string $orderID, string $msgType, string $text, string $email, string $subject, ?string $emailDelay = null): void
    {
        DB::table('emails')->insert([
            'user_type' => $userType,
            'user_name' => $userName,
            'order_id' => $orderID,
            'msg_type' => $msgType,
            'text' => $text,
            'email' => $email,
            'subject' => $subject,
            'email_delay' => $emailDelay,
        ]);
    }
    
    /**
     * Send email via mailer
     */
    private function sendMail(string $body, string $to, string $name, string $subject): void
    {
        // This would use Laravel's Mail facade
        // Implementation depends on your mail configuration
        // For now, just log it
        Log::info('Registration email would be sent', [
            'to' => $to,
            'name' => $name,
            'subject' => $subject,
        ]);
    }
    
    /**
     * Update child customers when parent is updated
     */
    private function updateChildCustomers(Customer $customer, array $data): void
    {
        $children = Customer::where('parent_id', $customer->id)->get();
        
        foreach ($children as $child) {
            $updateData = [];
            
            if (isset($data['statuses'])) {
                $updateData['statuses'] = $data['statuses'];
            }
            
            if (isset($data['interview_upload_allowed'])) {
                $updateData['interview_upload_allowed'] = $data['interview_upload_allowed'];
            }
            
            if (!empty($updateData)) {
                $child->update($updateData);
            }
        }
    }
    
    /**
     * Update email addresses in emails table
     */
    private function updateEmailAddresses(string $oldEmail, string $newEmail): void
    {
        DB::table('emails')
            ->where('email', $oldEmail)
            ->update(['email' => $newEmail]);
    }
}

