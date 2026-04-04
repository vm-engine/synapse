<?php

use Livewire\Component;

new class extends Component
{
    public function render()
    {
        $fronendLayout = theme_service()->resolveThemeLayout('frontend');

        if (! view()->exists($fronendLayout)) {
            $fronendLayout = 'synapps::components.layouts.layout';
        }

        return $this->view()->layout($fronendLayout);
    }
};
