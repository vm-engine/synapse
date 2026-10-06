<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Components;

use Illuminate\View\Component;

class DragNDrop extends Component
{
    public function __construct(
        public string $name = 'file',
        public ?string $wireModel = null,
        public string $accept = 'image/png,image/jpeg,image/webp',
        public int $maxSize = 5120, // KB (default 5MB)
        public ?int $maxWidth = null,
        public ?int $maxHeight = null,
        public bool $preview = true,
        public string $label = 'Drop File Here',
        public string $description = 'Drag and drop your PNG, JPG, WebP images here or browse',
        public string $browseText = 'Browse File',
        public ?string $error = null,
    ) {}

    public function render()
    {
        return view('synapse::components.drag-n-drop');
    }
}
