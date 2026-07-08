<?php

namespace App\Http\Controllers\Admin;

use App\Models\AttractionPrice;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\Request;

class AttractionPriceCrudController extends CrudController
{
    use CreateOperation {
        store as traitStore;
    }
    use DeleteOperation;
    use ListOperation;
    use ShowOperation;
    use UpdateOperation {
        update as traitUpdate;
    }

    public function setup(): void
    {
        CRUD::setModel(AttractionPrice::class);
        CRUD::setRoute(config('backpack.base.route_prefix').'/attraction-price');
        CRUD::setEntityNameStrings('attraction price', 'attraction prices');
    }

    protected function setupListOperation(): void
    {
        CRUD::column('name');
        CRUD::column('state');
        CRUD::column('price_myr')->label('Price (MYR)');
        CRUD::column('price_label');
        CRUD::column('source_name');
        CRUD::column('price_as_of')->type('date');
        CRUD::column('is_active')->type('boolean');
    }

    protected function setupCreateOperation(): void
    {
        CRUD::field('name');
        CRUD::field('state');
        CRUD::field('place_id')->hint('Optional Google Place ID for exact matching');
        CRUD::field([
            'name' => 'aliases_text',
            'label' => 'Aliases',
            'type' => 'textarea',
            'hint' => 'Alternate names Gemini might use, one per line',
        ]);
        CRUD::field('category');
        CRUD::field('price_myr')->type('number')->attributes(['step' => '0.01']);
        CRUD::field('price_label');
        CRUD::field('source_name');
        CRUD::field('source_url');
        CRUD::field('price_as_of')->type('date');
        CRUD::field('notes')->type('textarea');
        CRUD::field('is_active')->type('boolean')->default(true);
    }

    protected function setupUpdateOperation(): void
    {
        $this->setupCreateOperation();

        $entry = $this->crud->getCurrentEntry();
        CRUD::modifyField('aliases_text', [
            'value' => implode("\n", $entry->aliases ?? []),
        ]);
    }

    protected function setupShowOperation(): void
    {
        CRUD::column('name');
        CRUD::column('state');
        CRUD::column('place_id');
        CRUD::column('aliases')->type('array');
        CRUD::column('category');
        CRUD::column('price_myr')->label('Price (MYR)');
        CRUD::column('price_label');
        CRUD::column('source_name');
        CRUD::column('source_url');
        CRUD::column('price_as_of');
        CRUD::column('notes');
        CRUD::column('is_active')->type('boolean');
    }

    public function store()
    {
        $this->crud->hasAccessOrFail('create');
        $request = $this->crud->validateRequest();
        $request->merge($this->transformAliases($request));

        return $this->traitStore();
    }

    public function update()
    {
        $this->crud->hasAccessOrFail('update');
        $request = $this->crud->validateRequest();
        $request->merge($this->transformAliases($request));

        return $this->traitUpdate();
    }

    private function transformAliases(Request $request): array
    {
        $lines = $request->input('aliases_text', '');

        return [
            'aliases' => array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $lines)))),
        ];
    }
}
