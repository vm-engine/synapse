<?php

/*
 * Larastan boots a bare Testbench app where SynApps isn't autoloaded, so the
 * `synapse::` view namespace is never registered. Register it here so
 * view-string checks can resolve the package's own views.
 */
app('view')->addNamespace('synapse', __DIR__.'/resources/views');
