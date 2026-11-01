<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
final class FreightRateRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    protected function prepareForValidation(): void
    {
        $values = [];
        foreach (['origin', 'destination', 'price', 'transporter', 'remarks'] as $key) {
            if (is_string($this->input($key))) { $values[$key] = trim($this->input($key)); }
        }
        $this->merge($values);
    }
    public function rules(): array
    {
        return [
            'origin' => ['required', 'string', 'max:100'],
            'destination' => ['required', 'string', 'max:100'],
            'price' => ['bail', 'required', 'numeric', 'gt:0', 'max:999999999.99', 'regex:/^\d{1,9}(\.\d{1,2})?$/'],
            'transporter' => ['bail', 'required', 'string', 'max:150', 'exists:transporters,name'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'version' => [$this->isMethod('PUT') ? 'required' : 'nullable', 'integer', 'min:1'],
        ];
    }
    public function after(): array
    {
        return [function (Validator $validator): void {
            $origin = $this->input('origin'); $destination = $this->input('destination');
            if (is_string($origin) && is_string($destination) && mb_strtolower($origin) === mb_strtolower($destination)) {
                $validator->errors()->add('destination', 'Origin and destination must be different.');
            }
        }];
    }
    public function messages(): array
    {
        return [
            'price.regex' => 'Enter a positive price with up to two decimal places.',
            'transporter.exists' => 'Select a registered transporter. Add new names on the Transporters page first.',
        ];
    }
}
