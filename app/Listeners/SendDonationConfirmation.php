<?php

namespace App\Listeners;

use App\Events\DonationReceived;
use App\Notifications\DonationConfirmation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class SendDonationConfirmation implements ShouldQueue
{
    use InteractsWithQueue;

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
    public function handle(DonationReceived $event): void
    {
        $donation = $event->donation;
        $donation->loadMissing('user', 'campaign'); // Load user and campaign if not already loaded

        if ($donation->user) { // Ensure user is loaded before sending notification
            Notification::send($donation->user, new DonationConfirmation($donation));
        }
    }
}
