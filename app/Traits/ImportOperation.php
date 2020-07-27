<?php

namespace App\Traits;

use App\Exceptions\Import\InsertModelFailedException;
use App\Exceptions\Import\UpdateModelFailedException;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use League\Csv\Reader;

/**
 * Trait ImportOperation.
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
trait ImportOperation
{
    /**
     * Lays some default settings for import operation.
     */
    protected function setupImportDefaults()
    {
        $this->crud->set('import_key', 'id');
        $this->crud->allowAccess('import');
        $this->crud->set('imported_success_message', '__DATA_IMPORTED__');
        $this->crud->operation('list', function () {
            $this->crud->addButtonFromView('top', 'import', 'import', 'end');
        });
    }

    protected function setupImportRoutes($segment, $routeName, $controller)
    {
        Route::post($segment.'/import', [
            'as'        => $routeName.'.import',
            'uses'      => $controller.'@import',
            'operation' => 'import',
        ]);
    }

    public function import()
    {
        $this->crud->hasAccessOrFail('import');
        $request = $this->crud->validateRequest();
        $request->validate([
            'import' => 'required|file',
        ]);
        $csv = $this->readImportFile($request->file('import'));

        $header = $csv->getHeader();
        // throw_if(! in_array('id', $header), new ValidationException('Id column not found'));

        $modelTableColumns = $this->getModelTableColumns();

        // get only columns that are present in both header and database
        $safeColumns = array_intersect($header, $modelTableColumns);

        DB::beginTransaction();
        try {
            foreach ($csv->getRecords() as $index => $row) {
                $this->processRow($row, $safeColumns, $index);
            }
            DB::commit();

            return response()->json(['message' => $this->crud->get('imported_success_message')]);
        } catch (Exception $e) {
            DB::rollBack();
            report($e);
            throw $e;
        }
    }

    protected function getModelTableColumns()
    {
        return array_values(array_diff(array_keys($this->crud->getDbTableColumns()), [$this->crud->model->getCreatedAtColumn(), $this->crud->model->getUpdatedAtColumn(), 'deleted_at']));
    }

    protected function readImportFile(UploadedFile $file)
    {
        $csvReader = Reader::createFromPath($file->getRealPath(), 'r');

        $csvReader->setHeaderOffset(0);

        return $csvReader;
    }

    protected function getImportValidationClassFromConfiguration(string $configuration)
    {
        if ($this->crud->has($configuration)) {
            //throw_if(! class_exists($this->crud->get($configuration)), new Exception("Object '{$this->crud->get($configuration)}' does not exits"));

            return new $this->crud->get($configuration);
        }

        return false;
    }

    protected function getImportValidationClassFromOperation(string $action)
    {
        $operations = [
            'insert' => 'setupCreateOperation',
            'update' => 'setupUpdateOperation',
        ];
        if (method_exists($this, $operations[$action])) {
            $this->{$operations[$action]}();
            $className = $this->crud->getFormRequest();
            logger(gettype($className));

            return new $className();
        }

        return false;
    }

    protected function getImportValidationClass(string $action)
    {
        foreach (['import.validation', "{$action}.import.validation"] as $configuration) {
            $validation = $this->getImportValidationClassFromConfiguration($configuration);
            if ($validation) {
                return $validation;
            }
        }

        return $this->getImportValidationClassFromOperation($action) ?? false;
    }

    protected function processRow(array $row, array $safeColumns, int $index)
    {
        // only get columns presents in database
        $safeValues = array_intersect_key($row, array_flip($safeColumns));
        $modelKey = $this->crud->get('import_key');
        $modelId = $safeValues[$modelKey] ?? false;
        if (! $modelId) {
            return $this->insertRowToDatabase($safeValues, $index);
        }

        return $this->updateRowInDatabase($safeValues, $index);
    }

    private function insertRowToDatabase(array $attributes, $index)
    {
        $requestValidator = $this->getImportValidationClass('insert');
        if ($requestValidator !== false) {
            $validator = Validator::make($attributes, $requestValidator->rules(), $requestValidator->messages());

            throw_if($validator->fails(), new Exception($this->formatValidationErrorResponse($index, $validator->errors()->all())));
        }

        $modelKey = $this->crud->get('import_key');

        unset($attributes[$modelKey]);
        throw_unless($this->crud->model->insert($attributes), new InsertModelFailedException());

        logger()->info([
            'action' => 'insert model '.get_class($this->crud->model),
            'new_data' => $attributes,
        ]);
    }

    private function updateRowInDatabase(array $attributes, $index)
    {
        $requestValidator = $this->getImportValidationClass('update');
        if ($requestValidator !== false) {
            $validator = Validator::make($attributes, $requestValidator->rules(), $requestValidator->messages());

            throw_if($validator->fails(), new Exception($this->formatValidationErrorResponse($index, $validator->errors()->all())));
        }

        $modelKey = $this->crud->get('import_key');
        $modelId = $attributes[$modelKey];

        throw_unless($this->crud->model->where($modelKey, $modelId)->update($attributes), new UpdateModelFailedException());

        logger()->info([
            'action' => 'insert model'.get_class($this->crud->model)."::{$modelId}",
            'new_data' => $attributes,
        ]);
    }

    /**
     * Transform array of validation errors to html with dotted and such.
     * @param int $index row index
     * @param array $errorBag
     * @return string formatted HTML.
     */
    private function formatValidationErrorResponse(int $index, array $errorBag)
    {
        return
            "<b>__validation_fail_on_row_{$index}__</b>".
            '<ul>'.
                 '<li>'.implode('</li><li>', $errorBag).'</li>'.
            '</ul>';
    }
}
