<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    // Supported document categories
    public const TYPES = [
        'standard' => 'Standard Invoice',
        'proforma' => 'Proforma Invoice',
        'commercial' => 'Commercial Invoice',
        'tax' => 'Tax Invoice',
        'recurring' => 'Recurring Invoice',
        'credit_note' => 'Credit Note',
        'debit_note' => 'Debit Note',
    ];

    protected $fillable = [
        'customer_id',
        'created_by',
        'related_invoice_id',
        'invoice_number',
        'invoice_type',
        'issue_date',
        'due_date',
        'subtotal',
        'tax',
        'total',
        'status',
        'notes',
        'currency_code',
        'last_reminder_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'tax' => 'decimal:2',
            'total' => 'decimal:2',
            'last_reminder_sent_at' => 'datetime',
        ];
    }

    // Default invoice type
    protected $attributes = [
        'invoice_type' => 'standard',
    ];

    // Invoice belongs to a customer
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Invoice has many items
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    // Invoice has many payments
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // Credit/Debit note references its original invoice
    public function relatedInvoice(): BelongsTo
    {
        return $this->belongsTo(
            Invoice::class,
            'related_invoice_id'
        );
    }

    // Original invoice can have related credit/debit notes
    public function relatedInvoices(): HasMany
    {
        return $this->hasMany(
            Invoice::class,
            'related_invoice_id'
        );
    }

    // Get document type label
    public function getInvoiceTypeLabelAttribute(): string
    {
        return self::TYPES[$this->invoice_type]
            ?? 'Unknown Type';
    }

    // Get currency symbol
    public function getCurrencySymbolAttribute(): string
    {
        return match ($this->currency_code ?: 'KES') {
            'KES' => 'KSh',
            'USD' => '$',
            'GBP' => '£',
            'EUR' => '€',
            'TZS' => 'TSh',
            'UGX' => 'USh',
            default => $this->currency_code ?: 'KSh',
        };
    }
}
