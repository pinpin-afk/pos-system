<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;

class DocumentNumberService
{
    /**
     * @param  class-string<Model>  $model
     */
    public function next(string $prefix, string $model, string $column = 'number'): string
    {
        $date = now()->format('Ymd');
        $pattern = "{$prefix}-{$date}-%";

        $last = $model::query()
            ->where($column, 'like', $pattern)
            ->lockForUpdate()
            ->orderByDesc($column)
            ->value($column);

        $sequence = 1;

        if (is_string($last) && preg_match('/-(\d+)$/', $last, $matches) === 1) {
            $sequence = (int) $matches[1] + 1;
        }

        return sprintf('%s-%s-%05d', $prefix, $date, $sequence);
    }
}
