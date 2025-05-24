<?php

declare(strict_types=1);

namespace Hypervel\Validation;

use Closure;
use Hypervel\Translation\CreatesPotentiallyTranslatedStrings;
use Hypervel\Validation\Contracts\Rule as RuleContract;
use Hypervel\Validation\Contracts\Validator;
use Hypervel\Validation\Contracts\ValidatorAwareRule;

class ClosureValidationRule implements RuleContract, ValidatorAwareRule
{
    use CreatesPotentiallyTranslatedStrings;

    /**
     * Indicates if the validation callback failed.
     */
    public bool $failed = false;

    /**
     * The validation error messages.
     */
    public array $messages = [];

    /**
     * The current validator.
     */
    protected ?Validator $validator = null;

    /**
     * Create a new Closure based validation rule.
     *
     * @param Closure $callback the callback that validates the attribute
     */
    public function __construct(
        protected Closure $callback
    ) {
    }

    /**
     * Determine if the validation rule passes.
     */
    public function passes(string $attribute, mixed $value): bool
    {
        $this->failed = false;

        $this->callback->__invoke($attribute, $value, function ($attribute, $message = null) {
            $this->failed = true;

            return $this->pendingPotentiallyTranslatedString($attribute, $message);
        }, $this->validator);

        return ! $this->failed;
    }

    /**
     * Get the validation error messages.
     */
    public function message(): array
    {
        return $this->messages;
    }

    /**
     * Set the current validator.
     */
    public function setValidator(Validator $validator): static
    {
        $this->validator = $validator;

        return $this;
    }
}
