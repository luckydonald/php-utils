<?php
namespace luckydonald\phpUtils\exceptions;

class PyFormatter {
    static function exception(
        \Throwable $e, bool $show_data = false, bool $skip_seen = true, ?array $seen = null
    ): string {
        if ($skip_seen && !$seen) {
            $seen = array();
        }
        $prev  = $e->getPrevious();
        if ($prev) {
            $result[] = self::exception($prev, $show_data, $skip_seen, $seen);
            $result[] = "\nDuring handling of the above exception, another exception occurred:\n";
        }
        $trace = self::exception_to_trace($e);
        $str = self::trace($trace, $show_data,$skip_seen, $seen, "# Exception thrown");
        $str.= sprintf("\n%s: %s", get_class($e), $e->getMessage());
        return $str;
    }

    static function trace(
        array $trace, bool $show_data = false, bool $skip_seen = false, ?array $seen = null, string $end_message='# You are here.'
    ): string {
        if ($skip_seen && !$seen) {
            $seen = array();
        }
        $result = array();
        $trace = array_reverse($trace);
        $trace_count = count($trace);
        $result[] = "Traceback (most recent call last):";
        $last_stack = null;
        $current = null;
        while ($trace_count > 0) {
            $stack = $trace[0];

            $last_current = $current;
            $current = serialize($stack);
            if ($skip_seen && is_array($seen) && in_array($current, $seen) && in_array($last_current, $seen)) {
                $duplications = 1;
                while ($trace_count > 1) {
                    $last_stack = $stack;
                    $stack = $trace[0];
                    $current = serialize($stack);
                    if (!in_array($current, $seen)) {
                        $result[] = sprintf('  ... %d more ...', $duplications + 1);
                        break;
                    }
                    $duplications++;
                    array_shift($trace);
                    $trace_count--;
                }
                echo "";
            }

            //File "/Users/me/Desktop/exception_test.py", line 8, in foo
            if ($last_stack) {
                $func_name = sprintf('%s%s%s',
                    $trace_count && array_key_exists('class', $last_stack) ? str_replace('\\', '.', $last_stack['class']) : '',
                    $trace_count && array_key_exists('class', $last_stack) && array_key_exists('function', $last_stack) ? '.' : '',  // dot only if function and class.
                    $trace_count && array_key_exists('function', $last_stack) && $last_stack['function'] ? str_replace('\\', '.', $last_stack['function']) : '{???}'
                );
            } else {
                $func_name = '{main}';
            }
            $last_stack = $stack;
            $result[] = sprintf('  File %s%s in %s',
                array_key_exists('file', $stack) ? '"'.$stack['file'].'"' : 'Unknown Source',
                array_key_exists('file', $stack) && array_key_exists('line', $stack) && $stack['line'] ? sprintf(', line %s', $stack['line']) : '',
                $func_name
            );
            if ($trace_count > 1) {
                $result[] = sprintf("    %s%s%s(%s)",
                    $trace_count && array_key_exists('class', $stack) ? str_replace('\\', '.', $stack['class']) : '',
                    $trace_count && array_key_exists('class', $stack) && array_key_exists('function', $stack) ? (array_key_exists('type', $stack) ? $stack['type'] : '.') : '',  // dot only if function and class.
                    $trace_count && array_key_exists('function', $stack) && $stack['function'] ? str_replace('\\', '.', $stack['function']) : '{main}',
                    $show_data ? ($trace_count && array_key_exists('args', $stack) && $stack['args'] !== null ? implode(", ", array_map(function ($arg) use ($stack) {return implode('', array_map(function ($line) use ($stack) { return trim($line); }, explode("\n", @var_export($arg, true))));}, $stack['args'])) : '') : '…'
                );
            } else {
                $result[] = sprintf("    %s", $end_message);
            }

            if ($skip_seen && is_array($seen)) {
                $seen[] = $current;
            }
            if (!$trace_count) {
                break;
            }
            array_shift($trace);
            $trace_count--;
        }
        $result = join("\n", $result);
        return $result;
    }

    public function exception_to_trace(\Throwable $e): array {
        $trace = $e->getTrace();
        // add the current line as well
        $trace = array_merge([["file" => $e->getFile(), "line" => $e->getLine(), "function" => null, "args" => null]], $trace);
        return $trace;
    }
}