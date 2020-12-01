<?php

namespace Tests;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Voronoi\Apprentice\Transformers\Command as CommandTransformer;
use Illuminate\Console\Command;

class TestCommand extends Command
{
    protected $signature;

    protected $description;

    public function __construct($signature, $description)
    {
        $this->signature   = $signature;
        $this->description = $description;
        parent::__construct();
    }
}

class CommandTest extends TestCase
{
    use RefreshDatabase;

    /*
    |--------------------------------------------------------------------------
    | Collection tests
    |--------------------------------------------------------------------------
    |
    | Ensures transforming 0, one, or many commands works
    |
    |
    |
    */

    public function testMultipleCommands()
    {
        $transformer = new CommandTransformer;

        $result = $transformer->transformCollection([new TestCommand("command1", "description 1"), new TestCommand("command2", "description 2")]);

        $this->assertEquals([[
            "action" => "command1",
            "description" => "description 1",
            "arguments" => [],
          ], [
            "action" => "command2",
            "description" => "description 2",
            "arguments" => [],
          ]
          ], $result);
    }

    public function testEmptyList()
    {
        $transformer = new CommandTransformer;

        $result = $transformer->transformCollection([]);

        $this->assertEquals([], $result);
    }

    public function testOneCommand()
    {
        $transformer = new CommandTransformer;

        $result = $transformer->transform(new TestCommand("command", "description"));

        $this->assertEquals([
            "action" => "command",
            "description" => "description",
            "arguments" => [],
          ], $result);
    }


    /*
    |--------------------------------------------------------------------------
    | Arguments
    |--------------------------------------------------------------------------
    |
    | Tests various argument configurations produce the right values
    |
    |
    |
    */

    public function testArrayArgument()
    {
        $transformer = new CommandTransformer;

        $result = $transformer->transform(new TestCommand("create:users {emails* : a list of user emails}", "Create one or more users"));

        $this->assertEquals([
            "action" => "create:users",
            "description" => "Create one or more users",
            "arguments" => [[
              "type" => "array",
              "argument" => [
                "type" => "array",
                "artisanType" => [
                  "type" => "argument"
                ],
                "name" => "emails",
                "description" => "a list of user emails",
                "defaultValue" => [],
                "isRequired" => true
              ],
            ]],
          ], $result);
    }

    public function testOptionalArrayArgument()
    {
        $transformer = new CommandTransformer;

        $result = $transformer->transform(new TestCommand("create:users {emails?* : a list of user emails}", "Create one or more users"));

        $this->assertEquals([
            "action" => "create:users",
            "description" => "Create one or more users",
            "arguments" => [[
              "type" => "array",
              "argument" => [
                "type" => "array",
                "artisanType" => [
                  "type" => "argument"
                ],
                "name" => "emails",
                "description" => "a list of user emails",
                "defaultValue" => [],
                "isRequired" => false
              ],
            ]],
          ], $result);
    }

    public function testStringArgument()
    {
        $transformer = new CommandTransformer;

        $result = $transformer->transform(new TestCommand("create:user {email : the user's email}", "Create a user"));

        $this->assertEquals([
            "action" => "create:user",
            "description" => "Create a user",
            "arguments" => [[
              "type" => "string",
              "argument" => [
                "type" => "string",
                "artisanType" => [
                  "type" => "argument"
                ],
                "name" => "email",
                "description" => "the user's email",
                "defaultValue" => null,
                "isRequired" => true
              ],
            ]],
          ], $result);
    }

    public function testOptionalStringArgument()
    {
        $transformer = new CommandTransformer;

        $result = $transformer->transform(new TestCommand("create:user {email? : the user's email}", "Create a user"));

        $this->assertEquals([
            "action" => "create:user",
            "description" => "Create a user",
            "arguments" => [[
              "type" => "string",
              "argument" => [
                "type" => "string",
                "artisanType" => [
                  "type" => "argument"
                ],
                "name" => "email",
                "description" => "the user's email",
                "defaultValue" => null,
                "isRequired" => false
              ],
            ]],
          ], $result);
    }

    public function testStringArgumentWithDefault()
    {
        $transformer = new CommandTransformer;

        $result = $transformer->transform(new TestCommand("create:user {email=jane@example.com : the user's email}", "Create a user"));

        $this->assertEquals([
            "action" => "create:user",
            "description" => "Create a user",
            "arguments" => [[
              "type" => "string",
              "argument" => [
                "type" => "string",
                "artisanType" => [
                  "type" => "argument"
                ],
                "name" => "email",
                "description" => "the user's email",
                "defaultValue" => 'jane@example.com',
                "isRequired" => false
              ],
            ]],
          ], $result);
    }

