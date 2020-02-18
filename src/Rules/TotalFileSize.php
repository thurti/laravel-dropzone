<?php

namespace NLGA\Dropzone\Rules;

use Illuminate\Contracts\Validation\Rule;

class TotalFileSize implements Rule
{
    /**
     * Total file size in bytes
     *
     * @var int
     */
    private $total_size;

    /**
     * Size of already uploaded files.
     * Gets added to file size.
     *
     * @var int
     */
    private $current_size;

    /**
     * Create a new rule instance.
     *
     * @param int $total_size Total file size in kb.
     * @param int $current_size = 0 In kb, gets added to file size.
     * @return void
     */
    public function __construct(int $total_size, int $current_size = 0)
    {
        $this->total_size   = $total_size   * 1000;
        $this->current_size = $current_size * 1000;
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
        $size = array_reduce($files, function ($sum, $item) { 
            return ($item) ? $sum += $item->getSize() : $sum;
        });
    
        return ($size + $this->current_size) <= $this->total_size; 
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return __('dropzone::messages.rules.total_file_size', ['size' => $this->total_size / 1000]);
    }
}
