<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class HelpController extends Controller
{
    private const TOPICS = [
        'getting-started' => [
            'title'       => 'Getting Started',
            'description' => 'Set up your account, add your company details, and send your first invoice.',
            'icon'        => 'rocket',
            'category'    => 'basics',
        ],
        'invoicing' => [
            'title'       => 'Invoices & Receipts',
            'description' => 'Create invoices, record payments, apply VAT/WHT, and download PDFs.',
            'icon'        => 'document',
            'category'    => 'sales',
        ],
        'quotations' => [
            'title'       => 'Quotations',
            'description' => 'Send proforma invoices to customers and convert accepted quotes to invoices.',
            'icon'        => 'clipboard',
            'category'    => 'sales',
        ],
        'customers' => [
            'title'       => 'Customers',
            'description' => 'Add and manage customer records, contact details, and payment history.',
            'icon'        => 'users',
            'category'    => 'sales',
        ],
        'bookkeeping' => [
            'title'       => 'Bookkeeping',
            'description' => 'Understand your chart of accounts, journal entries, and how transactions are recorded.',
            'icon'        => 'book',
            'category'    => 'finance',
        ],
        'bank-accounts' => [
            'title'       => 'Bank Accounts',
            'description' => 'Link your business bank accounts and set opening balances for accurate reporting.',
            'icon'        => 'bank',
            'category'    => 'finance',
        ],
        'reports' => [
            'title'       => 'Financial Reports',
            'description' => 'Read your Profit & Loss, Balance Sheet, Trial Balance, and Tax Summary.',
            'icon'        => 'chart',
            'category'    => 'finance',
        ],
        'inventory' => [
            'title'       => 'Inventory',
            'description' => 'Manage stock items, track movements, create sales orders, and run inventory reports.',
            'icon'        => 'box',
            'category'    => 'operations',
            'plan'        => 'inventory',
        ],
        'payroll' => [
            'title'       => 'Payroll',
            'description' => 'Add employees, run payroll, generate payslips, and manage PAYE deductions.',
            'icon'        => 'cash',
            'category'    => 'operations',
            'plan'        => 'payroll',
        ],
        'team' => [
            'title'       => 'Team & Users',
            'description' => 'Invite team members, assign roles (Admin / Accountant / Staff), and manage access.',
            'icon'        => 'team',
            'category'    => 'settings',
        ],
        'billing' => [
            'title'       => 'Billing & Subscription',
            'description' => 'Manage your plan, subscription, referral credits, and upgrade or downgrade.',
            'icon'        => 'credit-card',
            'category'    => 'settings',
        ],
    ];

    public function index(): View
    {
        return view('help.index', ['topics' => self::TOPICS]);
    }

    public function show(string $topic): View
    {
        abort_unless(array_key_exists($topic, self::TOPICS), 404);
        $meta = self::TOPICS[$topic];
        // blade view name: help.topics.getting-started → blade handles hyphens fine
        return view('help.topics.' . $topic, compact('meta'));
    }
}
