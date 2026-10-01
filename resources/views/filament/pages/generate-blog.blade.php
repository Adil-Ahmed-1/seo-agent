<x-filament-panels::page>
    <form wire:submit="generate">
        {{ $this->form }}

        <div class="mt-6">
            <x-filament::button
                type="submit"
                size="lg"
                icon="heroicon-o-sparkles"
                :disabled="$isGenerating"
            >
                🚀 Generate Blog
            </x-filament::button>
        </div>
    </form>

    <x-filament::section class="mt-6">
        <x-slot name="heading">
            💡 Tips for Better Results
        </x-slot>

        <ul class="list-disc list-inside space-y-2 text-sm">
            <li><strong>Keyword specificity:</strong> "web design services" better than "design"</li>
            <li><strong>Long-tail keywords:</strong> 3-5 words target karo</li>
            <li><strong>Word count:</strong> 1500-2000 words SEO ke liye best hai</li>
            <li><strong>Tone:</strong> Business/agency ke liye "professional" best hai</li>
            <li><strong>Review:</strong> Auto-generate ke baad content review karna must hai</li>
        </ul>
    </x-filament::section>
</x-filament-panels::page>