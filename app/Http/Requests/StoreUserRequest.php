<?php

namespace App\Http\Requests;

use App\Rules\PersonName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'              => ['required', 'string', 'max:255', new PersonName],
            'last_name'         => ['required', 'string', 'max:120', new PersonName],
            'contact_number'    => ['nullable', 'string', 'regex:/^\d{4}-?\d{3}-?\d{4}$/'],
            'email'             => 'required|email|unique:users,email',
            'password'          => ['required', 'string', 'confirmed', Password::min(8)->max(94)->mixedCase()->symbols()],
            'role'              => 'required|in:admin,college_staff,org_staff,guest',
            'admin_access'      => 'nullable|in:full,facility_staff,maintenance_staff', // access level under the Admin role
            // A college / org account's requests are filed under its unit name, so it can't be blank
            'college_name'      => 'nullable|required_if:role,college_staff|string|max:255',
            'organization_name' => 'nullable|required_if:role,org_staff|string|max:255',
            'position'          => 'nullable|string|max:120',
        ];
    }

    public function messages(): array
    {
        return [
            'contact_number.regex' => 'Enter an 11-digit contact number in the format 09xx-xxx-xxxx.',
            'college_name.required_if'      => 'Enter the college name. College staff requests are filed under it.',
            'organization_name.required_if' => 'Enter the organization name. Organization requests are filed under it.',
        ];
    }
}
