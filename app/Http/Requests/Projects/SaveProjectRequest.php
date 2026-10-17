<?php

declare(strict_types=1);

namespace App\Http\Requests\Projects;

use App\Enums\ProjectColor;
use App\Models\Team;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SaveProjectRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $team = $this->route('current_team');

        abort_if(! $team instanceof Team, 404);

        return [
            'name' => ['required', 'string', 'max:255'],
            'identifier' => [
                'required',
                'string',
                'regex:/^[A-Z]{2,5}$/',
                Rule::unique('projects')->where('team_id', $team->id)->ignore($this->route('project')),
            ],
            'color' => ['required', Rule::enum(ProjectColor::class)],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * Get the custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'identifier.regex' => __('The identifier must be 2 to 5 uppercase letters.'),
            'identifier.unique' => __('Another project in this team already uses this identifier.'),
        ];
    }
}
