<?php

declare(strict_types=1);

//
// use VmEngine\Synapse\Services\ConfigGenerator\BuilderBase;
//
// describe('BuilderBase', function () {
//     beforeEach(function () {
//         $this->synapps = ['test' => 'data'];
//         $this->builder = new BuilderBase($this->synapps);
//     });
//
//     it('can be instantiated', function () {
//         expect($this->builder)->toBeInstanceOf(BuilderBase::class);
//     });
//
//     it('stores synapps reference', function () {
//         $reflection = new ReflectionClass($this->builder);
//         $property = $reflection->getProperty('synapps');
//         $property->setAccessible(true);
//
//         expect($property->getValue($this->builder))->toBe(['test' => 'data']);
//     });
//
//     it('has init method', function () {
//         expect($this->builder)->toHaveMethod('init');
//     });
//
//     it('has preLoop method', function () {
//         expect($this->builder)->toHaveMethod('preLoop');
//     });
//
//     it('has loop method', function () {
//         expect($this->builder)->toHaveMethod('loop');
//     });
//
//     it('has postLoop method', function () {
//         expect($this->builder)->toHaveMethod('postLoop');
//     });
//
//     it('has finalize method', function () {
//         expect($this->builder)->toHaveMethod('finalize');
//     });
//
//     it('init method does nothing by default', function () {
//         $result = $this->builder->init();
//         expect($result)->toBeNull();
//     });
//
//     it('preLoop method does nothing by default', function () {
//         $result = $this->builder->preLoop();
//         expect($result)->toBeNull();
//     });
//
//     it('loop method does nothing by default', function () {
//         $result = $this->builder->loop('test', ['config' => 'data']);
//         expect($result)->toBeNull();
//     });
//
//     it('postLoop method does nothing by default', function () {
//         $result = $this->builder->postLoop();
//         expect($result)->toBeNull();
//     });
//
//     it('finalize method does nothing by default', function () {
//         $result = $this->builder->finalize();
//         expect($result)->toBeNull();
//     });
// });
