<?php

namespace Voronoi\Apprentice\Transformers;

/**
 * Transform commands for sharing with the Apprentice App
 */
class Command
{
    /**
     * Transform a collection for the API.
     * @param  Illuminate\Support\Collection<Illuminate\Console\Command> $commands Collection of commands
     * @return Array
     */
    public function transformCollection($commands)
    {
        return collect($commands)->map(function ($command) {
            return $this->transform($command);
        })
          ->values()
          ->toArray();
    }

    /**
     * Transform one command
     * @param  Illuminate\Console\Command $command A command to
     * @return Array
     */
    public function transform($command)
    {
        return [
          'action'      => $command->getName(),
          'description' => $command->getDescription(),
          'arguments'   => $this->transformArguments($command->getDefinition()),
        ];
    }

    private function transformArguments($inputDefinition)
    {
        $arguments = collect($inputDefinition->getArguments())
          ->map(function ($argument) {
              $type = $argument->isArray() ? 'array' : 'string';
              return [
                'type' => $type,
                'argument' => [
                  'type' => $type,
                  'artisanType'  => ['type' => 'argument'],
                  'name'         => $argument->getName(),
                  'description'  => $argument->getDescription(),
                  'defaultValue' => $this->prepareDefault($argument->getDefault(), $type),
                  'isRequired'   => $argument->isRequired(),
                ]
              ];
          })->values()->toArray();

        $options = collect($inputDefinition->getOptions())
          ->map(function ($option) {
              $type = 'bool';
              if ($option->isArray()) {
                  $type = 'array';
              } elseif ($option->acceptValue()) {
                  $type = 'string';
              }
              return [
                'type' => $type,
                'argument' => [
                  'type' => $type,
                  'artisanType'  => ['type' => 'option'],
                  'name'         => $option->getName(),
                  'description'  => $option->getDescription(),
                  'defaultValue' => $this->prepareDefault($option->getDefault(), $type),
                  'isRequired'   => $option->isValueRequired(),
                ]
              ];
          })->values()->toArray();

        return array_merge($arguments, $options);
    }

    private function prepareDefault($default, $type)
    {
        switch ($type) {
        case 'array':
          return $default;
        case 'bool':
          return (bool)$default;
        case 'string':
        default:
          return (string) $default;
      }
    }
}
