<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

/**
 * Imports employees from a CSV / Excel file.
 *
 * Expected columns (order does not matter — matched by heading name):
 *
 *  first_name           required
 *  last_name            required
 *  job_title            required
 *  basic_salary         required  (₦, numeric)
 *  hire_date            required  (YYYY-MM-DD or DD/MM/YYYY)
 *  email                optional
 *  phone                optional
 *  department           optional
 *  employment_type      optional  full_time|part_time|contract  (default: full_time)
 *  state_of_residence   optional
 *  tin                  optional
 *  housing_allowance    optional  numeric (default 0)
 *  transport_allowance  optional  numeric (default 0)
 *  medical_allowance    optional  numeric (default 0)
 *  utility_allowance    optional  numeric (default 0)
 *  other_allowances     optional  numeric (default 0)
 *  nhf_enabled          optional  yes/no  (default yes)
 *  nhis_enabled         optional  yes/no  (default no)
 *  nhis_amount          optional  numeric (default 0)
 *  home_loan_interest   optional  numeric (default 0)
 *  life_insurance_premium optional numeric (default 0)
 *  annual_rent          optional  numeric (default 0)
 *  bank_name            optional
 *  account_number       optional
 *  account_name         optional
 */
class EmployeesImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    public array $errors   = [];
    public int   $imported = 0;
    public int   $skipped  = 0;
    public int   $updated  = 0;

    private int $nextSeq;

    public function __construct(
        private readonly Tenant $tenant,
        /** When true, rows whose email matches an existing employee update that record */
        private readonly bool   $updateExisting = false,
    ) {
        $this->nextSeq = Employee::where('tenant_id', $tenant->id)->count() + 1;
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $this->processRow($row->toArray(), $index + 2); // +2: 1-indexed + header
        }
    }

    private function processRow(array $raw, int $lineNum): void
    {
        // Normalise heading keys
        $row = [];
        foreach ($raw as $k => $v) {
            $row[strtolower(trim(str_replace([' ', '-'], '_', $k)))] = $v;
        }

        // ── Required fields ───────────────────────────────────────────────────
        $firstName = trim($row['first_name'] ?? '');
        $lastName  = trim($row['last_name']  ?? '');
        $jobTitle  = trim($row['job_title']  ?? '');

        if ($firstName === '') {
            $this->errors[] = "Row {$lineNum}: first_name is required.";
            $this->skipped++;
            return;
        }
        if ($lastName === '') {
            $this->errors[] = "Row {$lineNum}: last_name is required.";
            $this->skipped++;
            return;
        }
        if ($jobTitle === '') {
            $this->errors[] = "Row {$lineNum}: job_title is required.";
            $this->skipped++;
            return;
        }

        $basicSalary = (float) str_replace(',', '', $row['basic_salary'] ?? 0);
        if ($basicSalary < 30000) {
            $this->errors[] = "Row {$lineNum} ({$firstName} {$lastName}): basic_salary must be at least ₦30,000.";
            $this->skipped++;
            return;
        }

        $hireDate = $this->parseDate((string)($row['hire_date'] ?? ''));
        if (! $hireDate) {
            $this->errors[] = "Row {$lineNum} ({$firstName} {$lastName}): invalid hire_date '{$row['hire_date']}'. Use YYYY-MM-DD.";
            $this->skipped++;
            return;
        }

        // ── Optional numeric helpers ──────────────────────────────────────────
        $num = fn(string $col, float $default = 0.0) =>
            (float) str_replace(',', '', $row[$col] ?? $default);

        $data = [
            'tenant_id'              => $this->tenant->id,
            'first_name'             => $firstName,
            'last_name'              => $lastName,
            'email'                  => trim($row['email'] ?? '') ?: null,
            'phone'                  => trim($row['phone'] ?? '') ?: null,
            'job_title'              => $jobTitle,
            'department'             => trim($row['department'] ?? '') ?: null,
            'employment_type'        => $this->parseEmploymentType($row['employment_type'] ?? ''),
            'hire_date'              => $hireDate->toDateString(),
            'state_of_residence'     => trim($row['state_of_residence'] ?? '') ?: null,
            'tin'                    => trim($row['tin'] ?? '') ?: null,
            'bank_name'              => trim($row['bank_name'] ?? '') ?: null,
            'account_number'         => trim($row['account_number'] ?? '') ?: null,
            'account_name'           => trim($row['account_name'] ?? '') ?: null,
            'basic_salary'           => $basicSalary,
            'housing_allowance'      => $num('housing_allowance'),
            'transport_allowance'    => $num('transport_allowance'),
            'medical_allowance'      => $num('medical_allowance'),
            'utility_allowance'      => $num('utility_allowance'),
            'other_allowances'       => $num('other_allowances'),
            'nhf_enabled'            => $this->parseBool($row['nhf_enabled'] ?? 'yes'),
            'nhis_enabled'           => $this->parseBool($row['nhis_enabled'] ?? 'no'),
            'nhis_amount'            => $num('nhis_amount'),
            'home_loan_interest'     => $num('home_loan_interest'),
            'life_insurance_premium' => $num('life_insurance_premium'),
            'annual_rent'            => $num('annual_rent'),
            'is_active'              => true,
        ];

        // ── Check for existing employee by email (update flow) ────────────────
        if ($this->updateExisting && ! empty($data['email'])) {
            $existing = Employee::where('tenant_id', $this->tenant->id)
                ->where('email', $data['email'])
                ->first();

            if ($existing) {
                $existing->update($data);
                $existing->gross_salary = $existing->calculateGrossSalary();
                $existing->save();
                $this->updated++;
                return;
            }
        }

        // ── Duplicate email guard (new employees) ─────────────────────────────
        if (! empty($data['email'])) {
            $dup = Employee::where('tenant_id', $this->tenant->id)
                ->where('email', $data['email'])
                ->exists();

            if ($dup) {
                $this->errors[] = "Row {$lineNum} ({$firstName} {$lastName}): email '{$data['email']}' already exists — skipped. Use update mode to overwrite.";
                $this->skipped++;
                return;
            }
        }

        // ── Create ────────────────────────────────────────────────────────────
        try {
            $employeeId = 'EMP-' . str_pad($this->nextSeq++, 4, '0', STR_PAD_LEFT);
            $employee   = Employee::create(array_merge($data, ['employee_id' => $employeeId]));
            $employee->gross_salary = $employee->calculateGrossSalary();
            $employee->save();
            $this->imported++;
        } catch (\Exception $e) {
            $this->errors[] = "Row {$lineNum} ({$firstName} {$lastName}): " . $e->getMessage();
            $this->skipped++;
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function parseDate(string $value): ?Carbon
    {
        $value = trim($value);
        if ($value === '') return null;

        if (is_numeric($value)) {
            try {
                return Carbon::instance(
                    \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $value)
                );
            } catch (\Exception) {}
        }

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'm/d/Y', 'd M Y', 'd F Y'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value);
            } catch (\Exception) {}
        }

        return null;
    }

    private function parseBool(mixed $value): bool
    {
        if (is_bool($value)) return $value;
        return in_array(strtolower(trim((string) $value)), ['yes', '1', 'true', 'y'], true);
    }

    private function parseEmploymentType(mixed $value): string
    {
        $v = strtolower(trim((string) $value));
        return match (true) {
            str_contains($v, 'part')     => 'part_time',
            str_contains($v, 'contract') => 'contract',
            default                      => 'full_time',
        };
    }
}
