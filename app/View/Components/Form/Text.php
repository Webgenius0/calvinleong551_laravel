<?php

namespace App\View\Components\Form;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Text extends Component
{
    public $name;
    public $label;
    public $placeholder;
    public $value;

    public function __construct($name, $label = '', $placeholder = '', $value = '')
    {
        $this->name = $name;
        $this->label = $label;
        $this->placeholder = $placeholder;
        $this->value = $value;
    }
        
    public function render(): View|Closure|string
    {
        return view('components.form.text');
    }
}
