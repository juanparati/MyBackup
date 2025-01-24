<?php

namespace App\Commands\Concerns;

use App\Models\Placeholder;

trait NeedActions
{
    use NeedConfig;

    protected function runActions(array $dictionary = []): void
    {

        $this->newLine()->info('Running post actions');

        $placeholder = new Placeholder($dictionary);

        foreach ($this->config->post_actions as $k => $action) {
            $actionName = array_key_first($action);
            $instructions = $action[$actionName];
            $actionClass = 'App\\Actions\\'.str($actionName)->camel()->ucfirst().'Action';

            if (! class_exists($actionClass)) {
                $this->warn("Action [$k] $actionName is not available");

                continue;
            }

            $this->task(
                "- Running action [$k] $actionName",
                fn () => (new $actionClass($this->config, $placeholder, $instructions))()
            );
        }
    }
}
