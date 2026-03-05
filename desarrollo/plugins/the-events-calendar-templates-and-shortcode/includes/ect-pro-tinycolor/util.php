<?php


// Return a valid alpha value [0,1] with all invalid values being set to 1
function EctProboundAlpha($a)
{
    // $a = floatval($a);

    if (!is_numeric($a) || $a < 0 || $a > 1) {
        $a = 1;
    }

    return $a;
}

// Take input from [0, n] and return it as [0, 1]
function EctProbound01($n, $max)
{
    if (EctProisOnePointZero($n)) {
        $n = "100%";
    }

    $processPercent = EctProisPercentage($n);

    $n = min($max, max(0, floatval($n)));

    // Automatically convert percentage into number
    if ($processPercent) {
        $n = intval($n * $max, 10) / 100;
    }

    // Handle floating point rounding errors
    if ((abs($n - $max) < 0.000001)) {
        return 1;
    }

    // Convert into [0, 1] range if it isn't already
    return EctProparseIntFromFloat(fmod($n, $max) / floatval($max));
}

function EctProparseIntFromFloat($val)
{
    return $val == intval($val) ? intval($val) : $val;
}

// Force a number between 0 and 1
function EctProclamp01($val)
{
    return min(1, max(0, $val));
}

// Parse a base-16 hex value into a base-10 integer
function EctProparseIntFromHex($val)
{
    return intval($val, 16);
}

// Need to handle 1.0 as 100%, since once it is a number, there is no difference between it and 1
// <http://stackoverflow.com/questions/7422072/javascript-how-to-detect-number-as-a-decimal-including-1-0>
function EctProisOnePointZero($n)
{
    return is_string($n) && strpos($n, '.') !== false && floatval($n) === 1.0;
}

// Check to see if string passed in is a percentage
function EctProisPercentage($n)
{
    return is_string($n) && strpos($n, '%') !== false;
}

// Force a hex value to have 2 characters
function EctPropad2($c)
{
    return strlen($c) == 1 ? '0' . $c : '' . $c;
}

// Replace a decimal with it's percentage value
function EctProconvertToPercentage($n)
{
    if (strpos($n, '%') === false && $n <= 1) {
        $n = ($n * 100) . "%";
    }

    return $n;
}

// Converts a decimal to a hex value
function EctProconvertDecimalToHex($d)
{
    return dechex(round(floatval($d) * 255));
}

// Converts a hex value to a decimal
function EctProconvertHexToDecimal($h)
{
    return (EctProparseIntFromHex($h) / 255);
}

function ectProvalidateWCAG2Parms($parms)
{
    // return valid WCAG2 parms for isReadable.
    // If input parms are invalid, return {"level":"AA", "size":"small"}

    // $parms = $parms ?: ['level' => 'AA', 'size' => 'small'];
    $ect_level = isset($parms['level'])?$parms['level']:'AA';
    $ect_size = isset($parms['size'])?$parms['size']:'small';
    $level = strtoupper($ect_level);
    $size  = strtolower($ect_size);

    if ($level != "AA" && $level != "AAA") {
        $level = "AA";
    }
    if ($size != "small" && $size != "large") {
        $size = "small";
    }
    return ['level' => $level, 'size' => $size];
}

function Ecttinycolor($color, array $opts = [])
{
    return \Ecttinycolor\Ecttinycolor::parse($color, $opts);
}
