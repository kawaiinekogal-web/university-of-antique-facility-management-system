<div class="address-fields" data-address-fields data-provinces-url="{{ route('addresses.provinces') }}" data-cities-url="{{ url('/api/addresses/cities') }}" data-barangays-url="{{ url('/api/addresses/barangays') }}">
    <div class="pk-field">
        <label class="pk-label" for="street_address">Street address <span class="pk-req">*</span></label>
        <input type="text" name="street_address" id="street_address" required maxlength="255" autocomplete="street-address" class="pk-input" value="{{ old('street_address') }}" placeholder="House/building number, street, sitio">
        @error('street_address')<p class="pr-err">{{ $message }}</p>@enderror
    </div>
    <div class="pk-grid-2 u-mt-14">
        <div class="pk-field">
            <label class="pk-label" for="province">Province <span class="pk-req">*</span></label>
            <select name="province" id="province" required class="pk-input" data-address-province>
                <option value="">Loading provinces…</option>
            </select>
            <input type="hidden" name="province_code" id="province_code" data-address-province-code value="{{ old('province_code') }}">
            @error('province')<p class="pr-err">{{ $message }}</p>@enderror
        </div>
        <div class="pk-field">
            <label class="pk-label" for="city_municipality">City/Municipality <span class="pk-req">*</span></label>
            <select name="city_municipality" id="city_municipality" required class="pk-input" data-address-city disabled>
                <option value="">Select a province first</option>
            </select>
            <input type="hidden" name="city_code" id="city_code" data-address-city-code value="{{ old('city_code') }}">
            @error('city_municipality')<p class="pr-err">{{ $message }}</p>@enderror
        </div>
    </div>
    <div class="pk-grid-2 u-mt-14">
        <div class="pk-field">
            <label class="pk-label" for="barangay">Barangay <span class="pk-req">*</span></label>
            <select name="barangay" id="barangay" required class="pk-input" data-address-barangay disabled>
                <option value="">Select a city/municipality first</option>
            </select>
            <input type="hidden" name="barangay_code" id="barangay_code" data-address-barangay-code value="{{ old('barangay_code') }}">
            @error('barangay')<p class="pr-err">{{ $message }}</p>@enderror
        </div>
        <div class="pk-field">
            <label class="pk-label" for="country">Country <span class="pk-req">*</span></label>
            <select name="country" id="country" required class="pk-input">
                <option value="Philippines" selected>Philippines</option>
            </select>
            @error('country')<p class="pr-err">{{ $message }}</p>@enderror
        </div>
    </div>
</div>
<script>
(() => {
    const root = document.querySelector('[data-address-fields]');
    if (!root) return;
    const province = root.querySelector('[data-address-province]');
    const city = root.querySelector('[data-address-city]');
    const barangay = root.querySelector('[data-address-barangay]');
    const provinceCode = root.querySelector('[data-address-province-code]');
    const cityCode = root.querySelector('[data-address-city-code]');
    const barangayCode = root.querySelector('[data-address-barangay-code]');
    const oldProvince = @json(old('province'));
    const oldCity = @json(old('city_municipality'));
    const oldBarangay = @json(old('barangay'));
    const fill = (select, items, placeholder, selected) => {
        select.innerHTML = '<option value="">' + placeholder + '</option>';
        items.forEach(item => {
            const option = new Option(item.name, item.name, false, item.name === selected);
            option.dataset.code = item.code;
            select.add(option);
        });
        select.disabled = items.length === 0;
    };
    const load = async (url, select, placeholder, selected, codeInput) => {
        select.disabled = true;
        const response = await fetch(url, { headers: { Accept: 'application/json' } });
        if (!response.ok) throw new Error('Address data could not be loaded.');
        const items = await response.json();
        fill(select, items, placeholder, selected);
        if (selected) codeInput.value = select.selectedOptions[0]?.dataset.code || '';
    };
    const loadProvinces = async () => {
        try {
            await load(root.dataset.provincesUrl, province, 'Select a province', oldProvince, provinceCode);
            if (province.value) await loadCities(province.value, oldCity);
        } catch (error) {
            province.innerHTML = '<option value="">Address data unavailable — try again</option>';
            console.error(error);
        }
    };
    const loadCities = async (name, selected = '') => {
        const code = province.selectedOptions[0]?.dataset.code;
        provinceCode.value = code || '';
        cityCode.value = ''; barangayCode.value = '';
        barangay.innerHTML = '<option value="">Select a city/municipality first</option>';
        barangay.disabled = true;
        if (!code) return;
        try {
            await load(root.dataset.citiesUrl + '/' + code, city, 'Select a city/municipality', selected, cityCode);
            if (city.value) await loadBarangays(city.value, oldBarangay);
        } catch (error) { city.innerHTML = '<option value="">Unable to load cities</option>'; console.error(error); }
    };
    const loadBarangays = async (name, selected = '') => {
        const code = city.selectedOptions[0]?.dataset.code;
        cityCode.value = code || '';
        if (!code) return;
        try { await load(root.dataset.barangaysUrl + '/' + code, barangay, 'Select a barangay', selected, barangayCode); }
        catch (error) { barangay.innerHTML = '<option value="">Unable to load barangays</option>'; console.error(error); }
    };
    province.addEventListener('change', () => loadCities(province.value));
    city.addEventListener('change', () => loadBarangays(city.value));
    loadProvinces();
})();
</script>
