// Country data with phone number formats
export const countries = [
    { code: 'AE', name: 'United Arab Emirates', dialCode: '+971', format: /^\+971[0-9]{9}$/, placeholder: '+9715XXXXXXXX' },
    { code: 'SA', name: 'Saudi Arabia', dialCode: '+966', format: /^\+966[0-9]{9}$/, placeholder: '+9665XXXXXXXX' },
    { code: 'EG', name: 'Egypt', dialCode: '+20', format: /^\+20[0-9]{10}$/, placeholder: '+201234567890' },
    { code: 'US', name: 'United States', dialCode: '+1', format: /^\+1[0-9]{10}$/, placeholder: '+11234567890' },
    { code: 'GB', name: 'United Kingdom', dialCode: '+44', format: /^\+44[0-9]{10}$/, placeholder: '+447123456789' },
    { code: 'FR', name: 'France', dialCode: '+33', format: /^\+33[0-9]{9}$/, placeholder: '+33123456789' },
    { code: 'DE', name: 'Germany', dialCode: '+49', format: /^\+49[0-9]{10,11}$/, placeholder: '+491234567890' },
    { code: 'IT', name: 'Italy', dialCode: '+39', format: /^\+39[0-9]{9,10}$/, placeholder: '+39123456789' },
    { code: 'ES', name: 'Spain', dialCode: '+34', format: /^\+34[0-9]{9}$/, placeholder: '+34123456789' },
    { code: 'IN', name: 'India', dialCode: '+91', format: /^\+91[0-9]{10}$/, placeholder: '+911234567890' },
    { code: 'PK', name: 'Pakistan', dialCode: '+92', format: /^\+92[0-9]{10}$/, placeholder: '+923001234567' },
    { code: 'JO', name: 'Jordan', dialCode: '+962', format: /^\+962[0-9]{9}$/, placeholder: '+962791234567' },
    { code: 'LB', name: 'Lebanon', dialCode: '+961', format: /^\+961[0-9]{8}$/, placeholder: '+9613123456' },
    { code: 'KW', name: 'Kuwait', dialCode: '+965', format: /^\+965[0-9]{8}$/, placeholder: '+96512345678' },
    { code: 'QA', name: 'Qatar', dialCode: '+974', format: /^\+974[0-9]{8}$/, placeholder: '+97433123456' },
    { code: 'BH', name: 'Bahrain', dialCode: '+973', format: /^\+973[0-9]{8}$/, placeholder: '+97336123456' },
    { code: 'OM', name: 'Oman', dialCode: '+968', format: /^\+968[0-9]{8}$/, placeholder: '+96892123456' },
    { code: 'YE', name: 'Yemen', dialCode: '+967', format: /^\+967[0-9]{9}$/, placeholder: '+967712345678' },
    { code: 'IQ', name: 'Iraq', dialCode: '+964', format: /^\+964[0-9]{10}$/, placeholder: '+9647901234567' },
    { code: 'SY', name: 'Syria', dialCode: '+963', format: /^\+963[0-9]{9}$/, placeholder: '+963912345678' },
    { code: 'CN', name: 'China', dialCode: '+86', format: /^\+86[0-9]{11}$/, placeholder: '+8613123456789' },
    { code: 'JP', name: 'Japan', dialCode: '+81', format: /^\+81[0-9]{10}$/, placeholder: '+9012345678' },
    { code: 'KR', name: 'South Korea', dialCode: '+82', format: /^\+82[0-9]{9,10}$/, placeholder: '+1023456789' },
    { code: 'AU', name: 'Australia', dialCode: '+61', format: /^\+61[0-9]{9}$/, placeholder: '+6123456789' },
    { code: 'CA', name: 'Canada', dialCode: '+1', format: /^\+1[0-9]{10}$/, placeholder: '+11234567890' },
    { code: 'BR', name: 'Brazil', dialCode: '+55', format: /^\+55[0-9]{10,11}$/, placeholder: '+5511987654321' },
    { code: 'MX', name: 'Mexico', dialCode: '+52', format: /^\+52[0-9]{10}$/, placeholder: '+521234567890' },
    { code: 'AR', name: 'Argentina', dialCode: '+54', format: /^\+54[0-9]{10}$/, placeholder: '+541123456789' },
    { code: 'ZA', name: 'South Africa', dialCode: '+27', format: /^\+27[0-9]{9}$/, placeholder: '+27123456789' },
    { code: 'NG', name: 'Nigeria', dialCode: '+234', format: /^\+234[0-9]{10}$/, placeholder: '+2348123456789' },
    { code: 'TR', name: 'Turkey', dialCode: '+90', format: /^\+90[0-9]{10}$/, placeholder: '+905321234567' },
    { code: 'RU', name: 'Russia', dialCode: '+7', format: /^\+7[0-9]{10}$/, placeholder: '+79123456789' },
];

// Get country by code
export function getCountryByCode(code) {
    return countries.find(c => c.code === code) || countries[0];
}

// Validate phone number format for a country
export function validatePhoneFormat(phone, countryCode) {
    const country = getCountryByCode(countryCode);
    if (!country) return false;
    return country.format.test(phone);
}

// Format phone number with country dial code
export function formatPhoneNumber(phone, countryCode) {
    const country = getCountryByCode(countryCode);
    if (!country) return phone;
    
    // Remove any existing country code
    phone = phone.replace(/^\++/, '').replace(/^00/, '');
    
    // Remove the country dial code if present
    const dialCodeWithoutPlus = country.dialCode.replace('+', '');
    if (phone.startsWith(dialCodeWithoutPlus)) {
        phone = phone.substring(dialCodeWithoutPlus.length);
    }
    
    // Add country dial code
    return country.dialCode + phone;
}

