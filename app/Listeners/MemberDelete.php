<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Log;
use Statamic\Events\UserDeleted;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class MemberDelete
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(UserDeleted $event): void
    {

    }
}
