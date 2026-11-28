{{--
    Discount input shared by the invoice and quote forms.
    Requires the parent Alpine component to define: discountType, discountValue and a recalculation method.
    @param string $recalc  name of the Alpine method that recalculates totals (no parentheses)
--}}
<div>
    <label class="block text-xs font-medium text-gray-700">Discount <span class="font-normal text-gray-400">(optional)</span></label>
    <div class="mt-1 flex gap-2">
        <select name="discount_type" x-model="discountType" @change="{{ $recalc }}()" aria-label="Discount type"
                class="w-20 rounded border-gray-300 text-sm focus:ring-green-500 focus:border-green-500">
            <option value="fixed">₦</option>
            <option value="percent">%</option>
        </select>
        <input type="number" name="discount_value" x-model.number="discountValue" @input="{{ $recalc }}()"
               min="0" :max="discountType === 'percent' ? 100 : null" step="0.01" placeholder="0.00" aria-label="Discount value"
               class="block w-full rounded border-gray-300 text-sm focus:ring-green-500 focus:border-green-500">
    </div>
    <p class="mt-1 text-xs text-gray-500">Applied to the subtotal. VAT and WHT are worked out on the amount after discount.</p>
</div>
