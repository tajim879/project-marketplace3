<?php
declare(strict_types=1);

/**
 * Server-Side Input Validator
 */
final class Validator
{
    private array $data;
    private array $errors = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function required(string $field, string $label): self
    {
        $val = trim((string)($this->data[$field] ?? ''));
        if ($val === '') {
            $this->errors[$field] = "{$label} is required.";
        }
        return $this;
    }

    public function minLength(string $field, int $min, string $label): self
    {
        $val = trim((string)($this->data[$field] ?? ''));
        if ($val !== '' && mb_strlen($val, 'UTF-8') < $min) {
            $this->errors[$field] = "{$label} must be at least {$min} characters.";
        }
        return $this;
    }

    public function maxLength(string $field, int $max, string $label): self
    {
        $val = trim((string)($this->data[$field] ?? ''));
        if ($val !== '' && mb_strlen($val, 'UTF-8') > $max) {
            $this->errors[$field] = "{$label} must not exceed {$max} characters.";
        }
        return $this;
    }

    public function email(string $field, string $label = 'Email address'): self
    {
        $val = trim((string)($this->data[$field] ?? ''));
        if ($val !== '' && !filter_var($val, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = "Please enter a valid {$label}.";
        }
        return $this;
    }

    public function username(string $field, string $label = 'Username'): self
    {
        $val = trim((string)($this->data[$field] ?? ''));
        if ($val !== '' && !preg_match('/^[a-zA-Z0-9_]{3,32}$/', $val)) {
            $this->errors[$field] = "{$label} must be 3-32 characters using letters, numbers, or underscores.";
        }
        return $this;
    }

    public function phone(string $field, string $label = 'Phone number'): self
    {
        $val = trim((string)($this->data[$field] ?? ''));
        if ($val !== '' && !preg_match('/^[0-9+\-\s()]{8,22}$/', $val)) {
            $this->errors[$field] = "Please enter a valid {$label}.";
        }
        return $this;
    }

    public function passwordStrength(string $field, string $label = 'Password'): self
    {
        $val = (string)($this->data[$field] ?? '');
        if ($val !== '') {
            if (strlen($val) < 8) {
                $this->errors[$field] = "{$label} must be at least 8 characters long.";
            } elseif (!preg_match('/[A-Za-z]/', $val) || !preg_match('/[0-9]/', $val)) {
                $this->errors[$field] = "{$label} must include both letters and numbers.";
            }
        }
        return $this;
    }

    public function matches(string $field, string $otherField, string $message): self
    {
        if (($this->data[$field] ?? '') !== ($this->data[$otherField] ?? '')) {
            $this->errors[$field] = $message;
        }
        return $this;
    }

    public function inList(string $field, array $allowed, string $label): self
    {
        $val = (string)($this->data[$field] ?? '');
        if ($val !== '' && !in_array($val, $allowed, true)) {
            $this->errors[$field] = "Invalid selection for {$label}.";
        }
        return $this;
    }

    public function url(string $field, string $label = 'URL'): self
    {
        $val = trim((string)($this->data[$field] ?? ''));
        if ($val !== '') {
            $valid = filter_var($val, FILTER_VALIDATE_URL);
            $scheme = strtolower((string)parse_url($val, PHP_URL_SCHEME));
            if (!$valid || !in_array($scheme, ['http', 'https'], true)) {
                $this->errors[$field] = "Please enter a valid http/https {$label}.";
            }
        }
        return $this;
    }

    public function decimal(string $field, string $label, string $min = '0.00'): self
    {
        $val = trim((string)($this->data[$field] ?? ''));
        if ($val !== '') {
            if (!preg_match('/^\d{1,10}(\.\d{1,2})?$/', $val)) {
                $this->errors[$field] = "{$label} must be a valid monetary amount.";
            } elseif (bccomp($val, $min, 2) < 0) {
                $this->errors[$field] = "{$label} must be at least {$min}.";
            }
        }
        return $this;
    }

    public function addError(string $field, string $message): self
    {
        $this->errors[$field] = $message;
        return $this;
    }

    public function passes(): bool
    {
        return empty($this->errors);
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): string
    {
        return (string)(reset($this->errors) ?: 'Validation failed.');
    }
}
