<?php

namespace App\Rules;

use App\Models\Category;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class IsLeafCategory implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $category = Category::find($value);

        if (! $category) {
            $fail('The selected category does not exist.');

            return;
        }

        if ($category->isGroup()) {
            $fail('The selected category is a grouping category and cannot have transactions assigned to it.');
        }
    }
}
