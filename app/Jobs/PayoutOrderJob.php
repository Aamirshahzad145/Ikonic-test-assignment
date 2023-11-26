<?php

namespace App\Jobs;

use RuntimeException;
use App\Models\Order;
use App\Services\ApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class PayoutOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        public Order $order
    ) {}

    /**
     * Use the API service to send a payout of the correct amount.
     * Note: The order status must be paid if the payout is successful, or remain unpaid in the event of an exception.
     *
     * @return void
     */
    public function handle(ApiService $apiService)
    {
        // TODO: Complete this method
        
        DB::beginTransaction();

        try {
            $email = $this->order->affiliate->user->email;
            $amount = $this->order->commission_owed;

            $apiService->sendPayout($email, $amount);

            $this->order->update(['payout_status' => Order::STATUS_PAID]);

            DB::commit();
        } catch (RuntimeException $e) {
            Log::error('Payout failed for order ' . $this->order->id . ': ' . $e->getMessage());
            DB::rollBack();
            throw $e;
        }
    }
}
