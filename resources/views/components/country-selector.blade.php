@props(['name' => 'country_code', 'value' => old('country_code', 'AE'), 'required' => true, 'phoneInputId' => 'whatsapp_number'])

@php
    $countries = [
        ['code' => 'AE', 'name' => 'United Arab Emirates', 'dialCode' => '+971'],
        ['code' => 'SA', 'name' => 'Saudi Arabia', 'dialCode' => '+966'],
        ['code' => 'EG', 'name' => 'Egypt', 'dialCode' => '+20'],
        ['code' => 'US', 'name' => 'United States', 'dialCode' => '+1'],
        ['code' => 'GB', 'name' => 'United Kingdom', 'dialCode' => '+44'],
        ['code' => 'FR', 'name' => 'France', 'dialCode' => '+33'],
        ['code' => 'DE', 'name' => 'Germany', 'dialCode' => '+49'],
        ['code' => 'IT', 'name' => 'Italy', 'dialCode' => '+39'],
        ['code' => 'ES', 'name' => 'Spain', 'dialCode' => '+34'],
        ['code' => 'IN', 'name' => 'India', 'dialCode' => '+91'],
        ['code' => 'PK', 'name' => 'Pakistan', 'dialCode' => '+92'],
        ['code' => 'JO', 'name' => 'Jordan', 'dialCode' => '+962'],
        ['code' => 'LB', 'name' => 'Lebanon', 'dialCode' => '+961'],
        ['code' => 'KW', 'name' => 'Kuwait', 'dialCode' => '+965'],
        ['code' => 'QA', 'name' => 'Qatar', 'dialCode' => '+974'],
        ['code' => 'BH', 'name' => 'Bahrain', 'dialCode' => '+973'],
        ['code' => 'OM', 'name' => 'Oman', 'dialCode' => '+968'],
        ['code' => 'YE', 'name' => 'Yemen', 'dialCode' => '+967'],
        ['code' => 'IQ', 'name' => 'Iraq', 'dialCode' => '+964'],
        ['code' => 'SY', 'name' => 'Syria', 'dialCode' => '+963'],
        ['code' => 'CN', 'name' => 'China', 'dialCode' => '+86'],
        ['code' => 'JP', 'name' => 'Japan', 'dialCode' => '+81'],
        ['code' => 'KR', 'name' => 'South Korea', 'dialCode' => '+82'],
        ['code' => 'AU', 'name' => 'Australia', 'dialCode' => '+61'],
        ['code' => 'CA', 'name' => 'Canada', 'dialCode' => '+1'],
        ['code' => 'BR', 'name' => 'Brazil', 'dialCode' => '+55'],
        ['code' => 'MX', 'name' => 'Mexico', 'dialCode' => '+52'],
        ['code' => 'AR', 'name' => 'Argentina', 'dialCode' => '+54'],
        ['code' => 'ZA', 'name' => 'South Africa', 'dialCode' => '+27'],
        ['code' => 'NG', 'name' => 'Nigeria', 'dialCode' => '+234'],
        ['code' => 'TR', 'name' => 'Turkey', 'dialCode' => '+90'],
        ['code' => 'RU', 'name' => 'Russia', 'dialCode' => '+7'],
    ];
    
    $selectedCountry = collect($countries)->firstWhere('code', $value) ?? $countries[0];
    $phoneFormats = [
        'AE' => ['format' => '/^\\+971[0-9]{9}$/', 'placeholder' => '+9715XXXXXXXX'],
        'SA' => ['format' => '/^\\+966[0-9]{9}$/', 'placeholder' => '+9665XXXXXXXX'],
        'EG' => ['format' => '/^\\+20[0-9]{10}$/', 'placeholder' => '+201234567890'],
        'US' => ['format' => '/^\\+1[0-9]{10}$/', 'placeholder' => '+11234567890'],
        'GB' => ['format' => '/^\\+44[0-9]{10}$/', 'placeholder' => '+447123456789'],
        'FR' => ['format' => '/^\\+33[0-9]{9}$/', 'placeholder' => '+33123456789'],
        'DE' => ['format' => '/^\\+49[0-9]{10,11}$/', 'placeholder' => '+491234567890'],
        'IT' => ['format' => '/^\\+39[0-9]{9,10}$/', 'placeholder' => '+39123456789'],
        'ES' => ['format' => '/^\\+34[0-9]{9}$/', 'placeholder' => '+34123456789'],
        'IN' => ['format' => '/^\\+91[0-9]{10}$/', 'placeholder' => '+911234567890'],
        'PK' => ['format' => '/^\\+92[0-9]{10}$/', 'placeholder' => '+923001234567'],
        'JO' => ['format' => '/^\\+962[0-9]{9}$/', 'placeholder' => '+962791234567'],
        'LB' => ['format' => '/^\\+961[0-9]{8}$/', 'placeholder' => '+9613123456'],
        'KW' => ['format' => '/^\\+965[0-9]{8}$/', 'placeholder' => '+96512345678'],
        'QA' => ['format' => '/^\\+974[0-9]{8}$/', 'placeholder' => '+97433123456'],
        'BH' => ['format' => '/^\\+973[0-9]{8}$/', 'placeholder' => '+97336123456'],
        'OM' => ['format' => '/^\\+968[0-9]{8}$/', 'placeholder' => '+96892123456'],
        'YE' => ['format' => '/^\\+967[0-9]{9}$/', 'placeholder' => '+967712345678'],
        'IQ' => ['format' => '/^\\+964[0-9]{10}$/', 'placeholder' => '+9647901234567'],
        'SY' => ['format' => '/^\\+963[0-9]{9}$/', 'placeholder' => '+963912345678'],
        'CN' => ['format' => '/^\\+86[0-9]{11}$/', 'placeholder' => '+8613123456789'],
        'JP' => ['format' => '/^\\+81[0-9]{10}$/', 'placeholder' => '+9012345678'],
        'KR' => ['format' => '/^\\+82[0-9]{9,10}$/', 'placeholder' => '+1023456789'],
        'AU' => ['format' => '/^\\+61[0-9]{9}$/', 'placeholder' => '+6123456789'],
        'CA' => ['format' => '/^\\+1[0-9]{10}$/', 'placeholder' => '+11234567890'],
        'BR' => ['format' => '/^\\+55[0-9]{10,11}$/', 'placeholder' => '+5511987654321'],
        'MX' => ['format' => '/^\\+52[0-9]{10}$/', 'placeholder' => '+521234567890'],
        'AR' => ['format' => '/^\\+54[0-9]{10}$/', 'placeholder' => '+541123456789'],
        'ZA' => ['format' => '/^\\+27[0-9]{9}$/', 'placeholder' => '+27123456789'],
        'NG' => ['format' => '/^\\+234[0-9]{10}$/', 'placeholder' => '+2348123456789'],
        'TR' => ['format' => '/^\\+90[0-9]{10}$/', 'placeholder' => '+905321234567'],
        'RU' => ['format' => '/^\\+7[0-9]{10}$/', 'placeholder' => '+79123456789'],
    ];
    
    $phoneFormat = $phoneFormats[$value] ?? $phoneFormats['AE'];
