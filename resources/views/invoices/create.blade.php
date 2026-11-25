<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Create Document | INVOICEX</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --navy: #111827;
            --navy-light: #1f2937;
            --purple: #6d5dfc;
            --purple-light: #edeaff;
            --bg: #f4f6fb;
            --border: #e5e7eb;
            --muted: #6b7280;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--navy);
        }

        .navbar {
            background: var(--navy);
            padding: 16px 0;
        }

        .brand {
            font-size: 22px;
            font-weight: 800;
            color: white;
            letter-spacing: 1px;
        }

        .brand span {
            color: #a99cff;
        }

        .page-header {
            margin-bottom: 28px;
        }

        .page-header h1 {
            font-size: 28px;
            font-weight: 800;
        }

        .page-header p {
            color: var(--muted);
            margin-bottom: 0;
        }

        .card-custom {
            border: 1px solid var(--border);
            border-radius: 16px;
            background: white;
            box-shadow: 0 4px 20px rgba(17, 24, 39, 0.03);
        }

        .card-heading {
            font-size: 16px;
            font-weight: 700;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 22px;
        }

        .form-label {
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }

        .form-control,
        .form-select {
            border: 1px solid #dfe3eb;
            border-radius: 9px;
            padding: 11px 13px;
            font-size: 14px;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--purple);
            box-shadow: 0 0 0 3px rgba(109, 93, 252, 0.12);
        }

        .input-group-text {
            border-radius: 9px;
            background: #f9fafb;
            border-color: #dfe3eb;
            font-size: 13px;
            font-weight: 600;
        }

        .type-badge {
            display: inline-block;
            padding: 7px 12px;
            border-radius: 30px;
            background: var(--purple-light);
            color: var(--purple);
            font-size: 12px;
            font-weight: 700;
        }

        .items-table thead th {
            background: #f8fafc;
            font-size: 12px;
            font-weight: 700;
            color: #6b7280;
            padding: 13px;
            white-space: nowrap;
        }

        .items-table td {
            padding: 10px;
            vertical-align: middle;
        }

        .items-table input {
            min-width: 80px;
        }

        .description-input {
            min-width: 180px !important;
        }

        .remove-btn {
            border: none;
            background: #fff1f2;
            color: #e11d48;
            width: 34px;
            height: 34px;
            border-radius: 8px;
            font-weight: bold;
        }

        .remove-btn:hover {
            background: #ffe4e6;
        }

        .btn-purple {
            background: var(--purple);
            color: white;
            border: none;
            border-radius: 9px;
            padding: 11px 18px;
            font-weight: 600;
        }

        .btn-purple:hover {
            background: #5746e8;
            color: white;
        }

        .btn-outline-custom {
            border: 1px solid var(--border);
            background: white;
            color: var(--navy);
            border-radius: 9px;
            padding: 10px 16px;
            font-weight: 600;
        }

        .btn-outline-custom:hover {
            background: #f9fafb;
        }

        .summary-box {
            background: #f8fafc;
            border-radius: 12px;
            padding: 20px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 13px;
            color: #6b7280;
            font-size: 14px;
        }

        .summary-total {
            border-top: 1px solid #dfe3eb;
            padding-top: 15px;
            margin-top: 15px;
            color: var(--navy);
            font-size: 18px;
            font-weight: 800;
        }

        .summary-total span:last-child {
            color: var(--purple);
        }

        .info-alert {
            border-radius: 10px;
            padding: 13px 15px;
            font-size: 13px;
            margin-top: 16px;
        }

        .draft-alert {
            background: #fff7ed;
            border: 1px solid #fed7aa;
            color: #9a3412;
        }

        .payable-alert {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #065f46;
        }

        .hidden-section {
            display: none;
        }

        .footer-actions {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        @media (max-width: 768px) {
            .page-header h1 {
                font-size: 23px;
            }

            .items-table {
                min-width: 700px;
            }

            .footer-actions {
                flex-direction: column;
            }

            .footer-actions button,
            .footer-actions a {
                width: 100%;
            }
        }
    </style>
</head>

<body>

<!-- NAVBAR -->
<nav class="navbar">
    <div class="container">
        <div class="brand">
            INVOICE<span>X</span>
        </div>
    </div>
</nav>

<div class="container py-4 py-md-5">

    <!-- PAGE HEADER -->
    <div class="page-header d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <h1>Create New Document</h1>
            <p>Create and manage professional invoices and financial documents.</p>
        </div>

        <span class="type-badge">DOCUMENT MANAGEMENT</span>
    </div>

    <!-- VALIDATION ERRORS -->
    @if ($errors->any())
        <div class="alert alert-danger rounded-3 mb-4">
            <strong>Please correct the following:</strong>

            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('invoices.store') }}" method="POST" id="invoiceForm">
        @csrf

        <div class="row g-4">

            <!-- LEFT COLUMN -->
            <div class="col-lg-8">

                <!-- DOCUMENT DETAILS -->
                <div class="card-custom p-4 mb-4">

                    <h5 class="card-heading">
                        Document Details
                    </h5>

                    <div class="row g-3">

                        <!-- DOCUMENT TYPE -->
                        <div class="col-md-6">
                            <label class="form-label">
                                Document Type *
                            </label>

                            <select name="invoice_type"
                                    id="invoice_type"
                                    class="form-select"
                                    required>

                                @foreach ($invoiceTypes as $type => $label)
                                    <option value="{{ $type }}"
                                        {{ old('invoice_type', 'standard') == $type ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach

                            </select>
                        </div>

                        <!-- INVOICE NUMBER -->
                        <div class="col-md-6">
                            <label class="form-label">
                                Document Number *
                            </label>

                            <input type="text"
                                   name="invoice_number"
                                   class="form-control"
                                   placeholder="INV-2026-001"
                                   value="{{ old('invoice_number', $suggestedInvoiceNumber) }}"
                                   required>
                        </div>

                        <!-- CUSTOMER -->
                        <div class="col-md-6">
                            <label class="form-label">
                                Customer *
                            </label>

                            <select name="customer_id"
                                    id="customer_id"
                                    class="form-select"
                                    required>

                                <option value="">Select customer</option>

                                @foreach ($customers as $customer)
                                    <option value="{{ $customer->id }}"
                                        {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                        {{ $customer->name }}
                                    </option>
                                @endforeach

                            </select>
                        </div>

                        <!-- CURRENCY -->
                        <div class="col-md-6">
                            <label class="form-label">
                                Currency *
                            </label>

                            <select name="currency_code"
                                    id="currency_code"
                                    class="form-select"
                                    required>

                                @foreach ($currencies as $code => $label)
                                    <option value="{{ $code }}"
                                        {{ old('currency_code', $defaultCurrency) == $code ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach

                            </select>
                        </div>

                        <!-- ISSUE DATE -->
                        <div class="col-md-6">
                            <label class="form-label">
                                Issue Date *
                            </label>

                            <input type="date"
                                   name="issue_date"
                                   class="form-control"
                                   value="{{ old('issue_date', date('Y-m-d')) }}"
                                   required>
                        </div>

                        <!-- DUE DATE -->
                        <div class="col-md-6">
                            <label class="form-label">
                                Due Date *
                            </label>

                            <input type="date"
                                   name="due_date"
                                   class="form-control"
                                   value="{{ old('due_date', date('Y-m-d')) }}"
                                   required>
                        </div>

                        <!-- ORIGINAL INVOICE -->
                        <div class="col-12 hidden-section"
                             id="relatedInvoiceSection">

                            <label class="form-label">
                                Original Invoice *
                            </label>

                            <select name="related_invoice_id"
                                    id="related_invoice_id"
                                    class="form-select">

                                <option value="">Select original invoice</option>

                                @foreach ($originalInvoices as $original)
                                    <option value="{{ $original->id }}"
                                        {{ old('related_invoice_id') == $original->id ? 'selected' : '' }}>

                                        {{ $original->invoice_number }}
                                        —
                                        {{ $original->customer->name }}
                                        —
                                        {{ $original->currency_symbol }}
                                        {{ number_format($original->total, 2) }}

                                    </option>
                                @endforeach

                            </select>

                            <small class="text-muted">
                                Credit and Debit Notes must reference an original invoice belonging to the same customer.
                            </small>
                        </div>

                        <!-- NOTES -->
                        <div class="col-12">
                            <label class="form-label">
                                Notes
                            </label>

                            <textarea name="notes"
                                      class="form-control"
                                      rows="3"
                                      placeholder="Additional notes, terms or instructions...">{{ old('notes') }}</textarea>
                        </div>

                    </div>
                </div>

                <!-- ITEMS -->
                <div class="card-custom p-4 mb-4">

                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">

                        <h5 class="card-heading mb-0 border-0 pb-0">
                            Document Items
                        </h5>

                        <button type="button"
                                class="btn-outline-custom"
                                id="addItemBtn">
                            + Add Item
                        </button>

                    </div>

                    <div class="table-responsive">
                        <table class="table items-table align-middle">
                            <thead>
                                <tr>
                                    <th style="width: 40%">Description</th>
                                    <th style="width: 15%">Qty</th>
                                    <th style="width: 20%">Unit Price</th>
                                    <th style="width: 20%">Amount</th>
                                    <th style="width: 5%"></th>
                                </tr>
                            </thead>

                            <tbody id="itemsTableBody">

                                <!-- Initial item -->
                                <tr class="item-row">

                                    <td>
                                        <input type="text"
                                               name="items[0][description]"
                                               class="form-control description-input"
                                               placeholder="Item description"
                                               required>
                                    </td>

                                    <td>
                                        <input type="number"
                                               name="items[0][quantity]"
                                               class="form-control quantity-input"
                                               value="1"
                                               min="1"
                                               step="1"
                                               required>
                                    </td>

                                    <td>
                                        <input type="number"
                                               name="items[0][unit_price]"
                                               class="form-control price-input"
                                               value="0"
                                               min="0"
                                               step="0.01"
                                               required>
                                    </td>

                                    <td>
                                        <span class="row-amount fw-semibold">
                                            0.00
                                        </span>
                                    </td>

                                    <td>
                                        <button type="button"
                                                class="remove-btn remove-item"
                                                title="Remove item">
                                            &times;
                                        </button>
                                    </td>

                                </tr>

                            </tbody>
                        </table>
                    </div>

                    <div class="text-muted small mt-2">
                        Add all goods or services included in this document.
                    </div>

                </div>

                <!-- TAX -->
                <div class="card-custom p-4">

                    <h5 class="card-heading">
                        Tax Configuration
                    </h5>

                    <div class="row">
                        <div class="col-md-5">

                            <label class="form-label">
                                Tax Rate (%)
                            </label>

                            <div class="input-group">
                                <input type="number"
                                       name="tax_rate"
                                       id="tax_rate"
                                       class="form-control"
                                       value="{{ old('tax_rate', 0) }}"
                                       min="0"
                                       max="100"
                                       step="0.01"
                                       required>

                                <span class="input-group-text">
                                    %
                                </span>
                            </div>

                            <small class="text-muted">
                                Enter the applicable tax rate.
                            </small>

                        </div>
                    </div>

                </div>

            </div>

            <!-- RIGHT COLUMN -->
            <div class="col-lg-4">

                <!-- SUMMARY -->
                <div class="card-custom p-4 mb-4">

                    <h5 class="card-heading">
                        Document Summary
                    </h5>

                    <div class="summary-box">

                        <div class="summary-row">
                            <span>Subtotal</span>
                            <span id="subtotalDisplay">0.00</span>
                        </div>

                        <div class="summary-row">
                            <span>Tax (<span id="taxRateDisplay">0</span>%)</span>
                            <span id="taxDisplay">0.00</span>
                        </div>

                        <div class="summary-row summary-total">
                            <span>Total</span>
                            <span id="totalDisplay">0.00</span>
                        </div>

                        <div class="mt-2 small text-muted">
                            Currency:
                            <strong id="currencyDisplay">
                                {{ old('currency_code', $defaultCurrency) }}
                            </strong>
                        </div>

                    </div>

                    <!-- TYPE INFORMATION -->
                    <div class="info-alert draft-alert hidden-section"
                         id="draftAlert">

                        <strong>Draft Document</strong><br>

                        This document will be saved as a draft and will not be available for normal payment processing.
                    </div>

                    <div class="info-alert payable-alert hidden-section"
                         id="payableAlert">

                        <strong>Payable Invoice</strong><br>

                        This document will be created with an unpaid status and can proceed through your payment workflow.
                    </div>

                </div>

                <!-- QUICK INFO -->
                <div class="card-custom p-4 mb-4">

                    <h5 class="card-heading">
                        Document Guide
                    </h5>

                    <div class="small text-muted" id="documentGuide">
                        Standard Invoice: Used to request payment for goods or services provided.
                    </div>

                </div>

                <!-- ACTIONS -->
                <div class="card-custom p-4">

                    <div class="footer-actions">

                        <a href="{{ route('invoices.index') }}"
                           class="btn-outline-custom text-center text-decoration-none">
                            Cancel
                        </a>

                        <button type="submit"
                                class="btn-purple"
                                id="submitBtn">
                            Create Document
                        </button>

                    </div>

                </div>

            </div>

        </div>

    </form>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {

        const itemsTableBody = document.getElementById('itemsTableBody');
        const addItemBtn = document.getElementById('addItemBtn');

        const invoiceType = document.getElementById('invoice_type');
        const relatedInvoiceSection = document.getElementById('relatedInvoiceSection');
        const relatedInvoiceSelect = document.getElementById('related_invoice_id');

        const currencySelect = document.getElementById('currency_code');
        const currencyDisplay = document.getElementById('currencyDisplay');

        const taxRateInput = document.getElementById('tax_rate');

        const subtotalDisplay = document.getElementById('subtotalDisplay');
        const taxDisplay = document.getElementById('taxDisplay');
        const totalDisplay = document.getElementById('totalDisplay');
        const taxRateDisplay = document.getElementById('taxRateDisplay');

        const draftAlert = document.getElementById('draftAlert');
        const payableAlert = document.getElementById('payableAlert');
        const documentGuide = document.getElementById('documentGuide');
        const submitBtn = document.getElementById('submitBtn');

        let itemIndex = 1;

        const documentGuides = {
            standard: 'Standard Invoice: Used to request payment for goods or services provided.',
            proforma: 'Proforma Invoice: A preliminary quotation or estimated invoice. It is saved as a draft.',
            commercial: 'Commercial Invoice: A document describing goods involved in a commercial transaction, commonly used for trade.',
            tax: 'Tax Invoice: A document showing the transaction details and applicable tax.',
            recurring: 'Recurring Invoice: A billing template intended for repeated billing. This version is saved as a draft.',
            credit_note: 'Credit Note: Used to reduce or correct the amount owed on an original invoice. It is saved as a draft.',
            debit_note: 'Debit Note: Used to increase or correct the amount owed on an original invoice. It is saved as a draft.'
        };

        const draftTypes = [
            'proforma',
            'recurring',
            'credit_note',
            'debit_note'
        ];

        // Format money
        function formatMoney(amount) {
            return Number(amount || 0).toLocaleString(
                'en-KE',
                {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }
            );
        }

        // Update individual item amounts
        function updateItemAmounts() {

            let subtotal = 0;

            document.querySelectorAll('.item-row').forEach(function (row) {

                const quantity =
                    parseFloat(row.querySelector('.quantity-input').value) || 0;

                const price =
                    parseFloat(row.querySelector('.price-input').value) || 0;

                const amount = quantity * price;

                row.querySelector('.row-amount').textContent =
                    formatMoney(amount);

                subtotal += amount;
            });

            // Tax calculation
            const taxRate =
                parseFloat(taxRateInput.value) || 0;

            const tax = subtotal * (taxRate / 100);

            const total = subtotal + tax;

            // Update summary
            subtotalDisplay.textContent = formatMoney(subtotal);
            taxDisplay.textContent = formatMoney(tax);
            totalDisplay.textContent = formatMoney(total);

            taxRateDisplay.textContent = taxRate;

            return {
                subtotal: subtotal,
                tax: tax,
                total: total
            };
        }

        // Add item
        addItemBtn.addEventListener('click', function () {

            const row = document.createElement('tr');

            row.classList.add('item-row');

            row.innerHTML = `
                <td>
                    <input type="text"
                           name="items[${itemIndex}][description]"
                           class="form-control description-input"
                           placeholder="Item description"
                           required>
                </td>

                <td>
                    <input type="number"
                           name="items[${itemIndex}][quantity]"
                           class="form-control quantity-input"
                           value="1"
                           min="1"
                           step="1"
                           required>
                </td>

                <td>
                    <input type="number"
                           name="items[${itemIndex}][unit_price]"
                           class="form-control price-input"
                           value="0"
                           min="0"
                           step="0.01"
                           required>
                </td>

                <td>
                    <span class="row-amount fw-semibold">0.00</span>
                </td>

                <td>
                    <button type="button"
                            class="remove-btn remove-item"
                            title="Remove item">
                        &times;
                    </button>
                </td>
            `;

            itemsTableBody.appendChild(row);

            itemIndex++;

            updateItemAmounts();
        });

        // Remove item
        itemsTableBody.addEventListener('click', function (event) {

            if (event.target.closest('.remove-item')) {

                const rows =
                    itemsTableBody.querySelectorAll('.item-row');

                // Keep at least one item
                if (rows.length > 1) {

                    event.target.closest('.item-row').remove();

                    updateItemAmounts();
                }
            }
        });

        // Live calculations
        itemsTableBody.addEventListener('input', function () {
            updateItemAmounts();
        });

        taxRateInput.addEventListener('input', function () {
            updateItemAmounts();
        });

        // Currency update
        currencySelect.addEventListener('change', function () {

            currencyDisplay.textContent = currencySelect.value;

        });

        // Document type behavior
        function updateDocumentType() {

            const type = invoiceType.value;

            const isDraft = draftTypes.includes(type);

            const requiresOriginal =
                type === 'credit_note' || type === 'debit_note';

            // Original invoice selection
            relatedInvoiceSection.classList.toggle(
                'hidden-section',
                !requiresOriginal
            );

            relatedInvoiceSelect.required = requiresOriginal;

            if (!requiresOriginal) {
                relatedInvoiceSelect.value = '';
            }

            // Draft/payable information
            draftAlert.classList.toggle(
                'hidden-section',
                !isDraft
            );

            payableAlert.classList.toggle(
                'hidden-section',
                isDraft
            );

            // Update guide
            documentGuide.textContent =
                documentGuides[type] || '';

            // Update submit button
            submitBtn.textContent =
                isDraft ? 'Save Draft' : 'Create Invoice';
        }

        invoiceType.addEventListener(
            'change',
            updateDocumentType
        );

        // Initial page state
        updateDocumentType();
        updateItemAmounts();

    });
</script>

</body>
</html>