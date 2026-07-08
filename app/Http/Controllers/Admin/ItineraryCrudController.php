<?php

namespace App\Http\Controllers\Admin;

use App\Models\Itinerary;
use App\Models\User;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class ItineraryCrudController extends CrudController
{
    use ListOperation;
    use ShowOperation;

    public function setup(): void
    {
        CRUD::setModel(Itinerary::class);
        CRUD::setRoute(config('backpack.base.route_prefix').'/itinerary');
        CRUD::setEntityNameStrings('itinerary', 'itineraries');
        CRUD::denyAccess(['create', 'update', 'delete']);
    }

    protected function setupListOperation(): void
    {
        CRUD::addClause('with', 'user');

        CRUD::column('id');
        CRUD::column('title');
        CRUD::column('location');
        CRUD::column([
            'name' => 'user_id',
            'label' => 'User',
            'type' => 'select',
            'entity' => 'user',
            'attribute' => 'name',
            'model' => User::class,
        ]);
        CRUD::column('duration_days');
        CRUD::column('total_estimated_cost')->label('Total (MYR)');
        CRUD::column('status');
        CRUD::column('created_at');
    }

    protected function setupShowOperation(): void
    {
        CRUD::addClause('with', 'user');

        CRUD::column('title');
        CRUD::column('location');
        CRUD::column([
            'name' => 'user_id',
            'label' => 'User',
            'type' => 'select',
            'entity' => 'user',
            'attribute' => 'name',
            'model' => User::class,
        ]);
        CRUD::column('duration_days');
        CRUD::column('budget_min');
        CRUD::column('budget_max');
        CRUD::column('total_estimated_cost');
        CRUD::column('status');
        CRUD::column('summary')->type('textarea');
        CRUD::column('created_at');
    }
}
