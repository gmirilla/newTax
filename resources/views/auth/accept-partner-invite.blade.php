<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Partner Request — AccountTaxNG</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center px-4 py-12">

<div class="max-w-md w-full space-y-6">

    <div class="text-center">
        <div class="inline-flex items-center gap-2">
            <div class="w-8 h-8 bg-green-600 rounded-lg flex items-center justify-center">
                <span class="text-white font-bold text-sm">N</span>
            </div>
            <span class="text-xl font-bold text-gray-900">AccountTaxNG</span>
        </div>
    </div>

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-lg px-5 py-4">
        <ul class="text-sm text-red-700 list-disc pl-4">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="bg-white rounded-lg shadow px-8 py-8 space-y-5">
        <div>
            <h1 class="text-xl font-bold text-gray-900">Bookkeeping access request</h1>
            <p class="text-sm text-gray-500 mt-1">
                <strong>{{ $link->firmTenant->name }}</strong> is asking for access to manage your
                company's books on AccountTaxNG. They will be able to work on invoices, expenses,
                and financial reports — they will <strong>not</strong> have access to billing, your
                team, or company settings.
            </p>
        </div>

        <form method="POST" action="{{ route('partner-invite.accept', $link->token) }}">
            @csrf
            <button type="submit"
                    class="w-full py-2.5 bg-green-600 text-white font-semibold text-sm rounded-md hover:bg-green-700 transition-colors">
                Approve Access
            </button>
        </form>

        <p class="text-center text-xs text-gray-400">
            Not expecting this? You can safely ignore this page — nothing changes unless you approve.
            You can also manage this later from <a href="{{ route('partners.manage.index') }}" class="text-green-600 underline">Manage Accounting Partners</a>.
        </p>
    </div>

</div>
</body>
</html>
