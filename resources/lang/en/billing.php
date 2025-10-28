<?php

return [
    'title' => 'Billing',

    'invoices' => [
        'title' => 'Invoices',
        'history' => 'Invoice History',
        'all_for_club' => 'All invoices for :club',
        'upcoming' => 'Next Invoice',
        'upcoming_preview' => 'Preview of Upcoming Billing',
        'invoice_number' => 'Invoice #:number',
        'no_invoices' => 'No Invoices Available',
        'no_invoices_created' => 'No invoices have been created yet.',
        'no_invoices_status' => 'No invoices found with status ":status".',
        'first_invoice_info' => 'Once your subscription is billed, your invoices will appear here.',
        'check_subscription' => 'Check your subscription status for more information.',
        'load_more' => 'Load More Invoices',
        'days_until' => 'Days Until Billing',
        'positions' => 'Line Items',
        'more_positions' => '+ :count more item(s)',
    ],

    'status' => [
        'label' => 'Status',
        'all' => 'All',
        'draft' => 'Draft',
        'open' => 'Open',
        'paid' => 'Paid',
        'uncollectible' => 'Uncollectible',
        'void' => 'Void',
        'overdue' => '(overdue)',
    ],

    'labels' => [
        'due_on' => 'Due On',
        'subtotal' => 'Subtotal',
        'tax' => 'VAT (:percent%)',
        'discount' => 'Discount',
        'total' => 'Total',
        'description' => 'Description',
        'period' => 'Period',
        'amount' => 'Amount',
    ],

    'actions' => [
        'details' => 'Details',
        'download_pdf' => 'PDF',
        'view_details' => 'View Details',
    ],

    'payment_methods' => [
        'title' => 'Payment Methods',
        'manage' => 'Manage your payment methods for :club',
        'add' => 'Add Payment Method',
        'default' => 'Default Payment Method',
        'set_default' => 'Set as Default',
        'remove' => 'Remove',
        'edit' => 'Edit',
        'active_subscription' => 'Active Subscription',
        'next_payment' => 'Next payment on :date',
    ],

    'cards' => [
        'invoices_title' => 'Invoices',
        'invoices_desc' => 'View all your invoices and download PDF versions',
        'subscription_title' => 'Subscription',
        'subscription_desc' => 'Manage your subscription plan and view feature details',
        'subscription_desc_alt' => 'Manage your subscription plan and features',
    ],

    'info' => [
        'important' => 'Important Information',
        'auto_email' => 'Invoices are automatically sent via email',
        'pdf_download' => 'PDF invoices can be downloaded anytime',
        'support_contact' => 'For questions about an invoice, contact our support',
        'auto_payment' => 'Payments are automatically charged to your stored payment method',
        'auto_charge' => 'This invoice will be automatically sent to your stored payment method. You will receive a confirmation email after successful payment.',
    ],

    'messages' => [
        'loading_error' => 'Error Loading Invoices',
        'loading_error_retry' => 'Error Loading',
        'pdf_error' => 'Error Downloading PDF',
        'payment_methods_error' => 'Error Loading Payment Methods',
        'added' => 'Payment method successfully added!',
        'updated' => 'Billing information successfully updated!',
        'default_set' => 'Default payment method successfully changed!',
        'default_error' => 'Error setting default payment method',
        'removed' => 'Payment method successfully removed!',
        'remove_error' => 'Error removing payment method',
    ],

    'breadcrumbs' => [
        'dashboard' => 'Dashboard',
        'subscription' => 'Subscription',
        'invoices' => 'Invoices',
        'payment_methods' => 'Payment Methods',
    ],

    'navigation' => [
        'view_invoices' => 'View Invoices →',
        'manage_subscription' => 'Manage Subscription →',
    ],
];
