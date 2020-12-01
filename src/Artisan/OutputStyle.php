<?php

namespace Voronoi\Apprentice\Artisan;

use Illuminate\Console\OutputStyle as BaseOutputStyle;
use Voronoi\Apprentice\Exceptions\TimeoutException;
use Voronoi\Apprentice\Message;
use Symfony\Component\Console\Question\Question;
use Voronoi\Apprentice\Artisan\Concerns\DecodesLaravelCommandOutput;

class OutputStyle extends BaseOutputStyle
{
    use DecodesLaravelCommandOutput;
    
    private $reader;
    private $messenger;

    private $progress = null;

    public function __construct($reader, $messenger, $input, $output)
    {
        $this->reader    = $reader;
        $this->messenger = $messenger;

        parent::__construct($input, $output);
    }

    /**
     * {@inheritdoc}
     */
    public function askQuestion(Question $question)
    {
        return $this->performAsk($question->getQuestion(), $question->isHidden(), $question->getDefault());
    }

    /**
     * {@inheritdoc}
     */
    public function ask($question, $default = null, $validator = null)
    {
        return $this->performAsk($question, false, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function askHidden($question, $validator = null)
    {
        return $this->performAsk($question, true);
    }

    private function performAsk($question, $isHidden, $default = null)
    {
        $data = [
          'question'  => $question,
          'hideInput' => $isHidden,
          'default'   => $default
        ];
        return $this->sendIO(new Message('question', $data), $default);
    }
    
    private function sendIO($message, $default = null)
    {
        $this->messenger->send($message);

        try {
            return $this->reader->readUntilData(1);
        } catch (TimeoutException $exception) {
            return $default;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function confirm($question, $default = true)
    {
        $data = [
          'question' => $question,
          'default'  => $default
        ];
        $message = new Message('confirmation', $data);
        
        return $this->sendIO($message, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function choice($question, $choices, $default = null)
    {
        $message = new Message('multipleChoice', [
          'question' => $question,
          'choices'  => $choices,
          'default'  => $default
        ]);
        
        return $this->sendIO($message, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function table($headers, $rows)
    {
        $data = [
          'headers' => $headers,
          'rows'    => $rows
      ];
        $this->messenger->send(new Message('table', $data));
    }


    // Progress Bar

    /**
     * {@inheritdoc}
     */
    public function progressStart($max = 0)
    {
        $this->progress = $this->createProgressBar($max);
        $this->progress->display();
    }

    /**
     * {@inheritdoc}
     */
    public function progressAdvance($step = 1)
    {
        if (!is_null($this->progress)) {
            $this->progress->advance($step);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function progressFinish()
    {
        if (!is_null($this->progress)) {
            $this->progress->finish();
        }
        $this->progress = null;
    }

    /**
     * {@inheritdoc}
     */
    public function createProgressBar($max = 0)
    {
        return new ProgressBar($this->messenger, $max);
    }

    // text output

    /**
     * {@inheritdoc}
     */
    public function title($message)
    {
        $this->sendFormatted('title', $message);
    }

    /**
     * {@inheritdoc}
     */
    public function section($message)
    {
        $this->sendFormatted('section', $message);
    }

    /**
     * {@inheritdoc}
     */
    public function listing($elements)
    {
        $data = [
          'elements' => $elements
        ];
        $this->messenger->send(new Message('list', $data));
    }

    /**
     * {@inheritdoc}
     */
    public function text($message)
    {
        $messages = is_array($message) ? array_values($message) : array($message);

        $data = [
          'messages' => $messages,
          'newline' => false
        ];
        $this->messenger->send(new Message('text', $data));
    }


    // Formatted

    private function sendFormatted($format, $message)
    {
        $data = [
          'format'  => $format,
          'message' => $message
        ];
        $this->messenger->send(new Message('formattedText', $data));
    }

    /**
     * {@inheritdoc}
     */
    public function comment($message)
    {
        $this->sendFormatted('comment', $message);
    }

    public function info($message)
    {
        $this->sendFormatted('info', $message);
    }

    /**
     * {@inheritdoc}
     */
    public function success($message)
    {
        $this->sendFormatted('ok', $message);
    }

    /**
     * {@inheritdoc}
     */
    public function error($message)
    {
        $this->sendFormatted('error', $message);
    }

    /**
     * {@inheritdoc}
     */
    public function warning($message)
    {
        $this->sendFormatted('warning', $message);
    }

    /**
     * {@inheritdoc}
     */
    public function note($message)
    {
        $this->sendFormatted('note', $message);
    }

    /**
     * {@inheritdoc}
     */
    public function caution($message)
    {
        $this->sendFormatted('caution', $message);
    }

    // Write data

    /**
     * {@inheritdoc}
     */
     public function writeln($messages, int $type = self::OUTPUT_NORMAL)
    {
        $messages = is_array($messages) ? array_values($messages) : array($messages);
        $this->write($messages, true, $type);
    }

    /**
     * {@inheritdoc}
     */
    public function write($messages, $newline = false, $type = self::OUTPUT_NORMAL)
    {
        $messages = is_array($messages) ? array_values($messages) : array($messages);

        foreach ($messages as $message) {
            [$format, $output] = $this->decodeFormat($message);
            if ($format == 'text') {
                $this->text($output);
            } else {
                $this->sendFormatted($format, $output);
            }
        }

        if ($newline) {
            $this->newLine();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function newLine($count = 1)
    {
        $data = [
          'messages' => [str_repeat("\n", $count)],
          'newline' => false,
        ];
        $this->messenger->send(new Message('text', $data));
    }

    /**
     * {@inheritdoc}
     */
    public function block($messages, $type = null, $style = null, $prefix = ' ', $padding = false, $escape = true)
    {
        $messages = is_array($messages) ? array_values($messages) : array($messages);

        $data = [
          'messages'  => $messages,
          'blockType' => $type,
          'style'     => $style,
          'prefix'    => $prefix,
          'padding'   => $padding,
          'escape'    => $escape
        ];
        $this->messenger->send(new Message('block', $data));
    }
}
