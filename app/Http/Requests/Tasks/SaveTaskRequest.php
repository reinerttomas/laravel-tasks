<?php

declare(strict_types=1);

namespace App\Http\Requests\Tasks;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SaveTaskRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request; an update may send only the fields it changes.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $presence = $this->isMethod('POST') ? ['required'] : ['sometimes', 'required'];

        return [
            'title' => [...$presence, 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:20000'],
            'status' => ['sometimes', Rule::enum(TaskStatus::class)],
            'priority' => ['sometimes', Rule::enum(TaskPriority::class)],
            'assignee_id' => ['sometimes', 'nullable', 'string'],
            'due_date' => ['sometimes', 'nullable', 'date'],
        ];
    }

    /**
     * Get the validated task attributes, cast to their domain types.
     *
     * @return array{title?: string, description?: string|null, status?: TaskStatus, priority?: TaskPriority, assignee_id?: string|null, due_date?: CarbonImmutable|null}
     */
    public function taskAttributes(): array
    {
        $attributes = [];

        if ($this->has('title')) {
            $attributes['title'] = $this->string('title')->value();
        }

        if ($this->has('description')) {
            $attributes['description'] = $this->filled('description') ? $this->string('description')->value() : null;
        }

        if ($this->has('status')) {
            $attributes['status'] = TaskStatus::from($this->string('status')->value());
        }

        if ($this->has('priority')) {
            $attributes['priority'] = TaskPriority::from($this->string('priority')->value());
        }

        if ($this->has('assignee_id')) {
            $attributes['assignee_id'] = $this->filled('assignee_id') ? $this->string('assignee_id')->value() : null;
        }

        if ($this->has('due_date')) {
            $attributes['due_date'] = $this->filled('due_date') ? CarbonImmutable::parse($this->string('due_date')->value()) : null;
        }

        return $attributes;
    }
}