@endphp

<div 
    x-data="{
        selectedCountry: '{{ $value }}',
        phoneFormats: @js($phoneFormats),
        phoneHint: @js($phoneFormats[$value]['placeholder'] ?? 'Enter phone number with country code'),
        phoneInputId: '{{ $phoneInputId }}',
        
        init() {
            this.updatePhoneValidation();
            // Listen for phone input changes
            const phoneInput = document.getElementById(this.phoneInputId);
            if (phoneInput) {
                phoneInput.addEventListener('input', () => this.validatePhone());
            }
        },
        
        updatePhoneValidation() {
            const format = this.phoneFormats[this.selectedCountry];
            if (!format) return;
            
            this.phoneHint = format.placeholder || 'Enter phone number with country code';
            
            const phoneInput = document.getElementById(this.phoneInputId);
            if (phoneInput) {
                phoneInput.placeholder = format.placeholder || '';
                phoneInput.pattern = format.format.replace(/\//g, '');
                this.validatePhone();
            }
        },
        
        validatePhone() {
            const phoneInput = document.getElementById(this.phoneInputId);
            if (!phoneInput) return;
            
            const phone = phoneInput.value.trim();
            const format = this.phoneFormats[this.selectedCountry];
            
            if (!format || !phone) {
                phoneInput.setCustomValidity('');
                phoneInput.classList.remove('border-red-500', 'border-green-500');
                return;
            }
            
            try {
                const regex = new RegExp(format.format.replace(/\//g, ''));
                const isValid = regex.test(phone);
                
                if (!isValid && phone.length > 0) {
                    phoneInput.setCustomValidity(`Phone number must match format: ${format.placeholder}`);
                    phoneInput.classList.add('border-red-500');
                    phoneInput.classList.remove('border-green-500');
                } else if (isValid) {
                    phoneInput.setCustomValidity('');
                    phoneInput.classList.remove('border-red-500');
                    phoneInput.classList.add('border-green-500');
                } else {
                    phoneInput.setCustomValidity('');
                    phoneInput.classList.remove('border-red-500', 'border-green-500');
                }
            } catch (e) {
                console.error('Invalid regex pattern:', format.format);
            }
        }
    }"
    x-init="updatePhoneValidation()"
>
    <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
        Country <span class="text-red-500">*</span>
    </label>
    <div class="relative">
        <select 
            name="{{ $name }}" 
            id="{{ $name }}"
            x-model="selectedCountry"
            @change="updatePhoneValidation()"
            {{ $required ? 'required' : '' }}
            class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-shadow appearance-none pr-10"
        >
            @foreach($countries as $country)
                <option value="{{ $country['code'] }}" {{ $value === $country['code'] ? 'selected' : '' }}>
                    {{ $country['name'] }} ({{ $country['dialCode'] }})
                </option>
            @endforeach
        </select>
        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </div>
    </div>
    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-text="phoneHint"></p>
</div>

