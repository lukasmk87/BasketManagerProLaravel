<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Invoice Number Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for invoice number generation.
    |
    */

    'number_prefix' => env('INVOICE_NUMBER_PREFIX', 'INV'),
    'number_sequence_yearly' => env('INVOICE_SEQUENCE_YEARLY', true),

    /*
    |--------------------------------------------------------------------------
    | Default Tax Rate
    |--------------------------------------------------------------------------
    |
    | The default tax rate (VAT/MwSt) to apply to invoices.
    | Default is 19% for Germany.
    |
    */

    'default_tax_rate' => env('INVOICE_DEFAULT_TAX_RATE', 19.00),

    /*
    |--------------------------------------------------------------------------
    | Payment Terms
    |--------------------------------------------------------------------------
    |
    | Number of days until an invoice is due after being sent.
    |
    */

    'payment_terms_days' => env('INVOICE_PAYMENT_TERMS_DAYS', 14),

    /*
    |--------------------------------------------------------------------------
    | Reminder Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for automatic payment reminders.
    |
    */

    'reminders' => [
        'enabled' => env('INVOICE_REMINDERS_ENABLED', true),
        'intervals' => [7, 14, 21], // Days after due date
        'max_reminders' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Subscription Suspension
    |--------------------------------------------------------------------------
    |
    | Settings for automatic subscription suspension due to non-payment.
    |
    */

    'suspension' => [
        'enabled' => env('INVOICE_SUSPENSION_ENABLED', true),
        'days_after_due' => env('INVOICE_SUSPENSION_DAYS', 30),
        'warning_days_before' => 7,
    ],

    /*
    |--------------------------------------------------------------------------
    | PDF Storage
    |--------------------------------------------------------------------------
    |
    | Configuration for storing generated invoice PDFs.
    |
    */

    'pdf' => [
        'storage_disk' => env('INVOICE_PDF_DISK', 'local'),
        'storage_path' => env('INVOICE_PDF_PATH', 'invoices'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Company Information
    |--------------------------------------------------------------------------
    |
    | Your company information that appears on invoices.
    |
    */

    'company' => [
        'name' => env('INVOICE_COMPANY_NAME', 'BasketManager Pro'),
        'address_line1' => env('INVOICE_COMPANY_ADDRESS_LINE1', ''),
        'address_line2' => env('INVOICE_COMPANY_ADDRESS_LINE2', ''),
        'zip' => env('INVOICE_COMPANY_ZIP', ''),
        'city' => env('INVOICE_COMPANY_CITY', ''),
        'country' => env('INVOICE_COMPANY_COUNTRY', 'Deutschland'),
        'email' => env('INVOICE_COMPANY_EMAIL', ''),
        'phone' => env('INVOICE_COMPANY_PHONE', ''),
        'website' => env('INVOICE_COMPANY_WEBSITE', ''),
        'vat_number' => env('INVOICE_COMPANY_VAT_NUMBER', ''),
        'tax_number' => env('INVOICE_COMPANY_TAX_NUMBER', ''),
        'register_court' => env('INVOICE_COMPANY_REGISTER_COURT', ''),
        'register_number' => env('INVOICE_COMPANY_REGISTER_NUMBER', ''),
        'managing_director' => env('INVOICE_COMPANY_MANAGING_DIRECTOR', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Bank Information
    |--------------------------------------------------------------------------
    |
    | Bank account details for payment via bank transfer.
    |
    */

    'bank' => [
        'name' => env('INVOICE_BANK_NAME', ''),
        'iban' => env('INVOICE_BANK_IBAN', ''),
        'bic' => env('INVOICE_BANK_BIC', ''),
        'account_holder' => env('INVOICE_BANK_ACCOUNT_HOLDER', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for invoice-related emails.
    |
    */

    'email' => [
        'from_address' => env('INVOICE_EMAIL_FROM', env('MAIL_FROM_ADDRESS')),
        'from_name' => env('INVOICE_EMAIL_FROM_NAME', env('MAIL_FROM_NAME')),
        'reply_to' => env('INVOICE_EMAIL_REPLY_TO'),
        'cc' => env('INVOICE_EMAIL_CC'),
        'bcc' => env('INVOICE_EMAIL_BCC'),
    ],

];