    public function testNumberArgumentWithDefault()
    {
        $transformer = new CommandTransformer;

        $result = $transformer->transform(new TestCommand("create:user {numberOfUsers=5 : the number of users to create}", "Create a user"));

        $this->assertEquals([
            "action" => "create:user",
            "description" => "Create a user",
            "arguments" => [[
              "type" => "string",
              "argument" => [
                "type" => "string",
                "artisanType" => [
                  "type" => "argument"
                ],
                "name" => "numberOfUsers",
                "description" => "the number of users to create",
                "defaultValue" => 5,
                "isRequired" => false
              ],
            ]],
          ], $result);
    }


    /*
    |--------------------------------------------------------------------------
    | Options
    |--------------------------------------------------------------------------
    |
    | Tests various option configurations produce the right values
    |
    |
    |
    */

    public function testArrayOption()
    {
        $transformer = new CommandTransformer;

        $result = $transformer->transform(new TestCommand("create:users {{--emails=* : a list of user emails}", "Create one or more users"));

        $this->assertEquals([
            "action" => "create:users",
            "description" => "Create one or more users",
            "arguments" => [[
              "type" => "array",
              "argument" => [
                "type" => "array",
                "artisanType" => [
                  "type" => "option"
                ],
                "name" => "emails",
                "description" => "a list of user emails",
                "defaultValue" => [],
                "isRequired" => false
              ],
            ]],
          ], $result);
    }

    public function testStringOption()
    {
        $transformer = new CommandTransformer;

        $result = $transformer->transform(new TestCommand("create:user {--email= : the user's email}", "Create a user"));

        $this->assertEquals([
            "action" => "create:user",
            "description" => "Create a user",
            "arguments" => [[
              "type" => "string",
              "argument" => [
                "type" => "string",
                "artisanType" => [
                  "type" => "option"
                ],
                "name" => "email",
                "description" => "the user's email",
                "defaultValue" => null,
                "isRequired" => false
              ],
            ]],
          ], $result);
    }

    public function testStringOptionWithDefault()
    {
        $transformer = new CommandTransformer;

        $result = $transformer->transform(new TestCommand("create:user {--email=jane@example.com : the user's email}", "Create a user"));

        $this->assertEquals([
            "action" => "create:user",
            "description" => "Create a user",
            "arguments" => [[
              "type" => "string",
              "argument" => [
                "type" => "string",
                "artisanType" => [
                  "type" => "option"
                ],
                "name" => "email",
                "description" => "the user's email",
                "defaultValue" => "jane@example.com",
                "isRequired" => false
              ],
            ]],
          ], $result);
    }

    public function testNumberOptionWithDefault()
    {
        $transformer = new CommandTransformer;

        $result = $transformer->transform(new TestCommand("create:user {--numberOfUsers=5 : the number of users to create}", "Create a user"));

        $this->assertEquals([
            "action" => "create:user",
            "description" => "Create a user",
            "arguments" => [[
              "type" => "string",
              "argument" => [
                "type" => "string",
                "artisanType" => [
                  "type" => "option"
                ],
                "name" => "numberOfUsers",
                "description" => "the number of users to create",
                "defaultValue" => 5,
                "isRequired" => false
              ],
            ]],
          ], $result);
    }

    public function testBooleanOption()
    {
        $transformer = new CommandTransformer;

        $result = $transformer->transform(new TestCommand("create:user {--generate-email : will create a random email}", "Create a user"));

        $this->assertEquals([
            "action" => "create:user",
            "description" => "Create a user",
            "arguments" => [[
              "type" => "bool",
              "argument" => [
                "type" => "bool",
                "artisanType" => [
                  "type" => "option"
                ],
                "name" => "generate-email",
                "description" => "will create a random email",
                "defaultValue" => false,
                "isRequired" => false
              ],
            ]],
          ], $result);
    }

    public function testOptionWithShortcut()
    {
        $transformer = new CommandTransformer;

        $result = $transformer->transform(new TestCommand("create:user {--g|generate-email : will create a random email}", "Create a user"));

        $this->assertEquals([
                "action" => "create:user",
                "description" => "Create a user",
                "arguments" => [[
                  "type" => "bool",
                  "argument" => [
                    "type" => "bool",
                    "artisanType" => [
                      "type" => "option"
                    ],
                    "name" => "generate-email",
                    "description" => "will create a random email",
                    "defaultValue" => false,
                    "isRequired" => false
                  ],
                ]],
              ], $result);
    }


    /*
    |--------------------------------------------------------------------------
    | Additional Argument Testing
    |--------------------------------------------------------------------------
    |
    | Other Argument tests that don't fit neatly into the above categories
    |
    |
    |
    */

    public function testArgumentWithNoDescription()
    {
        $transformer = new CommandTransformer;

        $result = $transformer->transform(new TestCommand("create:user {email}", "Create a user"));

        $this->assertEquals([
            "action" => "create:user",
            "description" => "Create a user",
            "arguments" => [[
              "type" => "string",
              "argument" => [
                "type" => "string",
                "artisanType" => [
                  "type" => "argument"
                ],
                "name" => "email",
                "description" => "",
                "defaultValue" => null,
                "isRequired" => true
              ],
            ]],
          ], $result);
    }
}
