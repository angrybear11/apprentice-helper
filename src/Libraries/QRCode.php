<?php

namespace Voronoi\Apprentice\Libraries;

use Endroid\QrCode\QrCode as BaseQRCode;
use Endroid\QrCode\ErrorCorrectionLevel;

// Adapted from https://github.com/shelwinnn/qrcode-terminal to use endroid/qr-code and be more testable
//
// The MIT License (MIT)
//
// Copyright (c) <Shelwin Wei>
//
// Permission is hereby granted, free of charge, to any person obtaining a copy
// of this software and associated documentation files (the "Software"), to deal
// in the Software without restriction, including without limitation the rights
// to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
// copies of the Software, and to permit persons to whom the Software is
// furnished to do so, subject to the following conditions:
//
// The above copyright notice and this permission notice shall be included in
// all copies or substantial portions of the Software.
//
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
// IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
// FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
// AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
// LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
// OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
// THE SOFTWARE.

class QRCode
{
    protected $backColor = "\033[40m  \033[0m";
    protected $foreColor = "\033[47m  \033[0m";

    public function setBackColor($backColor)
    {
        $this->backColor = $backColor;
    }

    public function setForeColor($foreColor)
    {
        $this->foreColor = $foreColor;
    }

    public function terminal($text, $level = null, $size = 3, $margin = 4)
    {
        $qrCode = new BaseQRCode($text);
        $qrCode->setErrorCorrectionLevel($level ?? ErrorCorrectionLevel::LOW());

        $data = $qrCode->getData();

        $output = '';
        foreach ($data["matrix"] as $k => $data) {
            $len = count($data);
            $border = str_repeat($this->foreColor, $len + 2);
            if ($k === 0) {
                $output .= $border . "\n";
            }
            $curLine = '';
            for ($i = 0; $i< count($data); $i++) {
                $curLine .= ($data[$i] ? $this->backColor : $this->foreColor);
            }
            $output .= $this->foreColor. $curLine. $this->foreColor. "\n";

            if ($k === $len -1) {
                $output .= $border . "\n";
            }
        }
        return $output;
    }
}
