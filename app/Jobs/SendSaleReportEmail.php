<?php

namespace App\Jobs;

use App\Models\SaleReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendSaleReportEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $emails;
    protected $saleReport;
    protected $subject;
    protected $body;

    public function __construct(array $emails, SaleReport $saleReport, string $subject, string $body)
    {
        $this->emails = $emails;
        $this->saleReport = $saleReport;
        $this->subject = $subject;
        $this->body = $body;
    }

    public function handle()
    {
        Mail::send([], [], function($message) {
            $message->to($this->emails)
                    ->subject($this->subject)
                    ->html($this->body)
                    ->attach(storage_path("app/public/{$this->saleReport->report_file_path}"));
        });

        $this->saleReport->update([
            'sent_at' => now(),
            'sent_to' => implode(',', $this->emails),
        ]);
    }
}
