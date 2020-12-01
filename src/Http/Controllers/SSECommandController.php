<?php

namespace Voronoi\Apprentice\Http\Controllers;

use Voronoi\Apprentice\Storage;
use Voronoi\Apprentice\Session;
use Illuminate\Routing\ViewController;
use Illuminate\Http\Request;

use Voronoi\Apprentice\CommandExecutors\SSE as SSECommandExecutor;
use Voronoi\Apprentice\Http\Requests\Execute as ExecuteRequest;
use Voronoi\Apprentice\Http\Requests\Input as InputRequest;
use Voronoi\Apprentice\Http\Requests\Output as OutputRequest;

class SSECommandController extends ViewController
{
    protected $commandExecutor;
    protected $session;

    public function __construct(SSECommandExecutor $commandExecutor, Session $session)
    {
        $this->commandExecutor = $commandExecutor;
        $this->session = $session;
    }

    public function execute(ExecuteRequest $request)
    {
        $command = $request->get('command');
        $userID = $this->session->currentUserID();

        $this->abortIfNull($userID);

        $this->commandExecutor->execute($command, $userID);

        return response()->json("success");
    }

    public function input(InputRequest $request)
    {
        $data = $request->get('data');
        $userID = $this->session->currentUserID();

        $this->abortIfNull($userID);

        $this->commandExecutor->commandInput($data, $userID);

        return response()->json('success');
    }

    public function output(OutputRequest $request)
    {
        $userID = $this->session->currentUserID();

        $this->abortIfNull($userID);

        return $this->commandExecutor->outputResponse($userID);
    }

    private function abortIfNull($userID)
    {
        if (is_null($userID)) {
            abort(response()->json("no user", 422));
        }
    }
}
