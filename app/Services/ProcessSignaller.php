<?php

namespace App\Services;

/**
 * Sends signals to a process and all of its descendants.
 *
 * Media jobs run shell scripts that spawn ffmpeg as a child process, so
 * signalling only the script's PID would leave ffmpeg running. Descendants
 * are discovered through /proc on Linux; elsewhere only the PID is signalled.
 */
class ProcessSignaller
{
    /**
     * @return list<int> the PIDs that were signalled
     */
    public function signalTree(int $pid, int $signal): array
    {
        $pids = [$pid, ...$this->descendants($pid)];

        $this->signalAll($pids, $signal);

        return $pids;
    }

    /**
     * @param  list<int>  $pids
     */
    public function signalAll(array $pids, int $signal): void
    {
        foreach ($pids as $target) {
            if (function_exists('posix_kill')) {
                @posix_kill($target, $signal);
            }
        }
    }

    /**
     * @return list<int>
     */
    public function descendants(int $pid): array
    {
        $children = $this->childMap();
        $result = [];
        $pending = [$pid];

        while ($pending !== []) {
            $current = array_pop($pending);

            foreach ($children[$current] ?? [] as $child) {
                $result[] = $child;
                $pending[] = $child;
            }
        }

        return $result;
    }

    /**
     * @return array<int, list<int>> parent PID => child PIDs
     */
    private function childMap(): array
    {
        $map = [];
        $statFiles = glob('/proc/[0-9]*/stat') ?: [];

        foreach ($statFiles as $statFile) {
            $stat = @file_get_contents($statFile);

            if ($stat === false) {
                continue;
            }

            // Format: "pid (comm) state ppid ..."; comm may contain spaces or parens.
            $afterComm = substr($stat, (int) strrpos($stat, ')') + 2);
            $fields = explode(' ', $afterComm);
            $childPid = (int) $stat;
            $parentPid = (int) ($fields[1] ?? 0);

            if ($childPid > 0 && $parentPid > 0) {
                $map[$parentPid][] = $childPid;
            }
        }

        return $map;
    }
}
