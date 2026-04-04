<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register – NaijaBooks</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="h-full">
<div class="min-h-full flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-lg">
        <h1 class="text-center text-4xl font-bold text-green-700">🇳🇬 NaijaBooks</h1>
        <h2 class="mt-4 text-center text-2xl font-bold text-gray-900">Register your company</h2>
        <p class="mt-2 text-center text-sm text-gray-500">
            VAT, WHT, CIT & PAYE compliance made easy for Nigerian SMEs
        </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-lg">
        <div class="bg-white py-8 px-4 shadow rounded-lg sm:px-10">

            @if($errors->any())
            <div class="mb-4 rounded-md bg-red-50 p-4">
                @foreach($errors->all() as $error)
                    <p class="text-sm text-red-700">{{ $error }}</p>
                @endforeach
            </div>
            @endif

            <form method="POST" action="{{ route('register') }}" class="space-y-5">
                @csrf

                <h3 class="text-sm font-semibold text-gray-700 border-b pb-2">Company Information</h3>

                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Company Name *</label>
                        <input type="text" name="company_name" value="{{ old('company_name') }}" required
                               class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:ring-green-500 focus:border-green-500">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Company Email *</label>
                        <input type="email" name="company_email" value="{{ old('company_email') }}" required
                               class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:ring-green-500 focus:border-green-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">TIN (FIRS)</label>
                        <input type="text" name="tin" value="{{ old('tin') }}" placeholder="1234567-0001"
                               class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">State</label>
                        <select name="state" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                            <option value="">— Select State —</option>
                            @foreach(['Abia','Adamawa','Akwa Ibom','Anambra','Bauchi','Bayelsa','Benue','Borno','Cross River','Delta','Ebonyi','Edo','Ekiti','Enugu','FCT','Gombe','Imo','Jigawa','Kaduna','Kano','Katsina','Kebbi','Kogi','Kwara','Lagos','Nasarawa','Niger','Ogun','Ondo','Osun','Oyo','Plateau','Rivers','Sokoto','Taraba','Yobe','Zamfara'] as $state)
                                <option value="{{ $state }}" {{ old('state') === $state ? 'selected' : '' }}>{{ $state }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Annual Turnover (₦)</label>
                        <input type="number" name="annual_turnover" value="{{ old('annual_turnover') }}"
                               placeholder="e.g. 25000000" min="0" step="1"
                               class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                        <p class="mt-1 text-xs text-gray-400">Used to determine your tax category (small/medium/large)</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Business Type</label>
                        <select name="business_type" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                            <option value="limited_liability">Limited Liability Company</option>
                            <option value="sole_proprietorship">Sole Proprietorship</option>
                            <option value="partnership">Partnership</option>
                        </select>
                    </div>
                </div>

                <h3 class="text-sm font-semibold text-gray-700 border-b pb-2 pt-2">Your Account</h3>

                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Your Full Name *</label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                               class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:ring-green-500 focus:border-green-500">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Your Email *</label>
                        <input type="email" name="email" value="{{ old('email') }}" required
                               class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:ring-green-500 focus:border-green-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Password *</label>
                        <input type="password" name="password" required minlength="8"
                               class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Confirm Password *</label>
                        <input type="password" name="password_confirmation" required
                               class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    </div>
                </div>

                <button type="submit"
                        class="w-full py-2.5 px-4 bg-green-700 text-white text-sm font-medium rounded-md hover:bg-green-800">
                    Create Company Account
                </button>
            </form>

            <p class="mt-4 text-center text-sm text-gray-500">
                Already have an account?
                <a href="{{ route('login') }}" class="text-green-600 font-medium hover:text-green-500">Sign in</a>
            </p>
        </div>
    </div>
</div>
</body>
</html>
