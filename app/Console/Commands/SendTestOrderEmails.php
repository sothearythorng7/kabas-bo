<?php

namespace App\Console\Commands;

use App\Mail\OrderCancelledMail;
use App\Mail\OrderConfirmationMail;
use App\Mail\OrderShippedMail;
use App\Models\WebsiteOrder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendTestOrderEmails extends Command
{
    protected $signature = 'orders:send-test-emails {email : The email address to send test emails to}';
    protected $description = 'Send the 3 transactional order emails (confirmation, shipped, cancelled) to a test address';

    public function handle(): int
    {
        $email = $this->argument('email');

        $order = WebsiteOrder::where('payment_status', 'paid')
            ->whereHas('items')
            ->latest()
            ->first();

        if (! $order) {
            $this->error('No paid order with items found in the database.');
            return Command::FAILURE;
        }

        $order->load('items');

        $this->info("Using order {$order->order_number} ({$order->items->count()} items, \${$order->total})");
        $this->info("Sending to: {$email}");
        $this->newLine();

        // 1. Order Confirmation
        $this->components->task('Sending Order Confirmation email', function () use ($email, $order) {
            Mail::to($email)->send(new OrderConfirmationMail($order));
        });

        // 2. Order Shipped
        if (! $order->tracking_url) {
            $order->tracking_url = 'https://www.example.com/track/123456789';
        }

        $this->components->task('Sending Order Shipped email', function () use ($email, $order) {
            Mail::to($email)->send(new OrderShippedMail($order));
        });

        // 3. Order Cancelled
        $this->components->task('Sending Order Cancelled email', function () use ($email, $order) {
            Mail::to($email)->send(new OrderCancelledMail($order));
        });

        $this->newLine();
        $this->info('All 3 test emails sent successfully!');

        return Command::SUCCESS;
    }
}
