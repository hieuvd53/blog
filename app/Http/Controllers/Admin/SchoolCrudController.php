<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\SchoolRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class SchoolCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class SchoolCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup()
    {
        $this->crud->setModel('App\Models\School');
        $this->crud->setRoute(config('backpack.base.route_prefix') . '/master_schools');
        $this->crud->setEntityNameStrings('校舎', '校舎');
        $this->crud->setTitle("校舎一覧", "index");
        $this->crud->setHeading("校舎一覧", "index");
    }

    private function setupFields()
    {
        $this->crud->addFields([
            [
                "type" => "text",
                "name" => "name",
                "label" => "校舎名",
            ],
            [
                "type" => "text",
                "name" => "display_order",
                "label" => "表示順",
            ],
        ]);
    }

    protected function setupListOperation()
    {
        $this->crud->setColumns([
            [
                'name' => 'row_number',
                'type' => 'row_number',
                'label' => '#',
                'orderable' => false,
            ],

            [
                "type" => "text",
                "name" => "name",
                "label" => "校舎名",
                'wrapper' => [
                    'href' => function ($crud, $column, $entry, $related_key) {
                        return backpack_url("master_schools/{$entry->getKey()}/classes");
                    },
                ],
            ],
            [
                "type" => "relationship_count",
                "name" => "sclass",
                "label" => "クラス数",
                'suffix' => '',
            ],
        ]);
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation(SchoolRequest::class);
        $this->setupFields();
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }
    protected function setupShowOperation()
    {
        $this->setupListOperation();
        $this->crud->addColumns([
            [
                "type" => "text",
                "name" => "display_order",
                "label" => "表示順",
            ],
        ]);
    }
}
