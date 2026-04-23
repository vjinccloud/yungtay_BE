<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Events\AdminLoggedIn;
use App\Listeners\LogAdminLogin;
use App\Events\DataUpdated;
use App\Listeners\LogDataUpdate;
use App\Events\DataDeleted;
use App\Listeners\LogDataDelete;
use App\Events\DataCreated;
use App\Listeners\LogDataCreated;
use App\Events\DataSort;
use App\Listeners\LogDataSort;
use App\Events\CustomerServiceReplied;
use App\Listeners\LogCustomerServiceReply;
use App\Models\CustomerService;
use App\Observers\CustomerServiceObserver;
use App\Models\MemberNotification;
use App\Observers\MemberNotificationObserver;


class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        AdminLoggedIn::class => [
            LogAdminLogin::class,
        ],
        DataUpdated::class => [
            LogDataUpdate::class,
        ],
        DataDeleted::class => [
            LogDataDelete::class,
        ],

        DataCreated::class => [
            LogDataCreated::class,
        ],

        DataDeleted::class => [
            LogDataDelete::class,
        ],

        DataSort::class => [
            LogDataSort::class,
        ],

        CustomerServiceReplied::class => [
            LogCustomerServiceReply::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        // 註冊 Observer
        CustomerService::observe(CustomerServiceObserver::class);
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
