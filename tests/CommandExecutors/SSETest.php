<?php

namespace Tests;

use Tests\TestCase;
use Voronoi\Apprentice\CommandExecutors\SSE;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Voronoi\Apprentice\Libraries\Socket\Reader;
use Voronoi\Apprentice\Libraries\Socket\Writer;
use Voronoi\Apprentice\Messenger;
use Voronoi\Apprentice\Artisan\Artisan;
use Illuminate\Console\Command;
use Illuminate\Testing\TestResponse;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Voronoi\Apprentice\Exceptions\TimeoutException;

class SSETest extends TestCase
{
    use RefreshDatabase;

    public function testExecuteCallsArtisan()
    {
        $command = Mockery::mock(Command::class);

        $reader = Mockery::mock(Reader::class);
        $reader->shouldReceive('deleteFile')->once();
        app()->offsetSet(Reader::class, $reader);

        $artisan = Mockery::mock(Artisan::class);
        $artisan->shouldReceive('call')->with(
            $command,
            Mockery::on(function ($arg) {
                return $arg instanceof Reader;
            }),
            Mockery::on(function ($arg) {
                return $arg instanceof Writer;
            }),
            Mockery::on(function ($arg) {
                return $arg instanceof Messenger;
            })
        )->once();

        $sse = new SSE($artisan);

        $sse->execute($command, 1);
    }

    public function testInputWritesData()
    {
        $writer = Mockery::mock(Writer::class);
        $writer->shouldReceive('write')->with("a response")->once();
        app()->offsetSet(Writer::class, $writer);

        $artisan = Mockery::mock(Artisan::class);
        $sse = new SSE($artisan);

        $sse->commandInput("a response", 1);
    }

    public function testOutputReturnsStreamedResponse()
    {
        $artisan = Mockery::mock(Artisan::class);
        $sse = new SSE($artisan);

        $response = $sse->outputResponse(1);

        $this->assertInstanceOf(StreamedResponse::class, $response);
    }

    public function testOutputStreamsUntilDone()
    {
        $reader = Mockery::mock(Reader::class);
        $reader->shouldReceive('readUntilData')->once()->andReturn('{"type": "output", "data":"Hello World"}');
        $reader->shouldReceive('readUntilData')->once()->andReturn('{"type": "done"}');
        $reader->shouldReceive('deleteFile')->once();
        app()->offsetSet(Reader::class, $reader);

        $artisan = Mockery::mock(Artisan::class);
        $sse = new SSE($artisan);

        $response = $sse->outputResponse(1);

        $response = $this->forceStreamedResponseToOutput($response);

        $response->assertSee('data: {"type": "output", "data":"Hello World"}'."\n", false);
        $response->assertSee('data: {"type": "done"}'."\n", false);
    }

    public function testOutputTimeout()
    {
        $reader = Mockery::mock(Reader::class);
        $reader->shouldReceive('readUntilData')
        ->once()
        ->andThrow(new TimeoutException);

        $reader->shouldReceive('deleteFile')->once();
        app()->offsetSet(Reader::class, $reader);

        $artisan = Mockery::mock(Artisan::class);
        $sse = new SSE($artisan);

        $response = $sse->outputResponse(1);

        $response = $this->forceStreamedResponseToOutput($response);

        $response->assertSee('data: {"type":"timeout","data":"failed to receive output"}'."\n", false);
    }

    private function forceStreamedResponseToOutput($response)
    {
        // This seems to be the only way to capture a streamed response
        // https://stackoverflow.com/questions/51857346/test-a-streamed-response
        ob_start();
        $response->sendContent();
        ob_get_clean();
        $content = ob_get_clean();
        ob_start();

        return new TestResponse(
            new Response(
                $content,
                $response->getStatusCode(),
                $response->headers->all()
            )
        );
    }
}
