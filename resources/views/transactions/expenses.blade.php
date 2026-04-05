@extends('layouts.app')

@section('page-title', 'Expenses')

@section('content')
<div class="space-y-6">
    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b flex items-center justify-between">
            <h2 class="text-base font-semibold">Expenses</h2>
            <button onclick="document.getElementById('expense-form').classList.toggle('hidden')"
                    class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
                + Record Expense
            </button>
        </div>

        {{-- Quick expense form (collapsible) --}}
        <div id="expense-form" class="hidden border-b bg-gray-50 p-6" x-data="expenseForm()" @keydown.escape.window="showNewVendor = false; showNewVendorExemptReason = false">
            <form method="POST" action="{{ route('transactions.expenses.store') }}" class="space-y-4">
                @csrf
                <h3 class="text-sm font-semibold text-gray-700">Record New Expense</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Date *</label>
                        <input type="date" name="expense_date" value="{{ now()->toDateString() }}" required
                               class="mt-1 block w-full rounded border-gray-300 text-sm px-2 py-1.5 focus:ring-green-500 focus:border-green-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Category *</label>
                        <select name="category" required class="mt-1 block w-full rounded border-gray-300 text-sm px-2 py-1.5">
                            @foreach(['rent','utilities','salaries','transport','repairs','supplies','marketing','legal','insurance','other'] as $cat)
                                <option value="{{ $cat }}">{{ ucfirst($cat) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Amount (₦) *</label>
                        <input type="number" name="amount" min="0.01" step="0.01" required
                               class="mt-1 block w-full rounded border-gray-300 text-sm px-2 py-1.5 focus:ring-green-500 focus:border-green-500">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-gray-700">Description *</label>
                        <input type="text" name="description" required placeholder="What was this expense for?"
                               class="mt-1 block w-full rounded border-gray-300 text-sm px-2 py-1.5 focus:ring-green-500 focus:border-green-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Account *</label>
                        <select name="account_id" required class="mt-1 block w-full rounded border-gray-300 text-sm px-2 py-1.5">
                            <option value="">— Select Account —</option>
                            @foreach(\App\Models\Account::where('tenant_id', auth()->user()->tenant_id)->where('type','expense')->where('is_active',true)->orderBy('code')->get() as $acct)
                                <option value="{{ $acct->id }}">{{ $acct->code }} – {{ $acct->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-center gap-4">
                        <label class="flex items-center gap-2 text-xs">
                            <input type="checkbox" name="vat_applicable" value="1" class="rounded border-gray-300 text-green-600">
                            Input VAT (7.5%)
                        </label>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Vendor (optional)</label>
                        <div class="flex gap-2 mt-1">
                            <select name="vendor_id" x-ref="vendorSelect"
                                    class="block w-full rounded border-gray-300 text-sm px-2 py-1.5">
                                <option value="">— No Vendor —</option>
                                @foreach(\App\Models\Vendor::where('is_active',true)->orderBy('name')->get() as $v)
                                    <option value="{{ $v->id }}">
                                        {{ $v->name }} — {{ $v->wht_exempt ? 'WHT Exempt' : 'WHT: '.$v->wht_rate.'%' }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="button" @click="showNewVendor = true"
                                    class="shrink-0 px-2 py-1 text-xs bg-green-50 border border-green-300 text-green-700 rounded hover:bg-green-100 whitespace-nowrap">
                                + New
                            </button>
                        </div>
                        <p class="text-xs text-gray-400 mt-0.5">WHT auto-deducted based on vendor type</p>

                        {{-- New Vendor Modal --}}
                        <template x-teleport="body">
                            <div x-show="showNewVendor" x-cloak
                                 class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
                                 @click.self="showNewVendor = false">
                                <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6 space-y-4">
                                    <div class="flex justify-between items-center">
                                        <h3 class="text-base font-semibold">Add New Vendor</h3>
                                        <button type="button" @click="showNewVendor = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">×</button>
                                    </div>
                                    <div class="grid grid-cols-2 gap-3 text-sm">
                                        <div class="col-span-2">
                                            <label class="block text-xs font-medium text-gray-700">Vendor Name *</label>
                                            <input type="text" x-model="newVendor.name" placeholder="e.g. Zenith Supplies Ltd"
                                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700">Email</label>
                                            <input type="email" x-model="newVendor.email"
                                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700">Phone</label>
                                            <input type="text" x-model="newVendor.phone" placeholder="+234..."
                                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700">TIN</label>
                                            <input type="text" x-model="newVendor.tin" placeholder="1234567-0001"
                                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                                        </div>
                                        <div x-show="!newVendor.wht_exempt">
                                            <label class="block text-xs font-medium text-gray-700">Vendor Type</label>
                                            <select x-model="newVendor.vendor_type"
                                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                                                <option value="services">Services (WHT 5%)</option>
                                                <option value="goods">Goods (WHT 5%)</option>
                                                <option value="rent">Rent (WHT 10%)</option>
                                                <option value="mixed">Mixed (WHT 5%)</option>
                                            </select>
                                        </div>
                                        {{-- WHT Exempt toggle --}}
                                        <div class="col-span-2">
                                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                                <input type="checkbox" x-model="newVendor.wht_exempt"
                                                       class="rounded border-gray-300 text-amber-500 focus:ring-amber-400">
                                                <span class="text-xs font-medium text-gray-700">WHT Not Applicable</span>
                                            </label>
                                            <p class="text-xs text-gray-400 mt-0.5 ml-5">
                                                e.g. foreign vendor, income not earned in Nigeria, diplomatic, govt entity
                                            </p>
                                        </div>
                                        {{-- Exempt reason (shown when exempt is checked) --}}
                                        <div class="col-span-2" x-show="newVendor.wht_exempt" x-cloak>
                                            <label class="block text-xs font-medium text-gray-700">Reason *</label>
                                            <select x-model="newVendor.wht_exempt_reason"
                                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                                                <option value="">— Select reason —</option>
                                                <option value="foreign_income">Foreign vendor / income not earned in Nigeria</option>
                                                <option value="diplomatic">Diplomatic mission / international organisation</option>
                                                <option value="govt_entity">Government or public sector entity</option>
                                                <option value="firs_exemption">FIRS-issued WHT exemption certificate</option>
                                                <option value="treaty_relief">Double-taxation treaty relief</option>
                                                <option value="other">Other</option>
                                            </select>
                                        </div>
                                    </div>
                                    <p x-show="newVendorError" x-text="newVendorError" class="text-xs text-red-600"></p>
                                    <div class="flex justify-end gap-3 pt-2">
                                        <button type="button" @click="showNewVendor = false"
                                                class="px-4 py-2 text-sm border border-gray-300 rounded-md hover:bg-gray-50">Cancel</button>
                                        <button type="button" @click="saveNewVendor()"
                                                :disabled="savingVendor"
                                                class="px-4 py-2 text-sm bg-green-600 text-white rounded-md hover:bg-green-700 disabled:opacity-50">
                                            <span x-text="savingVendor ? 'Saving…' : 'Save Vendor'"></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Notes</label>
                        <input type="text" name="notes" placeholder="Additional notes"
                               class="mt-1 block w-full rounded border-gray-300 text-sm px-2 py-1.5">
                    </div>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit"
                            class="px-4 py-2 bg-green-600 text-white text-sm rounded-md hover:bg-green-700">
                        Save Expense
                    </button>
                    <button type="button" onclick="document.getElementById('expense-form').classList.add('hidden')"
                            class="px-4 py-2 border border-gray-300 text-sm rounded-md hover:bg-gray-50">
                        Cancel
                    </button>
                </div>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ref</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vendor</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">WHT</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Net Payable</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse($expenses as $expense)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-xs font-mono text-gray-500">{{ $expense->reference }}</td>
                        <td class="px-4 py-3 text-sm">{{ $expense->expense_date->format('d M Y') }}</td>
                        <td class="px-4 py-3 text-sm capitalize">{{ $expense->category }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700 max-w-xs truncate">{{ $expense->description }}</td>
                        <td class="px-4 py-3 text-sm">{{ $expense->vendor->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-right">₦{{ number_format($expense->amount, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-right text-orange-600">
                            @if($expense->wht_amount > 0)
                                ₦{{ number_format($expense->wht_amount, 2) }}
                                <span class="text-xs text-gray-400">({{ $expense->wht_rate }}%)</span>
                            @elseif($expense->vendor && $expense->vendor->wht_exempt)
                                <span class="text-xs text-gray-400">Exempt</span>
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-right font-medium">
                            ₦{{ number_format($expense->net_payable, 2) }}
                        </td>
                        <td class="px-4 py-3">
                            @php $colors = ['pending'=>'yellow','approved'=>'blue','paid'=>'green','rejected'=>'red'] @endphp
                            <span class="inline-flex rounded-full px-2 text-xs font-semibold
                                bg-{{ $colors[$expense->status] ?? 'gray' }}-100
                                text-{{ $colors[$expense->status] ?? 'gray' }}-800">
                                {{ ucfirst($expense->status) }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-4 py-8 text-center text-gray-500">
                            No expenses recorded yet.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if($expenses->isNotEmpty())
                <tfoot class="bg-gray-50 font-medium text-sm">
                    <tr>
                        <td colspan="5" class="px-4 py-3">Page Total</td>
                        <td class="px-4 py-3 text-right">₦{{ number_format($expenses->sum('amount'), 2) }}</td>
                        <td class="px-4 py-3 text-right text-orange-600">₦{{ number_format($expenses->sum('wht_amount'), 2) }}</td>
                        <td class="px-4 py-3 text-right">₦{{ number_format($expenses->sum('net_payable'), 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>

        <div class="px-6 py-4 border-t">
            {{ $expenses->links() }}
        </div>
    </div>
</div>
@push('scripts')
<script>
function expenseForm() {
    return {
        showNewVendor: false,
        savingVendor: false,
        newVendorError: '',
        newVendor: { name: '', email: '', phone: '', tin: '', vendor_type: 'services', wht_exempt: false, wht_exempt_reason: '' },

        async saveNewVendor() {
            if (!this.newVendor.name.trim()) {
                this.newVendorError = 'Vendor name is required.';
                return;
            }
            if (this.newVendor.wht_exempt && !this.newVendor.wht_exempt_reason) {
                this.newVendorError = 'Please select a reason for the WHT exemption.';
                return;
            }
            this.savingVendor = true;
            this.newVendorError = '';
            try {
                const res = await fetch('{{ route('vendors.quick-store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(this.newVendor),
                });
                const data = await res.json();
                if (!res.ok) {
                    this.newVendorError = data.message || 'Failed to save vendor.';
                    return;
                }
                const select = this.$refs.vendorSelect;
                if (!select.querySelector(`option[value="${data.id}"]`)) {
                    const label = data.wht_exempt
                        ? `${data.name} — WHT Exempt`
                        : `${data.name} — WHT: ${data.wht_rate}%`;
                    const opt = new Option(label, data.id, true, true);
                    select.add(opt);
                }
                select.value = data.id;
                this.showNewVendor = false;
                this.newVendor = { name: '', email: '', phone: '', tin: '', vendor_type: 'services', wht_exempt: false, wht_exempt_reason: '' };
            } catch (e) {
                this.newVendorError = 'Network error. Please try again.';
            } finally {
                this.savingVendor = false;
            }
        },
    };
}
</script>
@endpush

@endsection
