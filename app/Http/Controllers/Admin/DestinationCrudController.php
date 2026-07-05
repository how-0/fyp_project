<?php

namespace App\Http\Controllers\Admin;

use App\Models\Destination;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class DestinationCrudController extends CrudController
{
    use CreateOperation;
    use DeleteOperation;
    use ListOperation;
    use ShowOperation;
    use UpdateOperation;

    public function setup(): void
    {
        CRUD::setModel(Destination::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/destination');
        CRUD::setEntityNameStrings('destination', 'destinations');
    }

    protected function setupListOperation(): void
    {
        CRUD::column('name');
        CRUD::column('state');
        CRUD::column('category');
        CRUD::column('is_featured')->type('boolean');
    }

    protected function setupCreateOperation(): void
    {
        CRUD::field('name');
        CRUD::field('state');
        CRUD::field('category');
        CRUD::field('lat')->type('number')->attributes(['step' => 'any']);
        CRUD::field('lng')->type('number')->attributes(['step' => 'any']);
        CRUD::field('description')->type('textarea');
        CRUD::field('image_url');
        CRUD::field('is_featured')->type('boolean');
    }

    protected function setupUpdateOperation(): void
    {
        $this->setupCreateOperation();
    }
}
