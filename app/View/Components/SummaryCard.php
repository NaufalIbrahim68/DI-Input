<?php

namespace App\View\Components;

use Illuminate\View\Component;

class SummaryCard extends Component
{
    public $value;
    public $label;
    public $color;

    public function __construct($value, $label, $color)
    {
        $this->value = $value;
        $this->label = $label;
        $this->color = $color;
    }

    public function render()
    {
        return view('components.summary-card');
    }
}
