<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\SClassRequest;
use App\Models\School;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class SClassCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class SClassCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        $schoolId = request()->route()->parameter('school_id');
        $school = School::findOrFail($schoolId);
        $this->crud->setModel('App\Models\Sclass');
        $this->crud->setRoute(config('backpack.base.route_prefix') .'/master_schools/'.$schoolId. '/classes');
        $this->crud->setEntityNameStrings('学年', '学年');

        $this->crud->setTitle("学年一覧", "index");
        $this->crud->setHeading("校舎ID：{$school->id}　　校舎名：{$school->name}", "index");
        $this->crud->setSubheading('some string', 'list');
        $this->crud->setListView('admin/masterdata/class');
        $this->crud->addClause('whereHas','school', function ($query) use ($schoolId){
            $query->where('school_id', $schoolId);
        });
    }

       private function setupFields()
    {
        $this->crud->addFields([
            [
                "type" => "text",
                "name" => "name",
                "label" => "学年",
            ],
            [
                "type" => "hidden",
                "name" => "school_id",
                "value" =>  request()->route()->parameter('school_id'),
            ],
            [
                "type" => "text",
                "name" => "display_order",
                "label" => "表示順",
            ],
        ]);
    }
    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
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
                "label" => "学年",
                'wrapper' => [
                    'href' => function ($crud, $column, $entry, $related_key) {
                        $schoolId = request()->route()->parameter('school_id');
                        return backpack_url("master_schools/$schoolId/classes/{$entry->getKey()}/students");
                    },
                ],
            ]
        ]);
    }

    /**
     * Define what happens when the Create operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(SClassRequest::class);
        $this->setupFields();
    }

    /**
     * Define what happens when the Update operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
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
            [
                "type" => "text",
                "name" => "school_id",
                "label" => "学校ID",
            ],
        ]);
    }
}
