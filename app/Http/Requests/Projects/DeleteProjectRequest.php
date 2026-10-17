<?php

declare(strict_types=1);

namespace App\Http\Requests\Projects;

use App\Models\Project;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

final class DeleteProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('delete', $this->project());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'identifier' => ['required', 'string'],
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->input('identifier') !== $this->project()->identifier) {
                    $validator->errors()->add('identifier', __('The project identifier does not match.'));
                }
            },
        ];
    }

    /**
     * Get the project associated with the request.
     */
    private function project(): Project
    {
        $project = $this->route('project');

        abort_if(! $project instanceof Project, 404);

        return $project;
    }
}
