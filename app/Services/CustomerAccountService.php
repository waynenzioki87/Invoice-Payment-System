<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\User;

class CustomerAccountService
{
    public function forUser(User $user): Customer
    {
        $customer = $user->customer;
        if ($customer) {
            return $customer;
        }

        $matches = Customer::where('email', $user->email)
            ->whereNull('user_id')
            ->get();

        if ($matches->count() === 1) {
            $customer = $matches->first();
            $customer->update(['user_id' => $user->id]);

            return $customer;
        }

        return Customer::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ]);
    }
}
