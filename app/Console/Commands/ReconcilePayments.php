<?php

namespace App\Console\Commands;

use App\Services\PaymentService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('payments:reconcile')]
#[Description('Cek ulang ke Midtrans transaksi online yang masih menunggu (cadangan bila webhook gagal)')]
class ReconcilePayments extends Command
{
    public function handle(PaymentService $payments): int
    {
        $result = $payments->reconcilePending();

        $this->info("Dicek {$result['checked']} transaksi, {$result['updated']} berubah status.");

        return self::SUCCESS;
    }
}
