<?php

namespace App\Http\Controllers;

use App\Models\BusinessSetting;
use App\Models\Customer;
use App\Models\Invoice;
use App\Mail\InvoiceNotificationMail;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class InvoiceController extends Controller
{
    // Display all invoices
    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:255',
            'status' => 'nullable|in:unpaid,partial,paid,overdue,draft',
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ]);

        $invoices = Invoice::with(['customer', 'relatedInvoice'])
            ->when($validated['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('invoice_number', 'like', '%' . $search . '%')
                        ->orWhereHas('customer', function ($customerQuery) use ($search): void {
                            $customerQuery->where('name', 'like', '%' . $search . '%');
                        });
                });
            })
            ->when($validated['status'] ?? null, function ($query, string $status): void {
                if ($status === 'overdue') {
                    $query->whereIn('status', ['unpaid', 'partial', 'overdue'])
                        ->whereDate('due_date', '<', now()->toDateString());
                } else {
                    $query->where('status', $status);
                }
            })
            ->when($validated['from'] ?? null, fn ($query, string $from) => $query->whereDate('issue_date', '>=', $from))
            ->when($validated['to'] ?? null, fn ($query, string $to) => $query->whereDate('issue_date', '<=', $to))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('invoices.index', compact('invoices'));
    }

    // Display invoice creation form
    public function create()
    {
        $customers = Customer::orderBy('name')->get();

        $defaultCurrency = BusinessSetting::value(
            'default_currency_code'
        ) ?? 'KES';

        $currencies = [
            'KES' => 'Kenyan Shilling (KES)',
            'USD' => 'US Dollar (USD)',
            'GBP' => 'British Pound (GBP)',
            'EUR' => 'Euro (EUR)',
            'TZS' => 'Tanzanian Shilling (TZS)',
            'UGX' => 'Ugandan Shilling (UGX)',
        ];

        $invoiceTypes = Invoice::TYPES;
        $suggestedInvoiceNumber = $this->suggestedInvoiceNumber();

        // Invoices available to reference for credit/debit notes
        $originalInvoices = Invoice::whereIn('invoice_type', [
            'standard',
            'commercial',
            'tax',
            'recurring',
        ])
            ->with('customer')
            ->latest()
            ->get();

        return view('invoices.create', compact(
            'customers',
            'defaultCurrency',
            'currencies',
            'invoiceTypes',
            'originalInvoices',
            'suggestedInvoiceNumber'
        ));
    }

    // Save a new invoice and its items
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',

            'invoice_number' => 'required|string|max:255|unique:invoices,invoice_number',

            'invoice_type' => 'required|string|in:standard,proforma,commercial,tax,recurring,credit_note,debit_note',

            'related_invoice_id' => 'nullable|required_if:invoice_type,credit_note,debit_note|exists:invoices,id',

            'issue_date' => 'required|date',

            'due_date' => 'required|date|after_or_equal:issue_date',

            'notes' => 'nullable|string',

            'currency_code' => 'required|string|in:KES,USD,GBP,EUR,TZS,UGX',

            'tax_rate' => 'required|numeric|min:0|max:100',

            'items' => 'required|array|min:1',

            'items.*.description' => 'required|string|max:255',

            'items.*.quantity' => 'required|integer|min:1',

            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        // Validate original invoice for credit/debit notes
        if (in_array($validated['invoice_type'], [
            'credit_note',
            'debit_note',
        ])) {
            $originalInvoice = Invoice::findOrFail(
                $validated['related_invoice_id']
            );

            // The note must belong to the same customer
            if ($originalInvoice->customer_id != $validated['customer_id']) {
                throw ValidationException::withMessages([
                    'related_invoice_id' => 'The original invoice must belong to the selected customer.',
                ]);
            }

            // Prevent notes referencing another adjustment note
            if (in_array($originalInvoice->invoice_type, [
                'credit_note',
                'debit_note',
                'proforma',
            ])) {
                throw ValidationException::withMessages([
                    'related_invoice_id' => 'Select a valid original invoice, not a proforma or adjustment note.',
                ]);
            }
        }

        $currencyCode = $validated['currency_code'];
        $invoiceType = $validated['invoice_type'];

        $invoice = DB::transaction(function () use (
            $validated,
            $currencyCode,
            $invoiceType
        ) {
            // Calculate subtotal
            $subtotal = 0;

            foreach ($validated['items'] as $item) {
                $subtotal +=
                    $item['quantity'] * $item['unit_price'];
            }

            $subtotal = round($subtotal, 2);

            // Calculate tax
            $tax = round(
                $subtotal * ($validated['tax_rate'] / 100),
                2
            );

            // Calculate total
            $total = round($subtotal + $tax, 2);

            /*
             * Document status rules:
             *
             * Standard/Commercial/Tax = unpaid
             * Proforma/Recurring = draft
             * Credit/Debit Note = draft
             *
             * Draft documents cannot be paid through
             * the normal payment workflow.
             */

            if (in_array($invoiceType, [
                'proforma',
                'recurring',
                'credit_note',
                'debit_note',
            ])) {
                $status = 'draft';
            } else {
                $status = 'unpaid';
            }

            // Create document
            $invoice = Invoice::create([
                'customer_id' => $validated['customer_id'],
                'created_by' => Auth::id(),

                'related_invoice_id' => $validated['related_invoice_id'] ?? null,

                'invoice_number' => $validated['invoice_number'],

                'invoice_type' => $invoiceType,

                'issue_date' => $validated['issue_date'],

                'due_date' => $validated['due_date'],

                'notes' => $validated['notes'] ?? null,

                'subtotal' => $subtotal,

                'tax' => $tax,

                'total' => $total,

                'currency_code' => $currencyCode,

                'status' => $status,
            ]);

            // Save invoice items
            foreach ($validated['items'] as $item) {
                $amount = round(
                    $item['quantity'] * $item['unit_price'],
                    2
                );

                $invoice->items()->create([
                    'description' => $item['description'],

                    'quantity' => $item['quantity'],

                    'unit_price' => $item['unit_price'],

                    'amount' => $amount,
                ]);
            }

            return $invoice;
        });

        $invoice->load('customer');
        if ($invoice->customer->email) {
            Mail::to($invoice->customer->email)->queue(new InvoiceNotificationMail($invoice));
        }

        return redirect()
            ->route('invoices.index')
            ->with('success', 'Document created successfully.');
    }

    // Display one invoice/document
    public function show(Invoice $invoice)
    {
        $invoice->load([
            'customer',
            'items',
            'payments',
            'relatedInvoice.customer',
            'relatedInvoices',
        ]);

        return view('invoices.show', compact('invoice'));
    }

    public function downloadPdf(Invoice $invoice)
    {
        $invoice->load(['customer', 'items', 'payments']);
        $businessSettings = BusinessSetting::first();

        return Pdf::loadView('invoices.pdf', compact('invoice', 'businessSettings'))
            ->setPaper('a4')
            ->download('invoice-'.$invoice->invoice_number.'.pdf');
    }

    public function print(Invoice $invoice)
    {
        $invoice->load(['customer', 'items', 'payments']);
        $businessSettings = BusinessSetting::first();

        return view('invoices.pdf', compact('invoice', 'businessSettings'));
    }

    public function duplicate(Invoice $invoice)
    {
        $invoice->load('items');

        $copy = DB::transaction(function () use ($invoice): Invoice {
            $copy = $invoice->replicate();
            $copy->invoice_number = $this->suggestedInvoiceNumber();
            $copy->created_by = Auth::id();
            $copy->status = in_array($invoice->invoice_type, [
                'proforma',
                'recurring',
                'credit_note',
                'debit_note',
            ], true) ? 'draft' : 'unpaid';
            $copy->save();

            foreach ($invoice->items as $item) {
                $copy->items()->create([
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'amount' => $item->amount,
                ]);
            }

            return $copy;
        });

        return redirect()
            ->route('invoices.show', $copy)
            ->with('success', 'Invoice duplicated. Review the new invoice before sending it.');
    }

    // Display invoice editing form
    public function edit(Invoice $invoice)
    {
        $customers = Customer::orderBy('name')->get();

        $currencies = [
            'KES' => 'Kenyan Shilling (KES)',
            'USD' => 'US Dollar (USD)',
            'GBP' => 'British Pound (GBP)',
            'EUR' => 'Euro (EUR)',
            'TZS' => 'Tanzanian Shilling (TZS)',
            'UGX' => 'Ugandan Shilling (UGX)',
        ];

        return view('invoices.edit', compact(
            'invoice',
            'customers',
            'currencies'
        ));
    }

    // Update invoice
    public function update(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',

            'issue_date' => 'required|date',

            'due_date' => 'required|date|after_or_equal:issue_date',

            'notes' => 'nullable|string',

            'currency_code' => 'required|string|in:KES,USD,GBP,EUR,TZS,UGX',
        ]);

        if ($invoice->payments()->whereIn('status', ['pending', 'completed'])->exists()
            && $validated['currency_code'] !== $invoice->currency_code) {
            throw ValidationException::withMessages([
                'currency_code' => 'The currency cannot be changed after a payment has been initiated or recorded.',
            ]);
        }

        $invoice->update($validated);

        return redirect()
            ->route('invoices.index')
            ->with('success', 'Document updated successfully.');
    }

    // Delete invoice
    public function destroy(Invoice $invoice)
    {
        if ($invoice->payments()->exists()) {
            return redirect()
                ->route('invoices.index')
                ->withErrors([
                    'invoice' => 'Invoices with payment records cannot be deleted.',
                ]);
        }

        $invoice->delete();

        return redirect()
            ->route('invoices.index')
            ->with('success', 'Document deleted successfully.');
    }

    private function suggestedInvoiceNumber(): string
    {
        $prefix = 'INV-' . now()->format('Y') . '-';
        $sequence = Invoice::where('invoice_number', 'like', $prefix . '%')->count() + 1;

        do {
            $invoiceNumber = $prefix . str_pad((string) $sequence++, 4, '0', STR_PAD_LEFT);
        } while (Invoice::where('invoice_number', $invoiceNumber)->exists());

        return $invoiceNumber;
    }
}
