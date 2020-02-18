<?php

namespace NLGA\Dropzone\Rules;

use Illuminate\Contracts\Validation\Rule;

class TotalFileCount implements Rule
{
    /**
     * Total file count.
     *
     * @var int
     */
    private $total_count;

    /**
     * Count of already uploaded files.
     *
     * @var int
     */
    private $current_count;

    /**
     * Create a new rule instance.
     *
     * @param int $total_count Total file count.
     * @param int $current_count = 0 Gets added to file count.
     * @return void
     */
    public function __construct(int $total_count, int $current_count = 0)
    {
        $this->total_count   = $total_count;
        $this->current_count = $current_count;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $files
     * @return bool
     */
    public function passes($attribute, $files): bool
    {
        $files = (!is_array($files)) ? [$files] : $files;
        $count = array_reduce($files, function ($sum, $item) { 
            return ($item) ? $sum += 1 : $sum;
        });
    
        return ($count + $this->current_count) <= $this->total_count; 
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return __('dropzone::messages.rules.total_file_count', ['count' => $this->total_count]);
    }
}
