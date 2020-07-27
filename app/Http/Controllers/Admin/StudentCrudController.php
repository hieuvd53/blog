<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\StudentRequest;
use App\Models\School;
use App\Models\Sclass;
use App\Models\Student;
use App\Models\User;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Class StudentCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class StudentCrudController extends CrudController
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
        $classId = request()->route()->parameter('class_id');
        $school = School::findOrFail($schoolId);
        $class = Sclass::findOrFail($classId);
        $this->crud->setModel('App\Models\Student');
        $this->crud->setRoute(config('backpack.base.route_prefix') . '/master_schools/' . $schoolId . '/classes/' . $classId . '/students');
        $this->crud->setEntityNameStrings('学生', '学生');
        $this->crud->setTitle("学年一覧", "index");
        $this->crud->setHeading("校舎ID：{$class->id}　　校舎名：{$class->name}", "index");
        $this->crud->setSubheading('some string', 'list');
        $this->crud->setListView('admin/masterdata/class');

        $this->crud->addClause('whereHas', 'class', function ($query) use ($classId) {
            $query->where('class_id', $classId);
        });
    }

    private function setupFields()
    {
        $this->crud->addFields([

            [
                "type" => "text",
                "name" => "name",
                "label" => "名前"
            ],
            [
                "type" => "hidden",
                "name" => "class_id",
                "value" =>  request()->route()->parameter('class_id'),
            ],
            [
                "type" => "text",
                "name" => "display_order",
                "label" => "表示順",
            ],
            [
                "type" => "text",
                "name" => "email",
                "label" => "メールアドレス",
            ],
            [
                "type" => "password",
                "name" => "password",
                "label" => "パスワード",
            ],
            [
                "type" => "password",
                "name" => "password_confirmation",
                "label" => "パスワード",
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
                "label" => "学生氏名",
            ],
        ]);
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation(StudentRequest::class);
        $this->setupFields();
    }

    protected function setupUpdateOperation()
    {
        $this->crud->setValidation(StudentRequest::class);
        $this->crud->addFields([
            [
                "type" => "text",
                "name" => "name",
                "label" => "名前"
            ],
            [
                "type" => "hidden",
                "name" => "class_id",
                "value" => request()->route()->parameter('class_id'),
            ],
            [
                "type" => "text",
                "name" => "display_order",
                "label" => "表示順",
            ],
        ]);
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
                "name" => "student_code",
                "label" => "学生コード",
            ],
            [
                "type" => "text",
                "name" => "class_id",
                "label" => "学年ID",
            ],

        ]);
    }
    public function store(StudentRequest $request)
    {
        try {
            DB::beginTransaction();
            $this->crud->hasAccessOrFail('create');

            // execute the FormRequest authorization and validation, if one is required
            $request = $this->crud->getRequest();
            $this->crud->setValidation(StudentRequest::class);
            $newUser = $this->createUserForStudent($request);

            $newStudent = $this->createStudent($request, $newUser );

            $this->crud->setSaveAction();
            DB::commit();
            return $this->crud->performSaveAction($newStudent->getKey());
        } catch (Exception $e) {
            DB::rollBack();
            throw($e);
        }
    }

    public function createUserForStudent($requestData)
    {
        $user = User::create([
            "name" => $requestData->name,
            "password" => bcrypt($requestData->password),
            "email" => $requestData->email,
        ]);
        return $user;
    }
    public function createUserForImportStudent($requestData)
    {
        $user = User::create([
            "name" => $requestData['name'],
            "password" => bcrypt($requestData['password']),
            "email" => $requestData['email'],
        ]);
        return $user;
    }
    public function createImportStudent($requestData, $user)
    {
        $student = Student::create([
            "name" => $requestData['name'],
            "class_id" => $requestData['class_id'],
            "display_order" => $requestData['display_order'],
            "user_id" => $user['id'],
        ]);
        return $student;
    }
    public function createStudent($requestData, $user)
    {
        $student = Student::create([
            "name" => $requestData->name,
            "class_id" => $requestData->class_id,
            "display_order" => $requestData->display_order,
            "user_id" => $user->id,
        ]);
        return $student;
    }
    public function updateUserForStudent($requestData)
    {
        $student = Student::find($requestData->id);
        $user = User::find($student->user_id);
        $user->name = $requestData->name;
        $user->save();

        return $user;
    }

    public function updateStudent($requestData, $newUser)
    {
        $student = Student::find($requestData->id);

        $student->name = $requestData->name;
        $student->display_order = $requestData->display_order;
        $student->save();

        return $student;
    }

    public function update()
    {
        try {
            DB::beginTransaction();

            // execute the FormRequest authorization and validation, if one is required
            $request = $this->crud->getRequest();
            $newUser = $this->updateUserForStudent($request);

            $newStudent = $this->updateStudent($request, $newUser);

            $this->crud->setSaveAction();
            DB::commit();
            return $this->crud->performSaveAction($newStudent->getKey());
        } catch (Exception $e) {
            DB::rollBack();
            throw($e);
        }
    }
    public function processRow(array $rawData, array $stripped, int $index)
    {
        // TODO: Compare header
        $createStudentRequest = new StudentRequest();
        $rawData['class_id'] =  request()->route()->parameter('class_id');
        $validator = Validator::make($rawData, $createStudentRequest->rules(), $createStudentRequest->messages());
        throw_if($validator->fails(), new Exception($this->formatValidationErrorResponse($index, $validator->errors()->all())));
        $data = $validator->validated();
        $data['class_id'] = request()->route()->parameter('class_id');
        $user = $this->createUserForImportStudent($data);


        $this->createImportStudent($data, $user);
    }
}
