<?php

namespace Tests;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Voronoi\Apprentice\Artisan\OutputStyle;
use Voronoi\Apprentice\Libraries\Socket\Reader;
use Voronoi\Apprentice\Messenger;
use Symfony\Component\Console\Input\StringInput;
use Voronoi\Apprentice\Artisan\FileStreamOutput;
use Voronoi\Apprentice\Exceptions\TimeoutException;
use Symfony\Component\Console\Question\Question;
use Mockery;

class OutputStyleTest extends TestCase
{
    use RefreshDatabase;

    public function testAsk()
    {
        $reader    = Mockery::mock(Reader::class);
        $messenger = Mockery::mock(Messenger::class);
        $input     = Mockery::mock(StringInput::class);
        $output    = new FileStreamOutput($messenger);

        $reader->shouldReceive('readUntilData')->once()->andReturn('1');

        // sends ask message
        $messenger->shouldReceive('send')->with(Mockery::on(function ($argument) {
            return $argument->name == "question"
              && $argument->message == ["question" => "Which id?", "hideInput" => false, "default" => null];
        }))->once();

        $outputStyle = new OutputStyle($reader, $messenger, $input, $output);

        $response = $outputStyle->ask("Which id?");

        $this->assertEquals(1, $response);
    }
    
    public function testAskQuestion()
    {
        $reader    = Mockery::mock(Reader::class);
        $messenger = Mockery::mock(Messenger::class);
        $input     = Mockery::mock(StringInput::class);
        $output    = new FileStreamOutput($messenger);

        $reader->shouldReceive('readUntilData')->once()->andReturn('1');

        // sends ask message
        $messenger->shouldReceive('send')->with(Mockery::on(function ($argument) {
            return $argument->name == "question"
              && $argument->message == ["question" => "Which id?", "hideInput" => false, "default" => null];
        }))->once();

        $outputStyle = new OutputStyle($reader, $messenger, $input, $output);

        $question = new Question("Which id?");
        $response = $outputStyle->askQuestion($question);

        $this->assertEquals(1, $response);
    }
    
    public function testAskHidden()
    {
        $reader    = Mockery::mock(Reader::class);
        $messenger = Mockery::mock(Messenger::class);
        $input     = Mockery::mock(StringInput::class);
        $output    = new FileStreamOutput($messenger);

        $reader->shouldReceive('readUntilData')->once()->andReturn('1');

        // sends ask message
        $messenger->shouldReceive('send')->with(Mockery::on(function ($argument) {
            return $argument->name == "question"
              && $argument->message == ["question" => "Which id?", "hideInput" => true, "default" => null];
        }))->once();

        $outputStyle = new OutputStyle($reader, $messenger, $input, $output);

        $response = $outputStyle->askHidden("Which id?");

        $this->assertEquals(1, $response);
    }

    public function testAskUsesDefaultOnTimeout()
    {
        $reader    = Mockery::mock(Reader::class);
        $messenger = Mockery::mock(Messenger::class);
        $input     = Mockery::mock(StringInput::class);
        $output    = new FileStreamOutput($messenger);

        $reader->shouldReceive('readUntilData')->once()
          ->andThrow(new TimeoutException);
        $outputStyle = new OutputStyle($reader, $messenger, $input, $output);

        $messenger->shouldReceive('send')->once();

        $response = $outputStyle->ask("Which id?", '-1');

        $this->assertEquals('-1', $response);
    }

    public function testConfirmReadResponse()
    {
        $reader    = Mockery::mock(Reader::class);
        $messenger = Mockery::mock(Messenger::class);
        $input     = Mockery::mock(StringInput::class);
        $output    = new FileStreamOutput($messenger);

        $reader->shouldReceive('readUntilData')->once()
          ->andReturn('false');
        $outputStyle = new OutputStyle($reader, $messenger, $input, $output);

        $messenger->shouldReceive('send')->once();

        $response = $outputStyle->confirm("Turn on warp drive?");

        $this->assertEquals('false', $response);        
    }
    
    public function testConfirmTimeoutUsesDefault()
    {
        $reader    = Mockery::mock(Reader::class);
        $messenger = Mockery::mock(Messenger::class);
        $input     = Mockery::mock(StringInput::class);
        $output    = new FileStreamOutput($messenger);

        $reader->shouldReceive('readUntilData')->once()
          ->andThrow(new TimeoutException);
        $outputStyle = new OutputStyle($reader, $messenger, $input, $output);

        $messenger->shouldReceive('send')->once();

        $response = $outputStyle->confirm("Turn on warp drive?", true);

        $this->assertEquals(true, $response);        
    }

