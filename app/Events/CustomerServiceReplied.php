<?php

namespace App\Events;

use App\Models\CustomerService;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CustomerServiceReplied
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public CustomerService $customerService;

    public function __construct(CustomerService $customerService)
    {
        $this->customerService = $customerService;
    }
}