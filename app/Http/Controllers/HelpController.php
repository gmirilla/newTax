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
        'accounting-firms' => [
            'title'       => 'Accounting Firms & Clients',
            'description' => 'For accountants managing other companies, or business owners giving their accountant access.',
            'icon'        => 'team',
            'category'    => 'firm',
            'roles'       => ['admin', 'accountant'],
        ],
        'expenses' => [
            'title'       => 'Expenses & Vendors',
            'description' => 'Record what you spend, claim input VAT and track WHT you deduct.',
            'icon'        => 'cash',
            'category'    => 'finance',
            'roles'       => ['admin', 'accountant'],
        ],
        'tax-compliance' => [
            'title'       => 'Tax & Compliance',
            'description' => 'Compute VAT returns, track WHT and understand your CIT position.',
            'icon'        => 'chart',
            'category'    => 'finance',
            'roles'       => ['admin', 'accountant'],
        ],
        'maintenance' => [
            'title'       => 'Maintenance',
            'description' => 'Track equipment, schedule servicing, report breakdowns and manage work orders.',
            'icon'        => 'clipboard',
            'category'    => 'operations',
            'plan'        => 'maintenance',
        ],
        'manufacturing' => [
            'title'       => 'Manufacturing',
            'description' => 'Define recipes (BOMs) and run production orders that turn raw materials into finished goods.',
            'icon'        => 'box',
            'category'    => 'operations',
            'plan'        => 'manufacturing',
        ],
        'storefront' => [
            'title'       => 'Online Storefront',
            'description' => 'Run a public shop page, list products and services, and handle customer orders.',
            'icon'        => 'document',
            'category'    => 'operations',
            'plan'        => 'storefront',
        ],
        'chart-of-accounts' => [
            'title'       => 'Chart of Accounts',
            'description' => 'Add, rename and deactivate the accounts your reports are built from.',
            'icon'        => 'book',
            'category'    => 'settings',
            'roles'       => ['admin'],
        ],
        'e-invoicing' => [
            'title'       => 'NRS e-Invoicing',
            'description' => 'Connect your NRS credentials and submit invoices from within AccountTaxNG.',
            'icon'        => 'document',
            'category'    => 'settings',
            'roles'       => ['admin'],
            'plan'        => 'firs',
        ],
        'referrals' => [
            'title'       => 'Referrals',
            'description' => 'Share your link and earn account credit when businesses you refer subscribe.',
            'icon'        => 'users',
            'category'    => 'settings',
            'roles'       => ['admin'],
        ],
        'activity-log' => [
            'title'       => 'Activity Log',
            'description' => 'See who did what and when in your account.',
            'icon'        => 'clipboard',
            'category'    => 'settings',
            'roles'       => ['admin', 'accountant'],
        ],
    ];

    /** Topics the signed-in user's role may see (topics without a 'roles' key are for everyone). */
    private function visibleTopics(): array
    {
        $role = auth()->user()?->role;

        return array_filter(
            self::TOPICS,
            fn (array $t) => !isset($t['roles']) || in_array($role, $t['roles'], true)
        );
    }

    public function index(): View
    {
        $tenant = auth()->user()?->tenant;

        $topics = array_map(function (array $t) use ($tenant) {
            // 'locked' = the topic's feature isn't on the tenant's plan; the page is still readable.
            $t['locked'] = isset($t['plan']) && $tenant && !$tenant->planAllows($t['plan']);
            return $t;
        }, $this->visibleTopics());

        return view('help.index', ['topics' => $topics]);
    }

    public function show(string $topic): View
    {
        abort_unless(array_key_exists($topic, $this->visibleTopics()), 404);
        $meta = self::TOPICS[$topic];

        return view('help.topics.' . $topic, compact('meta'));
    }
}
