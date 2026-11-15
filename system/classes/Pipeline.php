<?php
/**
 * Software: SketchCMS
 * Author: valedrat
 *
 * Lightweight Middleware Pipeline for Kernel
 */

class Pipeline
{
    private Request $passable;
    private array $pipes = [];

    public function send(Request $request): self
    {
        $this->passable = $request;
        return $this;
    }

    public function through(array $pipes): self
    {
        $this->pipes = $pipes;
        return $this;
    }

    public function then(callable $destination)
    {
        $pipeline = array_reduce(
            array_reverse($this->pipes),
            function (callable $stack, $pipe) {
                return function (Request $request) use ($stack, $pipe) {
                    if (is_string($pipe)) {
                        $pipe = Container::get($pipe);
                    }
                    if ($pipe instanceof MiddlewareInterface) {
                        return $pipe->handle($request, $stack);
                    }
                    if (is_callable($pipe)) {
                        return $pipe($request, $stack);
                    }
                    return $stack($request);
                };
            },
            $destination
        );

        return $pipeline($this->passable);
    }
}
