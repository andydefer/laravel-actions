<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Integration\Directives;

use AndyDefer\Actions\Directives\MakeActionDirective;
use AndyDefer\Actions\Tests\IntegrationTestCase;
use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Services\DirectiveInteractionService;
use AndyDefer\Directive\Collections\ParameterCollection;
use AndyDefer\Directive\Records\ParameterRecord;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;

#[AllowMockObjectsWithoutExpectations]
final class MakeActionDirectiveIntegrationTest extends IntegrationTestCase
{
    private MockObject&DirectiveInteractionService $interaction;
    private MakeActionDirective $directive;

    protected function setUp(): void
    {
        parent::setUp();

        $this->interaction = $this->createMock(DirectiveInteractionService::class);
        $this->directive = new MakeActionDirective($this->interaction);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_getSignature_returns_correct_signature(): void
    {
        $signature = $this->directive->getSignature();

        $this->assertStringContainsString('make:action', $signature);
        $this->assertStringContainsString('{name', $signature);
        $this->assertStringContainsString('--type=', $signature);
        $this->assertStringContainsString('--force', $signature);
    }

    public function test_getDescription_returns_correct_description(): void
    {
        $description = $this->directive->getDescription();

        $this->assertStringContainsString('Create a new Action class', $description);
    }

    public function test_getAliases_returns_correct_aliases(): void
    {
        $aliases = $this->directive->getAliases();

        $this->assertTrue($aliases->contains('action:make'));
        $this->assertTrue($aliases->contains('create:action'));
        $this->assertSame(2, $aliases->count());
    }

    public function test_shouldBootLaravel_returns_true(): void
    {
        $this->assertTrue($this->directive->shouldBootLaravel());
    }

    public function test_execute_with_valid_api_action_returns_success(): void
    {
        // Simuler les arguments
        $arguments = new ParameterCollection();
        $arguments->add(new ParameterRecord(name: 'name', value: 'TestAction'));
        $this->directive->setArguments($arguments);

        // Simuler les options
        $options = new ParameterCollection();
        $options->add(new ParameterRecord(name: 'type', value: 'api'));
        $options->add(new ParameterRecord(name: 'force', value: false));
        $this->directive->setOptions($options);

        $this->interaction->expects($this->atLeastOnce())
            ->method('info');

        $result = $this->directive->execute();

        $this->assertSame(ExitCode::SUCCESS, $result);
    }

    public function test_execute_with_valid_web_action_returns_success(): void
    {
        $arguments = new ParameterCollection();
        $arguments->add(new ParameterRecord(name: 'name', value: 'TestAction'));
        $this->directive->setArguments($arguments);

        $options = new ParameterCollection();
        $options->add(new ParameterRecord(name: 'type', value: 'web'));
        $options->add(new ParameterRecord(name: 'force', value: false));
        $this->directive->setOptions($options);

        $this->interaction->expects($this->atLeastOnce())
            ->method('info');

        $result = $this->directive->execute();

        $this->assertSame(ExitCode::SUCCESS, $result);
    }

    public function test_execute_with_invalid_type_returns_failure(): void
    {
        $arguments = new ParameterCollection();
        $arguments->add(new ParameterRecord(name: 'name', value: 'TestAction'));
        $this->directive->setArguments($arguments);

        $options = new ParameterCollection();
        $options->add(new ParameterRecord(name: 'type', value: 'invalid'));
        $options->add(new ParameterRecord(name: 'force', value: false));
        $this->directive->setOptions($options);

        $this->interaction->expects($this->once())
            ->method('error')
            ->with("Invalid type 'invalid'. Allowed types: api, web");

        $result = $this->directive->execute();

        $this->assertSame(ExitCode::FAILURE, $result);
    }

    public function test_execute_without_name_returns_failure(): void
    {
        $arguments = new ParameterCollection();
        $this->directive->setArguments($arguments);

        $options = new ParameterCollection();
        $options->add(new ParameterRecord(name: 'type', value: 'api'));
        $options->add(new ParameterRecord(name: 'force', value: false));
        $this->directive->setOptions($options);

        $result = $this->directive->execute();

        $this->assertSame(ExitCode::FAILURE, $result);
    }

    public function test_execute_with_force_flag_success(): void
    {
        $arguments = new ParameterCollection();
        $arguments->add(new ParameterRecord(name: 'name', value: 'ExistingAction'));
        $this->directive->setArguments($arguments);

        $options = new ParameterCollection();
        $options->add(new ParameterRecord(name: 'type', value: 'api'));
        $options->add(new ParameterRecord(name: 'force', value: true));
        $this->directive->setOptions($options);

        $this->interaction->expects($this->atLeastOnce())
            ->method('info');

        $result = $this->directive->execute();

        $this->assertSame(ExitCode::SUCCESS, $result);
    }
}
