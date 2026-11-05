<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Booking;
use App\Models\Facility;
use App\Rules\OperatingHours;
use App\Rules\PersonName;
use App\Rules\WithinVenueCapacity;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreFacilitiesUtilizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->routeIs('public.request.store') && $this->user()) {
            $this->merge(['last_name' => $this->user()->last_name]);
        }
        // College / org requests are filed under the account's unit name (requesterUnit())
        if ($this->routeIs('college.requests.facilities.store', 'org.requests.facilities.store') && $this->user()) {
            $this->merge(['account_unit' => $this->routeIs('college.*') ? $this->user()->college_name : $this->user()->organization_name]);
        }

        if (! $this->exists('facility_ids') && $this->filled('facility_id')) {
            $this->merge(['facility_ids' => [$this->input('facility_id')]]);
        }
    }

    public function rules(): array
    {
        $requiresAddress = $this->routeIs('public.request.store');

        return [
            'last_name' => ['required', 'string', 'max:120', new PersonName],
            'account_unit' => [Rule::requiredIf($this->user() && $this->routeIs('college.requests.facilities.store', 'org.requests.facilities.store')), 'nullable', 'string'],
            'date_activity' => 'required|date|after_or_equal:' . now()->addDays(30)->toDateString(),
            // Optional last day for multi-day events (same start/end time every day), up to 14 days in total
            'date_end'      => [
                'nullable',
                'date',
                'after_or_equal:date_activity',
                function ($attribute, $value, $fail) {
                    $start = $this->input('date_activity');
                    if ($start && $value && Carbon::parse($start)->diffInDays(Carbon::parse($value)) > 13) {
                        $fail('A multi-day event can last at most 14 days.');
                    }
                },
            ],
            'start_time'    => ['required', 'date_format:H:i', new OperatingHours],
            'end_time'      => ['required', 'date_format:H:i', 'after:start_time', new OperatingHours],
            'facility_ids.*' => ['required', 'integer', 'distinct', 'exists:facilities,id'],
            'facility_ids' => [
                'required',
                'array',
                'min:1',
                function ($attribute, $value, $fail) {
                    if (! is_array($value) || count($value) !== count(array_unique($value))) {
                        return;
                    }

                    $facilities = Facility::whereIn('id', $value)->get();
                    if ($facilities->count() !== count($value)) {
                        return;
                    }

                    foreach ($facilities as $facility) {
                        if (! $facility->isAvailable()) {
                            $fail($facility->name . ' is currently unavailable for requests.');
                            return;
                        }
                    }
                }
            ],
            'purpose'       => 'required|string|max:500',
            'street_address' => [Rule::requiredIf($requiresAddress), 'nullable', 'string', 'max:255'],
            'barangay' => [Rule::requiredIf($requiresAddress), 'nullable', 'string', 'max:120'],
            'city_municipality' => [Rule::requiredIf($requiresAddress), 'nullable', 'string', 'max:120'],
            'province' => [Rule::requiredIf($requiresAddress), 'nullable', 'string', 'max:120', Rule::in(\App\Http\Controllers\AddressLookupController::PROVINCES)],
            'country' => [Rule::requiredIf($requiresAddress), 'nullable', 'string', 'max:80', Rule::in(['Philippines'])],
            'number_of_persons' => ['required', 'integer', 'min:1', 'max:100000', new WithinVenueCapacity($this->input('facility_ids'))],
            'qty_monobloc'  => 'nullable|integer|min:0',
            'qty_table'     => 'nullable|integer|min:0',
            'qty_fan'       => 'nullable|integer|min:0',
            'qty_rostrum'   => 'nullable|integer|min:0',
            'qty_flag'      => 'nullable|integer|min:0',
            'qty_sound'     => 'nullable|integer|min:0',
            'qty_led'       => 'nullable|integer|min:0',
            'equipment_other' => 'nullable|string|max:255', // legacy single text box
            ...\App\Models\FormSubmission::OTHER_EQUIPMENT_RULES,
            'venue_others'  => 'nullable|string|max:255',

            // Noted by (signatory)
            'noted_signatory_id' => 'nullable|string',
            'noted_signatory_custom' => 'nullable|string|max:255',

            // Receipt and supporting documents
            'payment_attachment' => [
                'nullable',
                Rule::prohibitedIf(fn () => $this->routeIs('public.request.store')),
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'mimetypes:image/jpeg,image/png,application/pdf',
                'max:10240',
            ],
            'attachments' => 'nullable|array|max:10',
            'attachments.*' => [
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'mimetypes:image/jpeg,image/png,application/pdf',
                'max:10240',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'facility_ids.required' => 'Select at least one venue.',
            'date_activity.required' => 'Pick the date of the activity.',
            'start_time.required' => 'Enter a start time.',
            'end_time.required' => 'Enter an end time.',
            'end_time.after' => 'The end time must be later than the start time.',
            'purpose.required' => 'Describe what the venue will be used for.',
            'number_of_persons.required' => 'Enter the number of persons attending.',
            'account_unit.required' => $this->routeIs('college.*')
                ? "Your account has no college name yet, so this request can't be filed. Ask the GSU administrator to add your college to your account."
                : "Your account has no organization name yet, so this request can't be filed. Ask the GSU administrator to add your organization to your account.",
            'province.in' => 'Requests are accepted from Western Visayas (Region VI) only: Aklan, Antique, Capiz, Guimaras, Iloilo or Negros Occidental.',
            'last_name.required' => $this->routeIs('public.request.store')
                ? 'Enter your last name exactly as you will type it in the status tracker.'
                : 'Your account needs a separately recorded last name before you can submit a request. Ask your unit administrator to update your account.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $data = $validator->getData();
            foreach (['date_activity', 'date_end', 'start_time', 'end_time', 'facility_ids'] as $field) {
                if ($validator->errors()->has($field)) {
                    return;
                }
            }
            foreach ($validator->errors()->keys() as $errorKey) {
                if (str_starts_with($errorKey, 'facility_ids.')) {
                    return;
                }
            }

            $facilityIds = array_map('intval', $data['facility_ids']);
            $startDay = Carbon::parse($data['date_activity'])->startOfDay();
            $endDay = Carbon::parse($data['date_end'] ?? $data['date_activity'])->startOfDay();

            for ($day = $startDay->copy(); $day->lte($endDay); $day->addDay()) {
                $slotStart = Carbon::parse($day->toDateString() . ' ' . $data['start_time']);
                $slotEnd = Carbon::parse($day->toDateString() . ' ' . $data['end_time']);
                $conflict = Booking::query()
                    ->whereHas('facilities', fn ($query) => $query->whereIn('facilities.id', $facilityIds))
                    ->whereIn('status', ['reserved', 'rescheduled', 'booked'])
                    ->where('start_time', '<', $slotEnd)
                    ->where('end_time', '>', $slotStart)
                    ->with('facilities:id,name')
                    ->first();

                if ($conflict) {
                    $facility = $conflict->facilities->first(fn ($facility) => in_array((int) $facility->id, $facilityIds, true));
                    $facilityName = $facility?->name ?? 'A selected facility';
                    $validator->errors()->add(
                        'start_time',
                        "{$facilityName} is reserved from {$conflict->start_time->format('g:i A')} to {$conflict->end_time->format('g:i A')} on {$day->format('M j, Y')}. Choose a time that does not overlap."
                    );
                    return;
                }
            }
        });
    }

    public function attributes(): array
    {
        return [
            'date_activity' => 'date of activity',
            'date_end'      => 'end date',
            'payment_attachment' => 'payment receipt',
            'attachments.*' => 'supporting file',
            'facility_ids' => 'venue', 'facility_ids.*' => 'venue', 'number_of_persons' => 'number of persons',
            'city_municipality' => 'city/municipality', 'street_address' => 'street address',
            'noted_signatory_id' => 'noted by', 'noted_signatory_custom' => 'noted by name',
            'other_equipment.*.name' => 'other equipment name', 'other_equipment.*.qty' => 'other equipment quantity',
        ];
    }
}