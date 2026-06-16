<?php

namespace Armezit\Lunar\VirtualProduct\Filament\Resources\ProductResource\Pages;

use Armezit\Lunar\VirtualProduct\Models\CodePoolSchema;
use Armezit\Lunar\VirtualProduct\Models\VirtualProduct;
use Armezit\Lunar\VirtualProduct\SourceProviders\CodePool;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Lunar\Admin\Filament\Resources\ProductResource;
use Lunar\Admin\Support\Pages\BaseEditRecord;

class ManageVirtualProduct extends BaseEditRecord
{
    protected static string $resource = ProductResource::class;

    public bool $virtual_enabled = false;

    public ?int $code_pool_schema_id = null;

    public function getTitle(): string|Htmlable
    {
        return __('lunarphp-virtual-product::default.virtual-product');
    }

    public static function getNavigationLabel(): string
    {
        return __('lunarphp-virtual-product::default.virtual-product');
    }

    public function getBreadcrumb(): string
    {
        return __('lunarphp-virtual-product::default.virtual-product');
    }

    public static function getNavigationIcon(): ?string
    {
        return FilamentIcon::resolve('lunar::product-variants');
    }

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $virtualProduct = $this->getCodePoolVirtualProduct();

        $this->virtual_enabled = $virtualProduct !== null;
        $this->code_pool_schema_id = $virtualProduct?->meta['schemaId'] ?? null;
    }

    protected function getDefaultHeaderActions(): array
    {
        return [];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('lunarphp-virtual-product::default.virtual-product'))
                    ->schema([
                        Toggle::make('virtual_enabled')
                            ->label(__('lunarphp-virtual-product::default.enabled'))
                            ->live(),

                        Select::make('code_pool_schema_id')
                            ->label(__('lunarphp-virtual-product::code-pool.product-settings.schema.label'))
                            ->options(fn (): array => CodePoolSchema::query()
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->preload()
                            ->visible(fn (Get $get): bool => (bool) $get('virtual_enabled'))
                            ->required(fn (Get $get): bool => (bool) $get('virtual_enabled')),
                    ]),
            ])
            ->statePath('');
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (! $this->virtual_enabled) {
            $this->getCodePoolVirtualProduct()?->delete();

            return $record;
        }

        VirtualProduct::query()->updateOrCreate([
            'product_id' => $record->getKey(),
            'source' => CodePool::class,
        ], [
            'meta' => [
                'schemaId' => $this->code_pool_schema_id,
            ],
        ]);

        return $record;
    }

    public function getRelationManagers(): array
    {
        return [];
    }

    protected function getCodePoolVirtualProduct(): ?VirtualProduct
    {
        return VirtualProduct::query()
            ->onlyCodePool()
            ->where('product_id', $this->getRecord()->getKey())
            ->first();
    }
}
