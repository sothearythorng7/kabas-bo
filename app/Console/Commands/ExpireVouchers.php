<?php

namespace App\Console\Commands;

use App\Services\VoucherService;
use Illuminate\Console\Command;

class ExpireVouchers extends Command
{
    protected $signature = 'vouchers:expire';
    protected $description = 'Mark expired vouchers as expired based on their expiration date';

    public function handle(VoucherService $voucherService): int
    {
        $count = $voucherService->expireVouchers();

        $this->info("Expired {$count} voucher(s).");

        return Command::SUCCESS;
    }
}
