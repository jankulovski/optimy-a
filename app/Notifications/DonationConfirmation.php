<?php

namespace App\Notifications;

use App\Models\Donation;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Campaign;

class DonationConfirmation extends Notification implements ShouldQueue
{
    use Queueable;

    public Donation $donation;

    /**
     * Create a new notification instance.
     */
    public function __construct(Donation $donation)
    {
        $this->donation = $donation;
        // Ensure campaign is loaded for reliable access, or load it in the listener
        // $this->donation->loadMissing('campaign'); 
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(User $notifiable): MailMessage
    {
        $campaignTitle = 'Unknown Campaign';
        if ($this->donation->campaign instanceof Campaign) {
            $campaignTitle = $this->donation->campaign->title;
        }

        return (new MailMessage)
                    ->subject('Donation Confirmation')
                    ->greeting('Hello ' . $notifiable->name . ',')
                    ->line('Thank you for your generous donation of $' . $this->donation->amount . ' to the campaign: ' . $campaignTitle . '.')
                    ->line('Your support is greatly appreciated!')
                    ->action('View Donation', url('/donations/' . $this->donation->id))
                    ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(User $notifiable): array
    {
        return [
            //
        ];
    }
}
