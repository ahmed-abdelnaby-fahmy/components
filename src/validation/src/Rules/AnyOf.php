<?php

declare(strict_types=1);

namespace Hypervel\Validation\Rules;

use Hypervel\Support\Arr;
use Hypervel\Support\Facades\Validator;
use Hypervel\Validation\Contracts\Rule;
use Hypervel\Validation\Contracts\Validator as ValidatorContract;
use Hypervel\Validation\Contracts\ValidatorAwareRule;
use InvalidArgumentException;

class AnyOf implements Rule, ValidatorAwareRule
{
    /**
     * The rules to match against.
     */
    protected array $rules = [];

    /**
     * The validator performing the validation.
     */
    protected ?ValidatorContract $validator = null;

    /**
     * Sets the validation rules to match against.
     *
     * @throws InvalidArgumentException
     */
    public function __construct(array $rules)
    {
        if (! is_array($rules)) {
            throw new InvalidArgumentException('The provided value must be an array of validation rules.');
        }

        $this->rules = $rules;
    }

    /**
     * Determine if the validation rule passes.
     */
    public function passes(string $attribute, mixed $value): bool
    {
        foreach ($this->rules as $rule) {
            $validator = Validator::make(
                Arr::isAssoc(Arr::wrap($value)) ? $value : [$value],
                Arr::isAssoc(Arr::wrap($rule)) ? $rule : [$rule],
                $this->validator->customMessages, // @phpstan-ignore-line
                $this->validator->customAttributes // @phpstan-ignore-line
            );

            if ($validator->passes()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the validation error messages.
     */
    public function message(): array|string
    {
        $message = $this->validator->getTranslator()->get('validation.any_of');

        return $message === 'validation.any_of'
            ? ['The :attribute field is invalid.']
            : $message;
    }

    /**
     * Set the current validator.
     */
    public function setValidator(ValidatorContract $validator): static
    {
        $this->validator = $validator;

        return $this;
    }
}
