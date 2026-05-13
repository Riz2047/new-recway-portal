<?php

namespace App\Providers;

use App\Models\ActionLog;
use App\Models\Candidate;
use App\Models\Customer;
use App\Models\EmailTemplate;
use App\Models\Media;
use App\Models\Module;
use App\Models\Post;
use App\Models\Place;
use App\Models\ServiceCategory;
use App\Models\Setting;
use App\Models\Status;
use App\Models\Term;
use App\Models\User;
use App\Policies\ActionLogPolicy;
use App\Policies\CandidatePolicy;
use App\Policies\CustomerPolicy;
use App\Policies\EmailTemplatePolicy;
use App\Policies\MediaPolicy;
use App\Policies\ModulePolicy;
use App\Policies\PermissionPolicy;
use App\Policies\PlacePolicy;
use App\Policies\PostPolicy;
use App\Policies\RolePolicy;
use App\Policies\ServiceCategoryPolicy;
use App\Policies\SettingPolicy;
use App\Policies\StatusPolicy;
use App\Policies\TermPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        User::class => UserPolicy::class,
        Post::class => PostPolicy::class,
        Role::class => RolePolicy::class,
        Permission::class => PermissionPolicy::class,
        Term::class => TermPolicy::class,
        Media::class => MediaPolicy::class,
        Setting::class => SettingPolicy::class,
        Module::class => ModulePolicy::class,
        ActionLog::class => ActionLogPolicy::class,
        ServiceCategory::class => ServiceCategoryPolicy::class,
        Status::class => StatusPolicy::class,
        Place::class => PlacePolicy::class,
        Candidate::class => CandidatePolicy::class,
        Customer::class => CustomerPolicy::class,
        EmailTemplate::class => EmailTemplatePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
