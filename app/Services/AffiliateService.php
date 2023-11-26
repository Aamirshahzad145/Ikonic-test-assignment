<?php

namespace App\Services;

use App\Models\User;
use App\Models\Order;
use App\Models\Merchant;
use App\Models\Affiliate;
use Illuminate\Support\Str;
use App\Mail\AffiliateCreated;
use Illuminate\Support\Facades\Mail;
use App\Exceptions\AffiliateCreateException;

class AffiliateService
{
    public function __construct(
        protected ApiService $apiService
    ) {}

    /**
     * Create a new affiliate for the merchant with the given commission rate.
     *
     * @param  Merchant $merchant
     * @param  string $email
     * @param  string $name
     * @param  float $commissionRate
     * @return Affiliate
     */
    public function register(Merchant $merchant, string $email, string $name, float $commissionRate): Affiliate
    {
        // TODO: Complete this method
        try {
            if ($merchant->user->email === $email) {
                throw new AffiliateCreateException('Email is already in use by the merchant.');
            }

            if (Affiliate::whereHas('user', function ($query) use ($email) {
                $query->where('email', $email);
            })->exists()) {
                throw new AffiliateCreateException('Email is already in use by another affiliate.');
            }

            $discountCode = $this->apiService->createDiscountCode($merchant);

            $user = User::create([
                'email' => $email,
                'name' => $name,
                'password' => bcrypt(Str::random(16)),
                'remember_token' => Str::random(10),
                'type' => User::TYPE_AFFILIATE,
            ]);

            $affiliate = Affiliate::create([
                'merchant_id' => $merchant->id,
                'user_id' => $user->id,
                'commission_rate' => $commissionRate,
                'discount_code' =>  $discountCode['code'],
            ]);

            Mail::to($email)->send(new AffiliateCreated($affiliate));

            return $affiliate;
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
