<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'address',
    ];

    // A customer can be linked to one user account.
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // One customer can have many invoices.
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
