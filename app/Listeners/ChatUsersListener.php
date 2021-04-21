<?php

namespace App\Listeners;

use App\Events\ChatUsersEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ChatUsersListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  ChatUsersEvent  $event
     * @return void
     */
    public function handle(ChatUsersEvent $event)
    {
        \Log::info("listenderrrr");
        return $event;
    }
}
