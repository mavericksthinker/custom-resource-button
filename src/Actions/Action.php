<?php

namespace Mavericks\ResourceCustomButton\Actions;

use Illuminate\Support\Fluent;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Actions\ActionMethod;
use Laravel\Nova\Http\Requests\ActionRequest;
use Laravel\Nova\Actions\Action as NovaAction;
use Laravel\Nova\Exceptions\MissingActionHandlerException;

class Action extends NovaAction
{
    /**
     * Execute the action for the given request.
     *
     * @param ActionRequest $request
     * @return mixed
     * @throws MissingActionHandlerException
     * @throws \Throwable
     */
    public function handleRequest(ActionRequest $request)
    {
        if ($request->globalResourceAction)
        {
            $method = ActionMethod::determine($this, $request->targetModel());

            if (! method_exists($this, $method)) {
                throw MissingActionHandlerException::make($this, $method);
            }

            if($request->withGlobalFields)
            {
                $fields = $this->resolveGlobalFields($request);
            } else{
                $fields = $request->resolveFields();
            }

            return DispatchAction::forModels(
                $request, $this, $method, collect([]), $fields
            );
        }
       return parent::handleRequest($request);
    }

    /**
     * Resolve the fields using the request.
     *
     * @param $request
     * @return \Laravel\Nova\Fields\ActionFields
     */
    public function resolveGlobalFields($request)
    {
        // Validate global fields
        $this->validateGlobalFields($request);

        return once(function () use ($request) {
            $fields = new Fluent;

            $results = collect($request->action()->getAllFields())->mapWithKeys(function ($field) use ($fields, $request) {
                return [$field->attribute => $field->fillForAction($request, $fields)];
            });

            return new ActionFields(collect($fields->getAttributes()), $results->filter(function ($field) {
                return is_callable($field);
            }));
        });
    }

    /**
     * Validate the given global fields.
     *
     * @param $request
     * @return void
     */
    public function validateGlobalFields($request)
    {
        $request->validate(collect($request->action()->globalFields())->mapWithKeys(function ($field) use ($request) {
            return $field->getCreationRules($request);
        })->all());
    }

    /**
     * Get the fields available on the global resource action
     *
     * @return array
     */
    public function globalFields()
    {
        return [];
    }

    /**
     * Get all fields
     *
     * @return array
     */
    protected function getAllFields()
    {
        return array_merge($this->fields(), $this->globalFields());
    }

    /**
     *  Get global fields
     *
     * @return array
     */
    protected function getGlobalFields()
    {
       return [
            'globalFields' => collect($this->globalFields())->each->resolve(new class {
            })->all()
        ];
    }

    /**
     * Prepare the action for JSON serialization.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return array_merge(parent::jsonSerialize(), $this->getGlobalFields());
    }
}
