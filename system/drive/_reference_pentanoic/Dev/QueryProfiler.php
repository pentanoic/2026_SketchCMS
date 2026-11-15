<?php
/**
 * Author: valedrat
 * Product: phpSketch / phpDQuery
 *
 * QueryProfiler — developer query profiling panel.
 * Renders a collapsible HTML panel showing all executed queries,
 * their bindings, execution time, and a slow-query warning.
 *
 * Usage:
 *   // At the bottom of your page (or in a dev toolbar)
 *   echo $db->profiler()->render();
 *
 *   // JSON (for API / AJAX debug toolbar)
 *   echo $db->profiler()->toJson();
 *
 *   // Only show slow queries (>50ms)
 *   echo $db->profiler()->slowOnly(50)->render();
 *
 *   // Inject the panel into a full HTML page
 *   $db->profiler()->inject();   // echoes and exits if ?_dquery_debug=1 in URL
 */

namespace PhpDQuery\Dev;

use PhpDQuery\Core\Database;

class QueryProfiler
{
    private array $log;
    private float $threshold = 0.0;   // ms — 0 = show all

    public function __construct(array $queryLog)
    {
        $this->log = $queryLog;
    }

    // ─── Options ──────────────────────────────────────────────────────────────

    /** Only show queries slower than $ms milliseconds. */
    public function slowOnly(float $ms): self
    {
        $clone            = clone $this;
        $clone->threshold = $ms;
        return $clone;
    }

    // ─── Stats ────────────────────────────────────────────────────────────────

    public function totalTime(): float
    {
        return array_sum(array_column($this->log, 'time')) * 1000;
    }

    public function totalQueries(): int
    {
        return count($this->log);
    }

    public function slowQueries(float $thresholdMs = 100.0): array
    {
        return array_filter($this->log, fn($q) => $q['time'] * 1000 >= $thresholdMs);
    }

    // ─── Export ───────────────────────────────────────────────────────────────

    public function toArray(): array
    {
        return array_map(fn($q) => [
            'sql'      => $q['sql'],
            'bindings' => $q['bindings'],
            'time_ms'  => round($q['time'] * 1000, 3),
        ], $this->log);
    }

    public function toJson(): string
    {
        return json_encode([
            'total_queries' => $this->totalQueries(),
            'total_time_ms' => round($this->totalTime(), 3),
            'queries'       => $this->toArray(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    // ─── HTML Panel ───────────────────────────────────────────────────────────

    public function render(): string
    {
        $queries  = $this->threshold > 0
            ? array_filter($this->log, fn($q) => $q['time'] * 1000 >= $this->threshold)
            : $this->log;

        $total    = $this->totalTime();
        $count    = $this->totalQueries();
        $shown    = count($queries);
        $slowCnt  = count($this->slowQueries(100));
        $uid      = 'dqp_' . substr(md5(uniqid()), 0, 6);

        $rows = '';
        foreach (array_values($queries) as $i => $q) {
            $ms      = round($q['time'] * 1000, 3);
            $slow    = $ms >= 100;
            $rowCls  = $slow ? 'dqp-slow' : '';
            $badge   = $slow ? '<span class="dqp-badge">SLOW</span>' : '';
            $bStr    = empty($q['bindings']) ? '—' : htmlspecialchars(json_encode($q['bindings'], JSON_UNESCAPED_UNICODE));
            $sqlHtml = htmlspecialchars($q['sql']);

            $rows .= <<<HTML
            <tr class="{$rowCls}">
                <td class="dqp-n">{$i}</td>
                <td class="dqp-sql"><code>{$sqlHtml}</code></td>
                <td class="dqp-bind">{$bStr}</td>
                <td class="dqp-time">{$ms}ms {$badge}</td>
            </tr>
            HTML;
        }

        $filterNote = $this->threshold > 0
            ? " &nbsp;<small>(showing &ge;{$this->threshold}ms only, {$shown}/{$count})</small>"
            : '';

        return <<<HTML
        <style>
        #{$uid}{font-family:monospace;font-size:12px;background:#1e1e2e;color:#cdd6f4;position:fixed;bottom:0;left:0;right:0;z-index:99999;max-height:40vh;overflow:auto;box-shadow:0 -2px 12px #0007;border-top:2px solid #89b4fa}
        #{$uid} summary{background:#181825;padding:6px 12px;cursor:pointer;list-style:none;display:flex;gap:16px;align-items:center;color:#89b4fa;font-weight:bold;position:sticky;top:0}
        #{$uid} summary span{color:#cdd6f4;font-weight:normal}
        #{$uid} table{width:100%;border-collapse:collapse}
        #{$uid} th{background:#313244;padding:4px 8px;text-align:left;color:#89b4fa;position:sticky;top:30px}
        #{$uid} td{padding:3px 8px;border-bottom:1px solid #313244;vertical-align:top}
        #{$uid} .dqp-n{color:#6c7086;width:28px}
        #{$uid} .dqp-sql code{color:#a6e3a1;white-space:pre-wrap;word-break:break-all}
        #{$uid} .dqp-bind{color:#f38ba8;max-width:200px;word-break:break-all}
        #{$uid} .dqp-time{color:#fab387;white-space:nowrap}
        #{$uid} .dqp-slow td{background:#2a1a1a}
        #{$uid} .dqp-badge{background:#f38ba8;color:#1e1e2e;border-radius:3px;padding:1px 4px;font-size:10px;margin-left:4px}
        #{$uid} .dqp-warn{color:#f38ba8}
        </style>
        <details id="{$uid}">
            <summary>
                🔍 phpDQuery Profiler
                &nbsp;|&nbsp; Queries: <span>{$count}</span>
                &nbsp;|&nbsp; Total: <span>{$total}ms</span>
                &nbsp;|&nbsp; Slow (&ge;100ms): <span class="dqp-warn">{$slowCnt}</span>
                {$filterNote}
            </summary>
            <table>
                <thead><tr><th>#</th><th>SQL</th><th>Bindings</th><th>Time</th></tr></thead>
                <tbody>{$rows}</tbody>
            </table>
        </details>
        HTML;
    }

    /**
     * If ?_dquery_debug=1 is in the URL, dump profiler JSON and exit.
     * Otherwise, append the HTML panel to output.
     * Call at the very end of your page.
     */
    public function inject(): void
    {
        if (isset($_GET['_dquery_debug'])) {
            header('Content-Type: application/json');
            echo $this->toJson();
            exit(0);
        }
        echo $this->render();
    }
}
