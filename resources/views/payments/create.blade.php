<x-app-layout>
    <div class="container-fluid">
        <div class="mb-4">
            <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Back to Payments
            </a>
        </div>

        <div class="card content-card mx-auto" style="max-width: 760px;">
            <div class="card-body p-4 p-md-5">
                <h1 class="section-heading mb-1">Record Payment</h1>
                <p class="section-description mb-4">Add a completed payment to an open invoice.</p>

                @if($errors->any())
                    <div class="alert alert-danger" role="alert">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if($invoices->isEmpty())
                    <div class="alert alert-info mb-0">
                        There are no unpaid invoices available for payment.
                    </div>
                @else
                    <form id="payment-form" action="{{ route('payments.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="invoice_id" class="form-label fw-semibold">Invoice</label>
                            <select id="invoice_id" name="invoice_id" class="form-select @error('invoice_id') is-invalid @enderror" required>
                                <option value="">Choose an invoice</option>
                                @foreach($invoices as $invoice)
                                    <option value="{{ $invoice->id }}" @selected(old('invoice_id', $selectedInvoice?->id) == $invoice->id)>
                                        {{ $invoice->invoice_number }} — {{ $invoice->customer->name }}
                                        · Due {{ $invoice->currency_symbol }} {{ number_format($invoice->balance_due, 2) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('invoice_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">The available balance excludes completed payments and pending payment requests.</div>
                        </div>

                        <div class="mb-3">
                            <label for="amount" class="form-label fw-semibold">Amount</label>
                            <input id="amount" type="number" name="amount"
                                   class="form-control @error('amount') is-invalid @enderror"
                                   step="{{ $selectedPaymentMethod === 'mpesa' ? '1' : '0.01' }}"
                                   min="{{ $selectedPaymentMethod === 'mpesa' ? '1' : '0.01' }}"
                                   value="{{ old('amount', $defaultAmount) }}" required>
                            @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="payment_method" class="form-label fw-semibold">Payment method</label>
                                <select id="payment_method" name="payment_method"
                                        class="form-select @error('payment_method') is-invalid @enderror" required>
                                    <option value="">Select a method</option>
                                    <option value="cash" @selected(old('payment_method', $selectedPaymentMethod) === 'cash')>Cash</option>
                                    <option value="bank_transfer" @selected(old('payment_method', $selectedPaymentMethod) === 'bank_transfer')>Bank transfer</option>
                                    <option value="card" @selected(old('payment_method', $selectedPaymentMethod) === 'card')>Card</option>
                                    <option value="mpesa" @selected(old('payment_method', $selectedPaymentMethod) === 'mpesa')>M-Pesa STK push</option>
                                </select>
                                @error('payment_method')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="payment_date" class="form-label fw-semibold">Payment date</label>
                                <input id="payment_date" type="date" name="payment_date"
                                       class="form-control @error('payment_date') is-invalid @enderror"
                                       max="{{ now()->toDateString() }}"
                                       value="{{ old('payment_date', now()->toDateString()) }}" required>
                                @error('payment_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div id="mpesa-phone-field" class="mt-3 mb-3 d-none">
                            <label for="phone_number" class="form-label fw-semibold">M-Pesa phone number</label>
                            <input id="phone_number" type="tel" name="phone_number"
                                   class="form-control @error('phone_number') is-invalid @enderror"
                                   value="{{ old('phone_number', $selectedInvoice?->customer->phone) }}" placeholder="07XXXXXXXX or 2547XXXXXXXX"
                                   autocomplete="tel" disabled>
                            @error('phone_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">The customer must approve the STK prompt on their phone. M-Pesa requires a KES invoice and a whole-shilling amount.</div>
                        </div>

                        <div class="mt-3 mb-4">
                            <label for="transaction_reference" class="form-label fw-semibold">Transaction reference <span class="text-muted fw-normal">(optional)</span></label>
                            <input id="transaction_reference" type="text" name="transaction_reference"
                                   class="form-control @error('transaction_reference') is-invalid @enderror"
                                   maxlength="255" value="{{ old('transaction_reference') }}">
                            @error('transaction_reference')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary">Cancel</a>
                            <button id="submit-payment" type="submit" class="btn btn-primary">
                                <i class="bi bi-check2-circle me-1"></i> Save Payment
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <script>
        const paymentForm = document.getElementById('payment-form');
        const paymentMethod = document.getElementById('payment_method');
        const phoneField = document.getElementById('mpesa-phone-field');
        const phoneInput = document.getElementById('phone_number');
        const referenceInput = document.getElementById('transaction_reference');
        const paymentButton = document.getElementById('submit-payment');
        const amountInput = document.getElementById('amount');

        function updatePaymentForm() {
            const isMpesa = paymentMethod.value === 'mpesa';
            paymentForm.action = isMpesa
                ? @json(route('payments.mpesa.initiate'))
                : @json(route('payments.store'));
            phoneField.classList.toggle('d-none', !isMpesa);
            phoneInput.disabled = !isMpesa;
            phoneInput.required = isMpesa;
            referenceInput.disabled = isMpesa;
            amountInput.step = isMpesa ? '1' : '0.01';
            amountInput.min = isMpesa ? '1' : '0.01';
            paymentButton.innerHTML = isMpesa
                ? '<i class="bi bi-phone me-1"></i> Send STK Prompt'
                : '<i class="bi bi-check2-circle me-1"></i> Save Payment';
        }

        paymentMethod.addEventListener('change', updatePaymentForm);
        updatePaymentForm();
    </script>
</x-app-layout>