    public function testChoiceGetResponse()
    {
        $reader    = Mockery::mock(Reader::class);
        $messenger = Mockery::mock(Messenger::class);
        $input     = Mockery::mock(StringInput::class);
        $output    = new FileStreamOutput($messenger);

        $reader->shouldReceive('readUntilData')->once()
          ->andReturn('blueberry');
        $outputStyle = new OutputStyle($reader, $messenger, $input, $output);

        $messenger->shouldReceive('send')->once();

        $response = $outputStyle->choice("Select a smoothie:", ['strawberry', 'lemon', 'blueberry']);

        $this->assertEquals('blueberry', $response);        
    }
    
    public function testChoiceTimeoutUsesDefault()
    {
        $reader    = Mockery::mock(Reader::class);
        $messenger = Mockery::mock(Messenger::class);
        $input     = Mockery::mock(StringInput::class);
        $output    = new FileStreamOutput($messenger);

        $reader->shouldReceive('readUntilData')->once()
          ->andThrow(new TimeoutException);
        $outputStyle = new OutputStyle($reader, $messenger, $input, $output);

        $messenger->shouldReceive('send')->once();

        $response = $outputStyle->choice("Select a smoothie:", ['strawberry', 'lemon', 'blueberry'], 'strawberry');

        $this->assertEquals('strawberry', $response);       
    }
    
    public function testTable()
    {
        $reader    = Mockery::mock(Reader::class);
        $messenger = Mockery::mock(Messenger::class);
        $input     = Mockery::mock(StringInput::class);
        $output    = new FileStreamOutput($messenger);

        $outputStyle = new OutputStyle($reader, $messenger, $input, $output);

        $messenger->shouldReceive('send')->with(Mockery::on(function ($argument) {
            return $argument->name == "table"
              && $argument->message == ["headers" => ['id', 'name'], "rows" => [['1', 'strawberry'], ['2', 'lemon']]];
        }))->once();
        
        $outputStyle->table(['id', 'name'], [['1', 'strawberry'], ['2', 'lemon']]);
    }
    
    public function testProgressDisplays()
    {
        $reader    = Mockery::mock(Reader::class);
        $messenger = Mockery::mock(Messenger::class);
        $input     = Mockery::mock(StringInput::class);
        $output    = new FileStreamOutput($messenger);
        
        $messenger->shouldReceive('send')->once();

        $outputStyle = new OutputStyle($reader, $messenger, $input, $output);

        $outputStyle->progressStart();
    }
    
    public function testAdvance() {
        $reader    = Mockery::mock(Reader::class);
        $messenger = Mockery::mock(Messenger::class);
        $input     = Mockery::mock(StringInput::class);
        $output    = new FileStreamOutput($messenger);
        
        $call = 0;
        $messenger->shouldReceive('send')->with(Mockery::on(function ($argument) use (&$call) {
            $call++;
            if ($call == 1) { return true; }
            
            return $argument->name == "progress"
              && $argument->message == '{"title":"","step":2,"max":null,"complete":false}';
        }))->times(2);    
        
        $outputStyle = new OutputStyle($reader, $messenger, $input, $output);

        $outputStyle->progressStart();        
        $outputStyle->progressAdvance(2);
    }

    public function testFinish() {
        $reader    = Mockery::mock(Reader::class);
        $messenger = Mockery::mock(Messenger::class);
        $input     = Mockery::mock(StringInput::class);
        $output    = new FileStreamOutput($messenger);
        
        $call = 0;
        $messenger->shouldReceive('send')->with(Mockery::on(function ($argument) use (&$call) {
            $call++;
            if ($call == 1) { return true; }
            
            return $argument->name == "progress"
              && $argument->message == '{"title":"","step":0,"max":null,"complete":true}';
        }))->times(2);

        $outputStyle = new OutputStyle($reader, $messenger, $input, $output);

        $outputStyle->progressStart();
        $outputStyle->progressFinish();
    }
    
    public function testTitle()
    {
        $output = $this->outputWithExpectedFormatted('title', 'My Title');        
        $output->title('My Title');
    }
    
    public function testSection()
    {
        $output = $this->outputWithExpectedFormatted('section', 'My Section');
        $output->section('My Section');
    }
    
    public function testListing()
    {
        $output = $this->outputWithExpectedSend('list', ['elements' => ['A', 'B', 'C']]);
        $output->listing(['A', 'B', 'C']);
    }
    
    public function testSingleText()
    {
        $output = $this->outputWithExpectedSend('text', ['messages' => ['Hello World'], 'newline' => false]);
        $output->text('Hello World');
    }

    public function testMultipleTexts()
    {
        $output = $this->outputWithExpectedSend('text', ['messages' => ['Hello', 'World'], 'newline' => false]);
        $output->text(['Hello', 'World']);
    }

    public function testComment()
    {
        $output = $this->outputWithExpectedFormatted('comment', 'Just a comment');
        $output->comment('Just a comment');
    }
    
    public function testInfo()
    {
        $output = $this->outputWithExpectedFormatted('info', 'some basic info');
        $output->info('some basic info');
    }
    
    public function testSuccess()
    {
        $output = $this->outputWithExpectedFormatted('ok', 'finished successfully!');
        $output->success('finished successfully!');
    }
    
    public function testError()
    {
        $output = $this->outputWithExpectedFormatted('error', 'it didn\'t work');
        $output->error('it didn\'t work');
    }
    
    public function testWarning()
    {
        $output = $this->outputWithExpectedFormatted('warning', 'something is happening ...');
        $output->warning('something is happening ...');
    }
    
    public function testNote()
    {
        $output = $this->outputWithExpectedFormatted('note', 'make a note');
        $output->note('make a note');
    }
    
    public function testCaution()
    {
        $output = $this->outputWithExpectedFormatted('caution', 'here be dragons');
        $output->caution('here be dragons');
    }

    public function testWriteLnSingleMessage()
    {
        $reader    = Mockery::mock(Reader::class);
        $messenger = Mockery::mock(Messenger::class);
        $input     = Mockery::mock(StringInput::class);
        $output    = new FileStreamOutput($messenger);

        $outputStyle = new OutputStyle($reader, $messenger, $input, $output);

        $messenger->shouldReceive('send')->with(Mockery::on(function ($argument) {
            return $argument->name == 'text'
              && $argument->message == ['messages' => ["Hello World\n"]];
        }))->once();

        $output->writeln("Hello World");
    }
    
    public function testWriteLnMultipleMessages()
    {
        $reader    = Mockery::mock(Reader::class);
        $messenger = Mockery::mock(Messenger::class);
        $input     = Mockery::mock(StringInput::class);
        $output    = new FileStreamOutput($messenger);

        $outputStyle = new OutputStyle($reader, $messenger, $input, $output);

        $call = 0;
        $messenger->shouldReceive('send')->with(Mockery::on(function ($argument) use (&$call) {
            $call++;
            if ($call == 1) {
                return $argument->name == 'text'
                  && $argument->message == ['messages' => ["Hello\n"]];
            } else {
                return $argument->name == 'text'
                  && $argument->message == ['messages' => ["World\n"]];
            }
        }))->times(2);

        $output->writeln(["Hello", "World"]);
    }    
    
    public function testWriteSingleMessage()
    {
        $output = $this->outputWithExpectedSend('text', ['messages' => ["Hello World"], 'newline' => false]);
        
        $output->write("Hello World");
    }
    
    public function testWriteMultipleMessages()
    {
        $output = $this->outputWithExpectedSend('text', ['messages' => ["Hello"], 'newline' => false]);
        
        $output->write(["Hello"]);
    }
    
    public function testWriteInfo()
    {
        $output = $this->outputWithExpectedSend('formattedText', ['format' => 'info', 'message' => "Hello"]);
        
        $output->write(["<info>Hello</info>"]);
    }
    
    public function testWriteBrokenTagUsesText()
    {
        $output = $this->outputWithExpectedSend('text', ['messages' => ["<info>Hello</infdo>"], 'newline' => false]);
        
        $output->write(["<info>Hello</infdo>"]);
    }
    
    public function testNewLine()
    {
        $output = $this->outputWithExpectedSend('text', ['messages' => ["\n\n"], 'newline' => false]);
        $output->newLine(2);
    }
    
    public function testBlockSingleMessage()
    {
        $output = $this->outputWithExpectedSend('block', ['messages' => ['Hello World'], 'blockType' => 'type', 'style' => 'fancy', 'prefix' => ' ', 'padding' => false, 'escape' => true]);
        $output->block('Hello World', 'type', 'fancy');
    }

    public function testBlockMultipleMessages()
        {
            $output = $this->outputWithExpectedSend('block', ['messages' => ['Hello', 'World'], 'blockType' => 'type', 'style' => 'fancy', 'prefix' => ' ', 'padding' => false, 'escape' => true]);
            $output->block(['Hello', 'World'], 'type', 'fancy');
        }

    private function outputWithExpectedFormatted($expectedFormat, $expectedMessage)
    {
        return $this->outputWithExpectedSend("formattedText", ["format" => $expectedFormat, "message" => $expectedMessage]);
    }
    
    private function outputWithExpectedSend($name, $message)
    {
        $reader    = Mockery::mock(Reader::class);
        $messenger = Mockery::mock(Messenger::class);
        $input     = Mockery::mock(StringInput::class);
        $output    = new FileStreamOutput($messenger);

        $outputStyle = new OutputStyle($reader, $messenger, $input, $output);

        $messenger->shouldReceive('send')->with(Mockery::on(function ($argument) use ($name, $message) {
            return $argument->name == $name
              && $argument->message == $message;
        }))->once();
        
        return $outputStyle;
    }    
}
